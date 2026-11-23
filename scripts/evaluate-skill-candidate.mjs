#!/usr/bin/env node

import { createHash } from 'node:crypto';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, isAbsolute, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

function fail(message) { throw new Error(message); }

function parse(argv) {
    const options = { changedFiles: [] };
    const valued = new Set(['root', 'manifest', 'baseline', 'candidate', 'candidate-evidence', 'evidence', 'report', 'changed-file']);
    for (let index = 0; index < argv.length; index += 1) {
        const name = argv[index].replace(/^--/, '');
        if (!valued.has(name)) {
            fail(`Unknown argument: ${argv[index]}`);
        }
        const value = argv[++index];
        if (!value || value.startsWith('--')) {
            fail(`--${name} requires a value.`);
        }
        if (name === 'changed-file') options.changedFiles.push(value);
        else options[name] = value;
    }
    return options;
}

function inside(root, path) {
    const absolute = resolve(root, path);
    if (relative(root, absolute).startsWith('..')) fail(`Path escapes the trusted root: ${path}`);
    return absolute;
}

function text(path) { return readFileSync(path, 'utf8'); }
function metric(value) {
    const bytes = Buffer.byteLength(value);
    return { bytes, words: value.trim() ? value.trim().split(/\s+/u).length : 0, estimatedTokens: Math.ceil(bytes / 4) };
}
function assess(value, criteria, fixtures) {
    const lower = value.toLowerCase();
    const concepts = Object.fromEntries(Object.entries(criteria.concepts).map(([name, terms]) => [name, terms.every((term) => lower.includes(term))]));
    const publicFixtures = fixtures.map((fixture) => ({ id: fixture.id, passed: fixture.concepts.every((name) => concepts[name]) }));
    return {
        metrics: metric(value), concepts, publicFixtures,
        publicScore: publicFixtures.filter((fixture) => fixture.passed).length,
        falsePositiveSignals: criteria.falsePositiveSignals.filter((signal) => lower.includes(signal)),
    };
}
function matches(path, allowed) { return allowed.some((entry) => path === entry || path.startsWith(entry)); }
function evidenceRecord(path, evidenceId) {
    const entries = JSON.parse(text(path)).evidence;
    if (!Array.isArray(entries)) fail(`Evidence file is invalid: ${path}`);
    return entries.find((entry) => entry.id === evidenceId);
}
function validatesEvidenceLifecycle(trustedPath, candidatePath, evidenceId) {
    const trustedEntries = JSON.parse(text(trustedPath)).evidence;
    const candidateEntries = JSON.parse(text(candidatePath)).evidence;
    if (!Array.isArray(trustedEntries) || !Array.isArray(candidateEntries) || trustedEntries.length !== candidateEntries.length) return false;
    return trustedEntries.every((trusted) => {
        const candidate = candidateEntries.find((entry) => entry.id === trusted.id);
        if (!candidate) return false;
        if (trusted.id === evidenceId) {
            const { status: trustedStatus, ...trustedFields } = trusted;
            const { status: candidateStatus, ...candidateFields } = candidate;
            return trustedStatus === 'eligible' && candidateStatus === 'proposed'
                && JSON.stringify(candidateFields) === JSON.stringify(trustedFields);
        }
        return JSON.stringify(candidate) === JSON.stringify(trusted);
    });
}

export function evaluate(options) {
    const defaultRoot = resolve(dirname(new URL(import.meta.url).pathname), '..');
    const root = resolve(options.root ?? defaultRoot);
    const manifest = JSON.parse(text(inside(root, options.manifest ?? 'skills-evaluation/joomla-security/manifest.json')));
    if (!/^[a-z0-9][a-z0-9:_@.#/-]*$/i.test(options.evidence ?? '')) fail('--evidence must be a stable, non-empty evidence identifier.');
    const criteriaText = text(inside(root, manifest.criteriaPath));
    const checksum = createHash('sha256').update(criteriaText).digest('hex');
    if (checksum !== manifest.criteriaSha256) fail('The immutable evaluation criteria checksum does not match the manifest.');
    const criteria = JSON.parse(criteriaText);
    const candidate = options.candidate
        ? (isAbsolute(options.candidate) ? resolve(options.candidate) : resolve(root, options.candidate))
        : inside(root, manifest.candidateRelativePath);
    if (!candidate.endsWith(manifest.candidateRelativePath)) fail(`Candidate must end in ${manifest.candidateRelativePath}.`);
    const protectedChanges = options.changedFiles.filter((path) => matches(path, manifest.protectedPaths));
    const disallowedChanges = options.changedFiles.filter((path) => !matches(path, manifest.allowedCandidatePaths));
    const missingRequiredChanges = manifest.requiredCandidatePaths.filter((path) => !options.changedFiles.includes(path));
    const trustedEvidencePath = inside(root, 'data/skill-improvement/evidence.json');
    const trustedEvidence = evidenceRecord(trustedEvidencePath, options.evidence);
    if (!trustedEvidence || trustedEvidence.status !== 'eligible') fail(`Evidence ${options.evidence} is not eligible on the trusted main branch.`);
    const missingControls = (trustedEvidence.controlPaths ?? []).filter((path) => !existsSync(inside(root, path)));
    const candidateEvidencePath = options['candidate-evidence']
        ? (isAbsolute(options['candidate-evidence']) ? resolve(options['candidate-evidence']) : resolve(root, options['candidate-evidence']))
        : inside(root, 'data/skill-improvement/evidence.json');
    const candidateEvidence = evidenceRecord(candidateEvidencePath, options.evidence);
    const evidenceLifecycleValid = candidateEvidence?.status === 'proposed'
        && validatesEvidenceLifecycle(trustedEvidencePath, candidateEvidencePath, options.evidence);
    const fixtures = JSON.parse(text(inside(root, manifest.publicFixtures)));
    const baseline = assess(text(inside(root, options.baseline ?? manifest.baselinePath)), criteria, fixtures);
    const candidateResult = assess(text(candidate), criteria, fixtures);
    const limits = {
        size: candidateResult.metrics.bytes <= criteria.maximumInstructionBytes,
        tokens: candidateResult.metrics.estimatedTokens <= criteria.maximumEstimatedTokens,
        falsePositives: candidateResult.falsePositiveSignals.length <= criteria.maximumFalsePositiveSignals,
    };
    const report = {
        target: manifest.target, evidence: options.evidence, criteriaSha256: checksum, evidenceLifecycleValid, missingControls,
        changedFiles: options.changedFiles, protectedChanges, disallowedChanges, missingRequiredChanges, baseline, candidate: candidateResult, limits,
        passed: evidenceLifecycleValid && missingControls.length === 0 && protectedChanges.length === 0 && disallowedChanges.length === 0 && missingRequiredChanges.length === 0 && candidateResult.publicScore >= baseline.publicScore && Object.values(limits).every(Boolean),
    };
    if (options.report) {
        const reportPath = resolve(options.report);
        if (!relative(root, reportPath).startsWith('..')) fail('--report must point outside the trusted repository root.');
        writeFileSync(reportPath, `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    }
    return report;
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
    try {
        const report = evaluate(parse(process.argv.slice(2)));
        process.stdout.write(`${JSON.stringify(report, null, 2)}\n`);
        process.exitCode = report.passed ? 0 : 1;
    } catch (error) {
        process.stderr.write(`${error.message}\n`);
        process.exitCode = 2;
    }
}

import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';
import { evaluate } from '../../scripts/evaluate-skill-candidate.mjs';

const root = fileURLToPath(new URL('../../', import.meta.url));
const manifestPath = new URL('../../skills-evaluation/joomla-security/manifest.json', import.meta.url);
const criteriaPath = new URL('../../skills-evaluation/joomla-security/criteria.json', import.meta.url);
const eventsManifestPath = new URL('../../skills-evaluation/joomla-events/manifest.json', import.meta.url);
const eventsCriteriaPath = new URL('../../skills-evaluation/joomla-events/criteria.json', import.meta.url);
const evaluateCandidate = (evidencePath, changedFiles) => evaluate({
    root,
    evidence: 'pr-187-request-output-and-upload-boundaries',
    candidate: '.agents/skills/joomla-security/SKILL.md',
    'candidate-evidence': evidencePath,
    changedFiles,
});
const evaluateEventsCandidate = (evidencePath, changedFiles) => evaluate({
    root,
    target: 'joomla-events',
    evidence: 'pr-235-event-accessor-recursion',
    candidate: '.agents/skills/joomla-events/SKILL.md',
    'candidate-evidence': evidencePath,
    changedFiles,
});

test('the independent criteria checksum is pinned in the manifest', () => {
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    assert.equal(manifest.criteriaSha256, createHash('sha256').update(readFileSync(criteriaPath)).digest('hex'));
});

test('each target pins its own immutable criteria', () => {
    const manifest = JSON.parse(readFileSync(eventsManifestPath, 'utf8'));
    assert.equal(manifest.criteriaSha256, createHash('sha256').update(readFileSync(eventsCriteriaPath)).digest('hex'));
});

test('the evaluator accepts the candidate only when it preserves public security coverage', () => {
    const temporary = mkdtempSync(join(tmpdir(), 'bfng-skill-evidence-'));
    const evidencePath = join(temporary, 'evidence.json');
    const evidence = JSON.parse(readFileSync(new URL('../../data/skill-improvement/evidence.json', import.meta.url), 'utf8'));
    evidence.evidence.find((entry) => entry.id === 'pr-187-request-output-and-upload-boundaries').status = 'proposed';
    writeFileSync(evidencePath, `${JSON.stringify(evidence)}\n`);
    try {
        const report = evaluateCandidate(evidencePath, [
            '.agents/skills/joomla-security/SKILL.md',
            'data/skill-improvement/evidence.json',
        ]);
        assert.equal(report.passed, true);
        assert.equal(report.evidenceLifecycleValid, true);
        assert.deepEqual(report.missingControls, []);
        assert.equal(report.protectedChanges.length, 0);
    } finally {
        rmSync(temporary, { recursive: true, force: true });
    }
});

test('the evaluator refuses an evidence record that was not consumed by this candidate', () => {
    const report = evaluateCandidate(fileURLToPath(new URL('../../data/skill-improvement/evidence.json', import.meta.url)), [
        '.agents/skills/joomla-security/SKILL.md',
        'data/skill-improvement/evidence.json',
    ]);

    assert.equal(report.evidenceLifecycleValid, false);
    assert.equal(report.passed, false);
});

test('the evaluator fails closed when a candidate changes protected criteria', () => {
    const temporary = mkdtempSync(join(tmpdir(), 'bfng-skill-evidence-'));
    const evidencePath = join(temporary, 'evidence.json');
    const evidence = JSON.parse(readFileSync(new URL('../../data/skill-improvement/evidence.json', import.meta.url), 'utf8'));
    evidence.evidence.find((entry) => entry.id === 'pr-187-request-output-and-upload-boundaries').status = 'proposed';
    writeFileSync(evidencePath, `${JSON.stringify(evidence)}\n`);
    try {
        const report = evaluateCandidate(evidencePath, [
            '.agents/skills/joomla-security/SKILL.md',
            'data/skill-improvement/evidence.json',
            'skills-evaluation/joomla-security/criteria.json',
        ]);
        assert.deepEqual(report.protectedChanges, ['skills-evaluation/joomla-security/criteria.json']);
    } finally {
        rmSync(temporary, { recursive: true, force: true });
    }
});

test('the evaluator independently scores the Joomla events target', () => {
    const temporary = mkdtempSync(join(tmpdir(), 'bfng-events-evidence-'));
    const evidencePath = join(temporary, 'evidence.json');
    const evidence = JSON.parse(readFileSync(new URL('../../data/skill-improvement/evidence.json', import.meta.url), 'utf8'));
    evidence.evidence.find((entry) => entry.id === 'pr-235-event-accessor-recursion').status = 'proposed';
    writeFileSync(evidencePath, `${JSON.stringify(evidence)}\n`);
    try {
        const report = evaluateEventsCandidate(evidencePath, [
            '.agents/skills/joomla-events/SKILL.md',
            'data/skill-improvement/evidence.json',
        ]);
        assert.equal(report.target, 'joomla-events');
        assert.equal(report.passed, true);
        assert.equal(report.baseline.publicScore, 0);
        assert.equal(report.candidate.publicScore, 3);
        assert.equal(report.candidate.falsePositiveSignals.length, 0);
        assert.ok(report.candidate.metrics.estimatedTokens <= 600);
    } finally {
        rmSync(temporary, { recursive: true, force: true });
    }
});

test('evidence cannot be replayed against a different target', () => {
    assert.throws(() => evaluate({
        root,
        target: 'joomla-events',
        evidence: 'pr-187-request-output-and-upload-boundaries',
        candidate: '.agents/skills/joomla-events/SKILL.md',
        changedFiles: ['.agents/skills/joomla-events/SKILL.md', 'data/skill-improvement/evidence.json'],
    }), /not allowlisted for joomla-events/);
});

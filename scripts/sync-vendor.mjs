#!/usr/bin/env node
// Third-party files that npm provides byte for byte: jsTree and SweetAlert2.
// package.json pins their exact versions and package-lock.json their
// integrity, so that Dependabot and `npm audit` follow them. The copies
// stay at their current paths, which the asset registry and the renderers
// use: nothing changes for an installed site.
//
// Usage:
//   node scripts/sync-vendor.mjs            copy the files from node_modules
//   node scripts/sync-vendor.mjs --check    fail when a copy differs
//   --root <directory>                      keep the copies under that directory
//
// Files with a local change (pickadate) or without an npm source (Plupload)
// are not managed here: see docs/maintenance/js-libraries-migration-plan.md.
import { copyFileSync, existsSync, mkdirSync, readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const repository = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const jstree = 'administrator/components/com_breezingformsng/libraries/jquery/jstree3';

/** @type {Array<[string, string, string]>} The package, its file and the copy in the repository. */
const FILES = [
    ['jstree', 'dist/jstree.min.js', `${jstree}/jstree.min.js`],
    ...['32px.png', '40px.png', 'style.min.css', 'throbber.gif']
        .map((file) => ['jstree', `dist/themes/default/${file}`, `${jstree}/themes/default/${file}`]),
    // The bundle with its style: it also gives the window.swal alias.
    ['sweetalert2', 'dist/sweetalert2.all.min.js', 'components/com_breezingformsng/libraries/js/sweetalert2.min.js'],
];

const args = process.argv.slice(2);
const check = args.includes('--check');
const rootIndex = args.indexOf('--root');
const root = rootIndex >= 0 ? resolve(args[rootIndex + 1] ?? '') : repository;

const pinned = JSON.parse(readFileSync(resolve(repository, 'package.json'), 'utf8')).dependencies ?? {};
const problems = [];

for (const name of new Set(FILES.map(([pkg]) => pkg))) {
    const version = pinned[name];
    const installedFile = resolve(repository, 'node_modules', name, 'package.json');
    if (!/^\d+\.\d+\.\d+$/.test(version ?? '')) {
        problems.push(`${name}: package.json must pin an exact version, not "${version}".`);
    } else if (!existsSync(installedFile)) {
        problems.push(`${name}: not installed, run npm ci.`);
    } else if (JSON.parse(readFileSync(installedFile, 'utf8')).version !== version) {
        problems.push(`${name}: node_modules holds another version than ${version}, run npm ci.`);
    }
}

let updated = 0;

if (problems.length === 0) {
    for (const [name, source, copy] of FILES) {
        const from = resolve(repository, 'node_modules', name, source);
        const to = resolve(root, copy);
        const current = existsSync(to) ? readFileSync(to) : null;

        if (current !== null && current.equals(readFileSync(from))) {
            continue;
        }
        if (check) {
            problems.push(`${copy} differs from ${name}@${pinned[name]} ${source}.`);
        } else {
            mkdirSync(dirname(to), { recursive: true });
            copyFileSync(from, to);
            updated++;
        }
    }
}

if (problems.length > 0) {
    console.error(problems.join('\n'));
    console.error(check ? 'Run npm run vendor:sync and commit the result.' : 'Nothing was copied.');
    process.exit(1);
}

console.log(check ? `Vendored libraries match package-lock.json (${FILES.length} files).` : `Vendored libraries synced: ${updated} of ${FILES.length} files updated.`);

import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { appendFileSync, mkdtempSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

const script = new URL('../../scripts/sync-vendor.mjs', import.meta.url).pathname;
const run = (...args) => spawnSync('node', [script, ...args], { encoding: 'utf8' });

test('the vendored copies of the repository match the pinned npm packages', () => {
    const result = run('--check');

    assert.equal(result.status, 0, result.stderr);
});

test('the libraries managed by npm are pinned to exact versions', () => {
    const { dependencies } = JSON.parse(readFileSync(new URL('../../package.json', import.meta.url), 'utf8'));

    assert.deepEqual(Object.keys(dependencies).sort(), ['jstree', 'sweetalert2']);
    for (const [name, version] of Object.entries(dependencies)) {
        assert.match(version, /^\d+\.\d+\.\d+$/, name);
    }
});

test('a copy that differs, or is missing, is reported then repaired', () => {
    const root = mkdtempSync(join(tmpdir(), 'bfng-vendor-'));

    try {
        const missing = run('--check', '--root', root);
        assert.equal(missing.status, 1);
        assert.match(missing.stderr, /libraries\/js\/sweetalert2\.min\.js differs from sweetalert2@11\.\d+\.\d+/);

        assert.equal(run('--root', root).status, 0);
        assert.equal(run('--check', '--root', root).status, 0);

        appendFileSync(join(root, 'components/com_breezingformsng/libraries/js/sweetalert2.min.js'), '// edited');
        const edited = run('--check', '--root', root);
        assert.equal(edited.status, 1);
        assert.match(edited.stderr, /sweetalert2\.min\.js differs/);
        assert.doesNotMatch(edited.stderr, /jstree/);

        assert.equal(run('--root', root).status, 0);
        assert.equal(run('--check', '--root', root).status, 0);
    } finally {
        rmSync(root, { recursive: true, force: true });
    }
});

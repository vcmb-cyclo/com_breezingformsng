import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../media/com_breezingformsng/js/admin/admin-toggle-published.js', import.meta.url), 'utf8');

function openEditor(active, savedReload = false) {
    const requests = [];
    const stored = new Map(savedReload ? [['bfqm:preserve-debug:42', '1']] : []);
    const toggle = {
        dataset: { itemFormId: '42' },
        classList: { contains: (name) => name === 'active' && active },
        setAttribute: () => {},
    };
    const context = {
        Joomla: { getOptions: () => ({ csrfToken: 'test-token' }) },
        document: {
            addEventListener: () => {},
            getElementById: (id) => id === 'bfFormDebugToggle' ? toggle : null,
        },
        sessionStorage: {
            getItem: (key) => stored.get(key) ?? null,
            removeItem: (key) => stored.delete(key),
        },
        fetch: (url, options) => {
            requests.push({ url, body: options.body });
            return new Promise(() => {});
        },
        window: {},
    };

    vm.runInNewContext(source, context);
    return { requests, stored };
}

test('opening a form resets a debug state left on', () => {
    const { requests } = openEditor(true);
    assert.equal(requests.length, 1);
    assert.match(requests[0].url, /task=forms\.setDebug/);
    assert.match(requests[0].body, /id=42&state=0&test-token=1/);
});

test('the reload immediately after Save keeps debug until Close', () => {
    const { requests, stored } = openEditor(true, true);
    assert.equal(requests.length, 0);
    assert.equal(stored.has('bfqm:preserve-debug:42'), false);
});

test('an inactive form consumes any stale reload marker', () => {
    const { requests, stored } = openEditor(false, true);
    assert.equal(requests.length, 0);
    assert.equal(stored.has('bfqm:preserve-debug:42'), false);
});

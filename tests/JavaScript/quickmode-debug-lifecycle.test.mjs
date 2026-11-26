import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../media/com_breezingformsng/js/admin/admin-toggle-published.js', import.meta.url), 'utf8');

function openEditor(active) {
    const requests = [];
    const toggle = {
        dataset: { itemFormId: '42' },
        classList: { contains: (name) => name === 'active' && active },
        setAttribute: () => {},
    };
    const context = {
        Joomla: { getOptions: () => ({ csrfToken: 'test-token' }), Text: { _: (key) => key } },
        document: {
            addEventListener: () => {},
            getElementById: (id) => id === 'bfFormDebugToggle' ? toggle : null,
        },
        fetch: (url, options) => {
            requests.push({ url, body: options.body });
            return new Promise(() => {});
        },
        window: {},
    };

    vm.runInNewContext(source, context);
    return { requests, context };
}

// As in ContentBuilder NG: only a click changes the debug mode, so the forms list and the editor agree.
test('opening a form leaves its debug state alone', () => {
    assert.equal(openEditor(true).requests.length, 0);
    assert.equal(openEditor(false).requests.length, 0);
});

test('the debug icon uses the red badge styling when enabled', () => {
    const { context } = openEditor(false);
    const icon = { className: '' };
    const classes = new Set();
    const link = {
        dataset: {},
        querySelector: () => icon,
        classList: { toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name) },
        hasAttribute: () => false,
    };

    context.bfSetToggleIcon(link, 1, 'debug');
    assert.equal(icon.className, 'fa-solid fa-bug');
    assert.equal(classes.has('active'), true);

    context.bfSetToggleIcon(link, 0, 'debug');
    assert.equal(icon.className, 'fa-solid fa-bug text-muted');
    assert.equal(classes.has('active'), false);
});

test('a button with its own icons shows them', () => {
    const { context } = openEditor(false);
    const icon = { className: '' };
    const link = {
        dataset: { iconOn: 'fa-solid fa-bug text-success', iconOff: 'icon-unpublish' },
        querySelector: () => icon,
        classList: { toggle: () => {} },
        hasAttribute: () => false,
    };

    context.bfSetToggleIcon(link, 1, 'debug');
    assert.equal(icon.className, 'fa-solid fa-bug text-success');
    context.bfSetToggleIcon(link, 0, 'debug');
    assert.equal(icon.className, 'icon-unpublish');
});

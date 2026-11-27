import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';

const source = readFileSync(new URL('../../media/com_breezingformsng/js/admin/forms-columns.js', import.meta.url), 'utf8');
const KEY = 'joomla-tablecolumns-bfng-forms';

function run(localStorage) {
	const window = {};
	Object.defineProperty(window, 'localStorage', { get: () => localStorage });
	runInNewContext(source, { window });
}

function memoryStorage(initial = {}) {
	const data = { ...initial };
	return { data, getItem: (k) => (k in data ? data[k] : null), setItem: (k, v) => { data[k] = String(v); } };
}

test('hides the Package and audit columns by default', () => {
	const storage = memoryStorage();
	run(storage);
	assert.equal(storage.data[KEY], '4,9,10,12');
});

test('hides the audit columns of the other lists by default', () => {
	const storage = memoryStorage();
	run(storage);
	assert.equal(storage.data['joomla-tablecolumns-bfng-scripts'], '8,9,11');
	assert.equal(storage.data['joomla-tablecolumns-bfng-pieces'], '8,9,11');
	assert.equal(storage.data['joomla-tablecolumns-bfng-integrator'], '7,8,10');
});

test('hides the new audit columns once for a choice stored before them', () => {
	// Name and Modified, then in ninth place, were hidden.
	const storage = memoryStorage({ [KEY]: '3,9' });
	run(storage);
	assert.equal(storage.data[KEY], '3,11,9,10,12');
	// Shown again by the user: they stay shown.
	storage.data[KEY] = '3';
	run(storage);
	assert.equal(storage.data[KEY], '3');
});

test('keeps a stored choice', () => {
	const storage = memoryStorage({ [KEY]: '', [`${KEY}-audit`]: '2' });
	run(storage);
	assert.equal(storage.data[KEY], '');
});

test('does not throw when storage access is blocked', () => {
	const blocked = { getItem() { throw new Error('blocked'); }, setItem() { throw new Error('blocked'); } };
	assert.doesNotThrow(() => run(blocked));
	assert.doesNotThrow(() => run(undefined));
	const noWrite = { getItem: () => null, setItem() { throw new Error('quota'); } };
	assert.doesNotThrow(() => run(noWrite));
});

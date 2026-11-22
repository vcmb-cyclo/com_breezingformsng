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

test('hides the Package column by default', () => {
	const storage = memoryStorage();
	run(storage);
	assert.equal(storage.data[KEY], '4');
});

test('keeps a stored choice', () => {
	const storage = memoryStorage({ [KEY]: '' });
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

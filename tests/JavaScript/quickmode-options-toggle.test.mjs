import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../media/com_breezingformsng/js/admin/quickmode-options-toggle.js', import.meta.url), 'utf8');
const fields = new Map([
    ['s1lib', { style: { display: 'none' } }],
    ['s1code', { style: { display: 'none' } }],
    ['p1lib', { style: { display: 'none' } }],
    ['p1code', { style: { display: 'none' } }],
]);
const window = {};
runInNewContext(source, {
    window,
    document: { getElementById: (id) => fields.get(id) },
});

for (const [prefix, libraryId, codeId] of [['s1', 's1lib', 's1code'], ['p1', 'p1lib', 'p1code']]) {
    window.bfToggle(libraryId, codeId, '1');
    assert.equal(fields.get(libraryId).style.display, '', `${prefix} Library is visible`);
    assert.equal(fields.get(codeId).style.display, 'none', `${prefix} Custom is hidden`);

    window.bfToggle(libraryId, codeId, '2');
    assert.equal(fields.get(libraryId).style.display, 'none', `${prefix} Library is hidden`);
    assert.equal(fields.get(codeId).style.display, '', `${prefix} Custom is visible`);

    window.bfToggle(libraryId, codeId, '0');
    assert.equal(fields.get(libraryId).style.display, 'none', `${prefix} Library is hidden for None`);
    assert.equal(fields.get(codeId).style.display, 'none', `${prefix} Custom is hidden for None`);
}

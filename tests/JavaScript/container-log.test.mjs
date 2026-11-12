import { test } from 'node:test';
import assert from 'node:assert/strict';
import { addedWarnings } from '../E2E/container-log.mjs';

const warning = 'PHP Warning:  Undefined array key "password" in QuickmodeModel.php on line 264';

test('a single warning added to a log ending with a line break is found', () => {
    const before = 'prior log\n';
    // Splitting both logs and slicing by the number of lines would return only an empty line.
    assert.deepEqual(addedWarnings(before, `${before}${warning}\n`), [warning]);
});

test('a single warning added to a log without a final line break is found', () => {
    assert.deepEqual(addedWarnings('prior log', `prior log\n${warning}`), [warning]);
});

test('a warning that was already there is not counted again', () => {
    const before = `${warning}\nprior log\n`;
    assert.deepEqual(addedWarnings(before, `${before}another line\n`), []);
});

test('several warnings are all returned, the other lines left out', () => {
    const before = 'prior log\n';
    const notice = 'PHP Notice:  ob_end_clean(): Failed to delete buffer';
    const after = `${before}${warning}\nGET /index.php 200\n${notice}\n`;
    assert.deepEqual(addedWarnings(before, after), [warning, notice]);
});

test('an empty addition gives nothing', () => {
    assert.deepEqual(addedWarnings('prior log\n', 'prior log\n'), []);
});

test('a log that does not continue the earlier one is an error, not a silent pass', () => {
    assert.throws(() => addedWarnings('prior log\n', 'new log\n'), /does not continue/);
});

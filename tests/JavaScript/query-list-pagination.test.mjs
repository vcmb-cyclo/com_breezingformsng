import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { runInNewContext } from 'node:vm';

test('query list pagination renders values as text on every page', () => {
    const php = String.raw`require 'components/com_breezingformsng/src/Service/Rendering/QueryListPageScriptBuilder.php';
        echo (new Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QueryListPageScriptBuilder())->build(
            ['start' => 'S', 'previous' => 'P', 'next' => 'N', 'end' => 'E'], false, 0, 0, false, "\n");`;
    const script = execFileSync('php', ['-r', php], { encoding: 'utf8' });
    const cell = {
        textContent: '',
        set innerHTML(value) {
            throw new Error(`Query value was interpreted as HTML: ${value}`);
        },
    };
    const row = { cells: [cell], style: {} };
    const context = {
        ff_queryRows: { 7: [['<b>first</b>'], ['<img src=x onerror=alert(1)>']] },
        ff_queryCurrPage: { 7: 0 },
        ff_queryPageSize: { 7: 1 },
        ff_queryPagenav: { 7: 0 },
        ff_queryCols: { 7: [1] },
        ff_queryCheckbox: { 7: 0 },
        ff_queryHeader: { 7: 0 },
        document: { getElementById: () => ({ rows: [row] }) },
        window: { scrollTo() {} },
    };
    runInNewContext(script, context);
    context.ff_dispQueryPage(7, 1);
    assert.equal(cell.textContent, '<b>first</b>');
    context.ff_dispQueryPage(7, 2);
    assert.equal(cell.textContent, '<img src=x onerror=alert(1)>');
});

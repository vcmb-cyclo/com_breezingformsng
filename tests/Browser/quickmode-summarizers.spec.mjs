import { test, expect } from '@playwright/test';

const SCRIPTS = 'media/com_breezingformsng/js/site/';
const HELPERS = [
    'quickmode-core-helpers.js',
    'quickmode-core-helpers-bootstrap.js',
    'quickmode-field-helpers-bootstrap.js',
];

// Option values are rendered as text; a custom fieldCalc receives the raw
// value and may return HTML.
for (const helper of HELPERS) {
    test.describe(helper, () => {
        test.beforeEach(async ({ page }) => {
            await page.setContent(`
                <script>
                    function wrap(els) {
                        var o = {
                            val: function () { return els[0] ? els[0].value : undefined; },
                            html: function (v) { if (v === undefined) { return els[0].innerHTML; } els.forEach(function (e) { e.innerHTML = v; }); return o; },
                            text: function (v) { if (v === undefined) { return els[0].textContent; } els.forEach(function (e) { e.textContent = v; }); return o; },
                            css: function (k, v) { els.forEach(function (e) { e.style[k] = v; }); return o; },
                            parent: function () { return wrap(els.map(function (e) { return e.parentNode; })); },
                            closest: function (s) { return wrap(els.map(function (e) { return e.closest(s); }).filter(Boolean)); }
                        };
                        return o;
                    }
                    var JQuery = function (selector) { return wrap(Array.from(document.querySelectorAll(selector))); };
                    var ff_processor = { form_id: 'ff_form1' };
                    var bfSummarizers = [];
                    var bfCharsLeftLabel = 'chars left';
                    var received = [];
                </script>
                <form name="ff_form1">
                    <input type="radio" name="ff_nm_radio[]" value="&lt;b&gt;A&amp;B&lt;/b&gt;" checked>
                    <select name="ff_nm_select[]" multiple>
                        <option value="A&amp;B" selected>A&amp;B</option>
                        <option value="C&lt;D" selected>C&lt;D</option>
                    </select>
                </form>
                <div><span id="sumRadio"></span></div>
                <div><span id="sumSelect"></span></div>
            `);
            await page.addScriptTag({ path: SCRIPTS + helper });
        });

        test('renders raw option values as text without fieldCalc', async ({ page }) => {
            await page.evaluate(() => {
                bfRegisterSummarize('sumRadio', 'radio', 'bfRadioGroup', '', false);
                bfRegisterSummarize('sumSelect', 'select', 'bfSelect', '', false);
                populateSummarizers();
            });

            await expect(page.locator('#sumRadio')).toHaveText('<b>A&B</b>');
            await expect(page.locator('#sumRadio b')).toHaveCount(0);
            await expect(page.locator('#sumSelect')).toHaveText('A&B, C<D');
        });

        test('passes raw values to fieldCalc and renders its HTML', async ({ page }) => {
            await page.evaluate(() => {
                window.bfFieldCalcsumRadio = function (value) { received.push(value); return '<em>radio</em>'; };
                window.bfFieldCalcsumSelect = function (value) { received.push(value); return '<em>select</em>'; };
                bfRegisterSummarize('sumRadio', 'radio', 'bfRadioGroup', '', false);
                bfRegisterSummarize('sumSelect', 'select', 'bfSelect', '', false);
                populateSummarizers();
            });

            expect(await page.evaluate(() => received)).toEqual(['<b>A&B</b>', 'A&B, C<D']);
            await expect(page.locator('#sumRadio em')).toHaveText('radio');
            await expect(page.locator('#sumSelect em')).toHaveText('select');
        });
    });
}

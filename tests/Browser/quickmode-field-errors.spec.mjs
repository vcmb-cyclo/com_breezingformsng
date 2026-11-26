import { test, expect } from '@playwright/test';

// Classic QuickMode: the error list on top stays, and each message is repeated under its field.
const row = (name, field, extra = '') => `<section class="bfElemWrap ${extra}" id="row${name}"><label>${name}</label>${field}</section>`;
const markup = (theme) => `<div class="bfFormDiv" data-bf-theme="${theme}"><form class="bfQuickMode" name="form">
    <span class="bfErrorMessage" style="display:none"></span>
    <fieldset>
        ${row('LastName', '<input id="last" type="text" name="ff_nm_LastName[]" aria-describedby="help">', 'bfLabelLeft')}
        <p id="help">Help</p>
        ${row('Reason', '<input type="radio" name="ff_nm_Reason[]" value="1"><input type="radio" name="ff_nm_Reason[]" value="2">')}
        ${row('Phone', '<input type="hidden" name="ff_nm_Phone[]"><input type="tel" id="visiblePhone">')}
    </fieldset></form></div>`;
const errors = [
    ['LastName', 'Please enter your <b>last</b> name.\n'],
    ['Reason', 'Please choose a reason.\n'],
    ['Phone', 'Please enter your phone number.\n'],
];

const open = async (page, theme, { show = true, dark = false } = {}) => {
    await page.setContent(`<!doctype html><html data-bs-theme="${dark ? 'dark' : 'light'}"><head><meta charset="utf-8"></head>
        <body>${markup(theme)}</body></html>`);
    await page.addStyleTag({ path: 'components/com_breezingformsng/themes/quickmode/system.css' });
    await page.addStyleTag({ path: `media/breezingforms/themes/${theme}/theme.css` });
    await page.addScriptTag({ path: 'node_modules/jquery/dist/jquery.min.js' });
    await page.addScriptTag({ content: `var JQuery = window.jQuery; var bfShowDefaultErrors = ${show};
        var inlineErrorElements = [];
        function ff_getElementByName(name) { return document.form['ff_nm_' + name + '[]'] || null; }` });
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-error-alerts.js' });
};

const showErrors = (page, list) => page.evaluate((items) => {
    window.inlineErrorElements = items;
    window.bfShowErrors(items.map((item) => item[1]).join(''));
}, list);

// Horizontal distance between the left edges of a field and of its message.
const offsetFromField = () => Math.abs(document.querySelector('#rowLastName > .bf-field-error').getBoundingClientRect().left
    - document.querySelector('#last').getBoundingClientRect().left);

test('repeats each message under its field and keeps the list on top', async ({ page }) => {
    await open(page, 'vcmb_j5b');
    await showErrors(page, [...errors, ['LastName', ''], ['Unknown', 'No such field.\n']]);

    await expect(page.locator('.bfErrorMessage .bfError')).toHaveCount(5);
    await expect(page.locator('.bf-field-error')).toHaveCount(3);
    await expect(page.locator('#rowLastName > .bf-field-error')).toHaveText('Please enter your <b>last</b> name.');
    await expect(page.locator('#rowReason > .bf-field-error')).toHaveText('Please choose a reason.');
    await expect(page.locator('#last')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#last')).toHaveAttribute('aria-describedby', /^help \S+$/);
    await expect(page.locator('#visiblePhone')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#last')).toHaveCSS('border-top-color', 'rgb(160, 21, 21)');
});

test('removes the messages of corrected fields at the next check', async ({ page }) => {
    await open(page, 'vcmb_j5b');
    await showErrors(page, errors);
    await showErrors(page, errors.slice(1));

    await expect(page.locator('.bf-field-error')).toHaveCount(2);
    await expect(page.locator('#last')).not.toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#last')).toHaveAttribute('aria-describedby', 'help');
    await expect(page.locator('#last')).not.toHaveClass(/is-invalid/);

    // ff_validation() calls this before a check that may find no error at all.
    await page.evaluate(() => window.bfClearFieldErrors());
    await expect(page.locator('.bf-field-error')).toHaveCount(0);
    await expect(page.locator('[aria-invalid]')).toHaveCount(0);
});

test('adds nothing when the form does not show the default errors', async ({ page }) => {
    await open(page, 'vcmb_j5b', { show: false });
    await showErrors(page, errors);

    await expect(page.locator('.bf-field-error')).toHaveCount(0);
});

test('aligns the message again after a resize and on a page shown later', async ({ page }) => {
    await open(page, 'vcmb_j5b');
    await page.evaluate(() => {
        document.querySelector('fieldset').style.display = 'none';
    });
    await showErrors(page, errors);
    await page.evaluate(() => {
        document.querySelector('fieldset').style.display = '';
    });
    await expect.poll(() => page.evaluate(offsetFromField)).toBeLessThan(2);

    await page.setViewportSize({ width: 480, height: 700 });
    await expect.poll(() => page.evaluate(offsetFromField)).toBeLessThan(2);
});

for (const theme of ['vcmb_j5', 'vcmb_j5b', 'vcmb_j6']) {
    for (const dark of [false, true]) {
        test(`the field message of ${theme} is readable, ${dark ? 'dark' : 'light'}`, async ({ page }) => {
            await open(page, theme, { dark });
            await showErrors(page, errors);

            const result = await page.locator('#rowLastName > .bf-field-error').evaluate((message) => {
                const channels = (value) => value.match(/[\d.]+/g).map(Number);
                const luminance = (value) => {
                    const [r, g, b] = channels(value).slice(0, 3).map((channel) => {
                        const unit = channel / 255;

                        return unit <= 0.04045 ? unit / 12.92 : ((unit + 0.055) / 1.055) ** 2.4;
                    });

                    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
                };
                let background = 'rgb(255, 255, 255)';

                for (let node = message; node; node = node.parentElement) {
                    const value = getComputedStyle(node).backgroundColor;

                    if ((channels(value)[3] ?? 1) > 0.95) {
                        background = value;
                        break;
                    }
                }

                const [high, low] = [luminance(getComputedStyle(message).color), luminance(background)].sort((a, b) => b - a);
                const box = message.getBoundingClientRect();
                const field = document.querySelector('#last').getBoundingClientRect();

                return { ratio: (high + 0.05) / (low + 0.05), below: box.top >= field.bottom - 1 };
            });

            expect(result.ratio).toBeGreaterThanOrEqual(4.5);
            expect(result.below).toBe(true);
            await expect.poll(() => page.evaluate(offsetFromField)).toBeLessThan(2);
        });
    }
}

test('keeps the message close to its field in the Bootstrap renderers', async ({ page }) => {
    const wrapper = (id, message) => `<div class="bfElemWrap mb-3"><div class="bf-form-inline"><div class="bf-form-group mb-3">
        <label>${id}</label><input id="${id}" type="text"></div></div>${message}</div>`;
    // The mb-3 utility of Bootstrap is !important.
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"><style>.mb-3 { margin-bottom: 1rem !important; }
        .invalid-feedback { display: block; margin-top: .25rem; }</style></head><body><form class="bfQuickMode">
        ${wrapper('wrong', '<div class="invalid-feedback bf-field-error">Required.</div>')}${wrapper('right', '')}</form></body></html>`);
    await page.addStyleTag({ path: 'components/com_breezingformsng/themes/quickmode-bootstrap5/system.css' });

    const gap = await page.evaluate(() => document.querySelector('.bf-field-error').getBoundingClientRect().top
        - document.querySelector('#wrong').getBoundingClientRect().bottom);

    expect(gap).toBeLessThanOrEqual(6);
    await expect(page.locator('#right').locator('..')).toHaveCSS('margin-bottom', '16px');
});

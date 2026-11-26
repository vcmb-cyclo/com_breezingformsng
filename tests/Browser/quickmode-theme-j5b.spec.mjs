import { test, expect } from '@playwright/test';

// Markup of the Classic renderer: the buttons carry the Bootstrap classes of the site template.
const field = (name, control, hint = '') => `<section class="bfElemWrap bfLabelLeft" id="row${name}">
    <label><span class="editlinktip hasTooltip"><span class="bfTooltip">&nbsp;</span></span>${name}</label>${control}
    ${hint && `<small class="bfHint">${hint}</small>`}</section>`;
const button = (name, label) => `<button type="button" class="btn btn-primary ${name} button"><span>${label}</span></button>`;
const markup = `<div class="bfFormDiv" data-bf-theme="vcmb_j5b"><form class="bfQuickMode">
    <span class="bfErrorMessage"><div class="bfError">Please enter your name.</div></span>
    <fieldset><legend>Details</legend>
        ${field('text', '<input id="text" type="text">', 'As on your identity card')}
        ${field('mail', '<input id="mail" type="email">')}
        ${field('number', '<input id="number" type="number">')}
        ${field('select', '<select id="select"><option>One</option></select>')}
        ${field('area', '<textarea id="area"></textarea>')}
        ${field('date', '<input type="text" id="date"><button type="button" class="ff_elem bfCalendar btn btn-secondary">...</button>')}
        ${field('check', '<input id="check" type="checkbox">')}
    </fieldset>
    ${button('bfPrevButton', 'Previous')}${button('bfNextButton', 'Next')}${button('bfSubmitButton', 'Send')}${button('bfCancelButton', 'Reset')}
</form></div>`;

const open = async (page, scheme = 'light') => {
    await page.setContent(`<!doctype html><html data-bs-theme="${scheme}"><head><meta charset="utf-8">
        <style>.btn { padding: 6px 12px; } .btn-primary { background: #2a69b8; color: #fff; }</style></head>
        <body>${markup}</body></html>`);
    await page.addStyleTag({ path: 'components/com_breezingformsng/themes/quickmode/system.css' });
    await page.addStyleTag({ path: 'media/breezingforms/themes/vcmb_j5b/theme.css' });
};

const height = async (page, selector) => (await page.locator(selector).boundingBox()).height;

test('gives the fields and buttons a compact, consistent size', async ({ page }) => {
    await open(page);

    for (const selector of ['#text', '#mail', '#number', '#select', '#area', '#date', '.bfCalendar',
        '.bfPrevButton', '.bfNextButton', '.bfSubmitButton', '.bfCancelButton']) {
        expect(await height(page, selector), selector).toBeGreaterThanOrEqual(28);
    }

    const looks = await page.evaluate(() => ['#text', '#mail', '#number', '#select', '#area'].map((selector) => {
        const style = getComputedStyle(document.querySelector(selector));

        return `${style.borderTopWidth} ${style.borderTopStyle} ${style.borderTopColor} ${style.borderTopLeftRadius}`;
    }));
    expect(new Set(looks).size).toBe(1);
    const widths = await Promise.all(['#text', '#mail', '#number', '#select'].map(async (selector) =>
        Math.round((await page.locator(selector).boundingBox()).width)));
    expect(new Set(widths).size, widths.join(' ')).toBe(1);
    expect(await height(page, '#check')).toBeLessThan(30);

    const send = await page.locator('.bfSubmitButton').boundingBox();
    const next = await page.locator('.bfNextButton').boundingBox();
    expect(Math.abs(next.x - send.x) - send.width).toBeGreaterThanOrEqual(7);

    // Neither the one-line fields nor the buttons are higher than that.
    for (const selector of ['#text', '#mail', '#select', '.bfPrevButton', '.bfSubmitButton', '.bfCalendar']) {
        expect(await height(page, selector), selector).toBeLessThanOrEqual(32);
    }
});

for (const scheme of ['light', 'dark']) {
    test(`tells the main buttons from the secondary ones, ${scheme}`, async ({ page }) => {
        await open(page, scheme);

        const background = (selector) => page.locator(selector).evaluate((node) => getComputedStyle(node).backgroundColor);
        expect(await background('.bfSubmitButton')).toBe(await background('.bfNextButton'));
        expect(await background('.bfPrevButton')).toBe(await background('.bfCancelButton'));
        expect(await background('.bfPrevButton')).not.toBe(await background('.bfSubmitButton'));

        const contrast = await page.evaluate(() => ['.bfSubmitButton', '.bfPrevButton'].map((selector) => {
            const luminance = (value) => {
                const [r, g, b] = value.match(/[\d.]+/g).slice(0, 3).map((channel) => {
                    const unit = channel / 255;

                    return unit <= 0.04045 ? unit / 12.92 : ((unit + 0.055) / 1.055) ** 2.4;
                });

                return 0.2126 * r + 0.7152 * g + 0.0722 * b;
            };
            const style = getComputedStyle(document.querySelector(selector));
            const [high, low] = [luminance(style.color), luminance(style.backgroundColor)].sort((a, b) => b - a);

            return (high + 0.05) / (low + 0.05);
        }));
        contrast.forEach((ratio) => expect(ratio).toBeGreaterThanOrEqual(4.5));
    });
}

test('shows the help under its field instead of the icon', async ({ page }) => {
    await open(page);

    await expect(page.locator('#rowtext .bfHint')).toBeVisible();
    await expect(page.locator('#rowtext .editlinktip')).toBeHidden();
    // A field whose help has no text line keeps its icon.
    await expect(page.locator('#rowmail .editlinktip')).not.toHaveCSS('display', 'none');

    const input = await page.locator('#text').boundingBox();
    const hint = await page.locator('#rowtext .bfHint').boundingBox();
    expect(hint.y).toBeGreaterThanOrEqual(input.y + input.height);
    expect(Math.abs(hint.x - input.x)).toBeLessThan(2);
});

test('puts the section titles on the line of their frame and marks the error list', async ({ page }) => {
    await open(page);

    // The title is raised onto the line of its frame, which saves room.
    await expect(page.locator('legend')).toHaveCSS('margin-top', '-28px');
    await expect(page.locator('.bfErrorMessage')).toHaveCSS('border-left-width', '4px');
});

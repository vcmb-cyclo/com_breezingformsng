import { test, expect } from '@playwright/test';

// The rating widget keeps its value in the field of the form and shows it as stars or a scale.
const open = async (page, options, value = '') => {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body><div class="bfFormDiv">
        <form class="bfQuickMode"><section class="bfElemWrap bfLabelLeft"><label id="bfLabel7" for="ff_elem7">Opinion</label>
        <input id="ff_elem7" type="number" name="ff_nm_Score[]" data-bfng-widget="rating" data-bfng-value="${value}"
            data-bfng-options='${JSON.stringify(options)}'></section><button type="reset">Reset</button></form></div></body></html>`);
    await page.addStyleTag({ path: 'components/com_breezingformsng/themes/quickmode/system.css' });
    await page.addStyleTag({ path: 'media/com_breezingformsng/css/bfng-widgets.css' });
    await page.evaluate(() => {
        window.changes = [];
        document.getElementById('ff_elem7').addEventListener('change', (event) => window.changes.push(event.target.value));
    });
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/bfng-widgets.js' });
};

const field = (page) => page.locator('#ff_elem7');
const radios = (page) => page.getByRole('radiogroup', { name: 'Opinion' }).getByRole('radio');
const choice = (page, value) => page.locator(`.bfng-rating label[data-value="${value}"]`);
const lit = (page) => page.locator('.bfng-rating label.is-on');

test('rates with stars and clears on a second click', async ({ page }) => {
    await open(page, { style: 'stars', min: 1, max: 5 });

    await expect(field(page)).toHaveAttribute('type', 'hidden');
    await expect(radios(page)).toHaveCount(5);
    await expect(field(page)).toHaveValue('');
    await choice(page, 4).click();
    await expect(field(page)).toHaveValue('4');
    await expect(lit(page)).toHaveCount(4);
    await choice(page, 4).click();
    await expect(field(page)).toHaveValue('');
    await expect(lit(page)).toHaveCount(0);
    expect(await page.evaluate(() => window.changes)).toEqual(['4', '']);
});

test('works with the keyboard and names each choice', async ({ page }) => {
    await open(page, { style: 'stars', min: 1, max: 5 });

    await page.getByRole('radio', { name: '2 / 5' }).focus();
    await page.keyboard.press('Space');
    await page.keyboard.press('ArrowRight');
    await expect(field(page)).toHaveValue('3');
});

test('shows a numbered scale with its two captions and restores a value', async ({ page }) => {
    await open(page, { style: 'scale', min: 0, max: 10, low: 'Not at all', high: '<b>Absolutely</b>' }, '7');

    await expect(radios(page)).toHaveCount(11);
    await expect(field(page)).toHaveValue('7');
    await expect(lit(page)).toHaveText(['7']);
    await expect(page.locator('.bfng-rating__low')).toHaveText('Not at all');
    await expect(page.locator('.bfng-rating__high')).toHaveText('<b>Absolutely</b>');
    await choice(page, 0).click();
    await expect(field(page)).toHaveValue('0');
    await page.getByRole('button', { name: 'Reset' }).click();
    await expect(field(page)).toHaveValue('7');
    await expect(lit(page)).toHaveText(['7']);
});

test('sits beside a left label, takes a colour and wraps on a phone', async ({ page }) => {
    await page.setViewportSize({ width: 360, height: 600 });
    await open(page, { style: 'scale', min: 0, max: 10, color: '#336699', large: true });

    const widget = await page.locator('.bfng-rating').boundingBox();
    expect(widget.x + widget.width).toBeLessThanOrEqual(360);
    expect((await choice(page, 0).boundingBox()).height).toBeGreaterThanOrEqual(40);
    await choice(page, 5).click();
    await expect(choice(page, 5)).toHaveCSS('background-color', 'rgb(51, 102, 153)');
});

test('lights the stars in a bright gold on a light page', async ({ page }) => {
    await open(page, { style: 'stars', min: 1, max: 5 }, '3');

    await expect(lit(page).first()).toHaveCSS('color', 'rgb(217, 119, 6)');
});

for (const [label, options] of [
    ['ten stars', { style: 'stars', min: 1, max: 10 }],
    ['ten large stars', { style: 'stars', min: 1, max: 10, large: true }],
    ['a scale from 0 to 10', { style: 'scale', min: 0, max: 10 }],
]) {
    test(`keeps ${label} on one line beside a left label`, async ({ page }) => {
        await page.setViewportSize({ width: 720, height: 600 });
        await open(page, options);
        await page.addStyleTag({ path: 'media/breezingforms/themes/vcmb_j5b/theme.css' });

        const tops = await page.locator('.bfng-rating label').evaluateAll((labels) =>
            labels.map((node) => Math.round(node.getBoundingClientRect().top)));
        expect(new Set(tops).size, tops.join(' ')).toBe(1);
        expect((await page.locator('.bfng-rating label').first().boundingBox()).width).toBeGreaterThanOrEqual(24);
    });
}

for (const [label, options] of [
    ['ten large stars', { style: 'stars', min: 1, max: 10, large: true }],
    ['a scale from 0 to 10', { style: 'scale', min: 0, max: 10, low: 'Not at all', high: 'Absolutely' }],
]) {
    test(`keeps ${label} on one line in the Bootstrap renderers`, async ({ page }) => {
        await page.setViewportSize({ width: 720, height: 600 });
        await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body><div class="bfFormDiv">
            <form class="bfQuickMode"><div class="bfElemWrap"><div class="bf-form-inline"><div class="bf-form-group">
            <label for="ff_elem7">Opinion</label><input id="ff_elem7" type="number" name="ff_nm_Score[]" data-bfng-widget="rating"
            data-bfng-options='${JSON.stringify(options)}'></div></div></div></form></div></body></html>`);
        await page.addStyleTag({ path: 'media/com_breezingformsng/css/bfng-widgets.css' });
        await page.addStyleTag({ path: 'components/com_breezingformsng/themes/quickmode-bootstrap5/system.css' });
        await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/bfng-widgets.js' });

        const boxes = await page.locator('.bfng-rating label').evaluateAll((labels) => labels.map((node) => {
            const box = node.getBoundingClientRect();

            return { top: Math.round(box.top), width: box.width };
        }));
        expect(new Set(boxes.map((box) => box.top)).size).toBe(1);
        expect(Math.max(...boxes.map((box) => box.width))).toBeLessThan(60);

        if (options.low) {
            const choices = await page.locator('.bfng-rating__choices').boundingBox();
            const low = await page.locator('.bfng-rating__low').boundingBox();
            const high = await page.locator('.bfng-rating__high').boundingBox();
            expect(low.y).toBeGreaterThanOrEqual(choices.y + choices.height);
            expect(Math.abs(high.y - low.y)).toBeLessThan(2);
            expect(Math.abs((high.x + high.width) - (choices.x + choices.width))).toBeLessThan(2);
        }

        if (process.env.BFNG_SHOT) {
            await page.screenshot({ path: `${process.env.BFNG_SHOT}-${options.style}.png`, clip: { x: 0, y: 0, width: 720, height: 160 } });
        }
    });
}

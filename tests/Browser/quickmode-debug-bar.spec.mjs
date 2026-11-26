import { test, expect } from '@playwright/test';

const markup = `<div class="bfng-debug"><div class="bfng-debug__bar"><span class="bfng-debug__form">Form</span>
    <span class="bfng-debug__help-box">
    <button type="button" class="bfng-debug__help" aria-label="How to hide this bar">?</button>
    <span class="bfng-debug__tooltip" aria-hidden="true">How to hide this bar</span></span></div></div>
    <p style="margin-block-start: 10rem">Outside</p>`;

for (const width of [375, 1200]) {
    test(`the help of the debug bar shows at once, ${width}px wide`, async ({ page }) => {
        await page.setViewportSize({ width, height: 600 });
        await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>${markup}</body></html>`);
        await page.addStyleTag({ path: 'media/com_breezingformsng/css/site/quickmode-debug-bar.css' });
        await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });

        const help = page.locator('.bfng-debug__help');
        const tooltip = page.locator('.bfng-debug__tooltip');
        await expect(tooltip).toBeHidden();

        await help.hover();
        await expect(tooltip).toBeVisible();

        const box = await tooltip.boundingBox();
        expect(box.x).toBeGreaterThanOrEqual(0);
        expect(box.x + box.width).toBeLessThanOrEqual(width);

        // The pointer travels from the button down to the tooltip, which stays.
        await page.mouse.move(box.x + box.width - 5, box.y + box.height - 5, { steps: 20 });
        await expect(tooltip).toBeVisible();

        await page.locator('p').hover();
        await expect(tooltip).toBeHidden();

        await help.click();
        await page.locator('p').hover();
        await expect(tooltip).toBeVisible();

        await page.keyboard.press('Escape');
        await expect(tooltip).toBeHidden();

        await help.focus();
        await expect(tooltip).toBeVisible();
    });
}

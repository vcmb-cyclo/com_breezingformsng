import { test, expect } from '@playwright/test';

// The Classic renderer lets the rows of a Joomla calendar overflow, so that the popup is not clipped:
// the row must still contain its floated label, field and button.
const row = (id, label, control) => `<section class="bfElemWrap bfLabelLeft" id="${id}"><label>${label}</label>${control}</section>`;
const calendar = `<div class="field-calendar" style="float: left; width: 80%"><input type="text" class="ff_elem bfCalendar">
    <button type="button" class="btn">C</button></div>`;
const responsive = `<span class="bfElementGroupNoWrap"><input class="ff_elem bfCalendarInput" type="text" style="width: 65%">
    <button type="button" class="bfCalendar btn">...</button></span>`;

for (const theme of ['vcmb_j5', 'vcmb_j5b', 'vcmb_j6', 'default']) {
    test(`keeps each calendar on its own row with ${theme}`, async ({ page }) => {
        await page.setViewportSize({ width: 1300, height: 600 });
        await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body><div class="bfFormDiv" data-bf-theme="${theme}">
            <form class="bfQuickMode">${row('cal', 'Calendar', calendar)}${row('resp', 'Responsive calendar', responsive)}
            <section class="bfElemWrap" id="hidden" style="display: none">x</section></form></div></body></html>`);
        await page.addStyleTag({ path: 'components/com_breezingformsng/themes/quickmode/system.css' });
        await page.addStyleTag({ path: `media/breezingforms/themes/${theme}/theme.css` });
        // As the renderer does for the rows holding a calendar.
        await page.locator('#cal').evaluate((node) => { node.style.overflow = 'visible'; });

        const first = await page.locator('#cal').boundingBox();
        const second = await page.locator('#resp').boundingBox();
        const field = await page.locator('#cal .field-calendar').boundingBox();
        expect(field.y + field.height).toBeLessThanOrEqual(first.y + first.height + 1);
        expect(second.y).toBeGreaterThanOrEqual(first.y + first.height);
        expect(Math.abs(second.width - first.width)).toBeLessThan(2);
        await expect(page.locator('#hidden')).toBeHidden();
        const input = await page.locator('#resp input').boundingBox();
        const button = await page.locator('#resp button').boundingBox();
        expect(button.x).toBeGreaterThanOrEqual(input.x + input.width);
        expect(button.y).toBeLessThan(input.y + input.height);
    });
}

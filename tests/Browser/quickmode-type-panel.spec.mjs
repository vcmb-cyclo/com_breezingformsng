import { test, expect } from '@playwright/test';

const option = (value, label, keywords = '') => `<option value="${value}" class="bfng-type-icon--${value}"
    data-keywords="${keywords}">${label}</option>`;
const MARKUP = `<!doctype html><html><head><meta charset="utf-8"></head><body><div id="bfQuickModeWrapper">
    <label for="bfElementType">Type</label>
    <span class="bfng-type-select" id="bfElementTypeIcon">
        <select id="bfElementType" data-bfng-type-panel data-search-label="Search a type" data-recent-label="Recent"
            data-empty-label="No type found.">
            <option value="" disabled selected>Choose</option>
            <optgroup label="Input">${option('text', 'Text', 'mail name')}${option('phone', 'Phone')}${option('date', 'Date')}</optgroup>
            <optgroup label="Choice">${option('radio', 'Radio group', 'single choice')}${option('select', 'Select')}</optgroup>
            <optgroup label="Payment">${option('stripe', 'Stripe', 'card cb')}</optgroup>
            <optgroup label="Actions">${option('submit', 'Submit button')}${option('summary', 'Récapitulatif')}</optgroup>
        </select>
    </span></div></body></html>`;

const open = async (page, width = 1200) => {
    await page.setViewportSize({ width, height: 700 });
    await page.route('http://panel.test/', (route) => route.fulfill({ contentType: 'text/html; charset=utf-8', body: MARKUP }));
    await page.goto('http://panel.test/');
    await page.addStyleTag({ path: 'media/com_breezingformsng/css/bfng-admin.css' });
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/admin/quickmode-type-menu.js' });
    await page.evaluate(() => {
        window.changes = [];
        document.getElementById('bfElementType').addEventListener('change', (event) => window.changes.push(event.target.value));
    });
};

const button = (page) => page.locator('#bfElementTypeButton');
const panel = (page) => page.locator('.bfng-type-panel');
const search = (page) => page.getByRole('combobox', { name: 'Search a type' });
const group = (page, name) => page.getByRole('group', { name });
const active = (page) => page.locator('.bfng-type-menu__option.is-active');

test('shows every family side by side, without the placeholder', async ({ page }) => {
    await open(page);
    await button(page).click();

    await expect(panel(page)).toBeVisible();
    await expect(search(page)).toBeFocused();
    await expect(group(page, 'Input').getByRole('option')).toHaveText(['Text', 'Phone', 'Date']);
    await expect(page.getByRole('option', { name: 'Choose' })).toHaveCount(0);
    await expect(group(page, 'Recent')).toHaveCount(0);

    const tops = await Promise.all(['Input', 'Choice', 'Payment', 'Actions']
        .map(async (name) => Math.round((await group(page, name).boundingBox()).y)));
    expect(new Set(tops).size).toBe(1);
});

test('stacks the columns and stays on a narrow screen', async ({ page }) => {
    await open(page, 375);
    await button(page).click();

    const box = await panel(page).boundingBox();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(375);
    expect(box.y + box.height).toBeLessThanOrEqual(700);
    expect((await group(page, 'Actions').boundingBox()).y).toBeGreaterThan((await group(page, 'Input').boundingBox()).y);
    await page.getByRole('option', { name: 'Submit button' }).click();
    await expect(button(page)).toHaveText('Submit button');
});

test('searches names and synonyms, whatever the accents', async ({ page }) => {
    await open(page);
    await button(page).click();

    await search(page).fill('cb');
    await expect(page.getByRole('option')).toHaveText(['Stripe']);
    await expect(group(page, 'Input')).toHaveCount(0);
    await search(page).fill('recap');
    await expect(page.getByRole('option')).toHaveText(['Récapitulatif']);
    await search(page).fill('zzz');
    await expect(page.getByRole('option')).toHaveCount(0);
    await expect(page.getByText('No type found.')).toBeVisible();
    await page.keyboard.press('Enter');
    expect(await page.evaluate(() => window.changes)).toEqual([]);
});

test('works with the keyboard', async ({ page }) => {
    await open(page);
    await button(page).focus();
    await page.keyboard.press('ArrowDown');
    await expect(active(page)).toHaveText('Text');
    await page.keyboard.press('ArrowDown');
    await expect(active(page)).toHaveText('Phone');
    await page.keyboard.press('ArrowRight');
    await expect(active(page)).toHaveText('Select');
    await page.keyboard.press('ArrowLeft');
    await expect(active(page)).toHaveText('Phone');
    await expect(search(page)).toHaveAttribute('aria-activedescendant', await active(page).getAttribute('id'));
    await page.keyboard.press('Escape');
    await expect(panel(page)).toBeHidden();
    await expect(button(page)).toBeFocused();

    await page.keyboard.press('Enter');
    await page.keyboard.type('radio');
    await page.keyboard.press('Enter');
    await expect(button(page)).toBeFocused();
    await expect(button(page)).toHaveText('Radio group');
    expect(await page.evaluate(() => window.changes)).toEqual(['radio']);

    await page.keyboard.press('Enter');
    await page.keyboard.press('Tab');
    await expect(panel(page)).toBeHidden();
});

test('keeps the three last choices at hand', async ({ page }) => {
    await open(page);

    for (const name of ['Text', 'Phone', 'Stripe', 'Select', 'Phone']) {
        await button(page).click();
        await group(page, 'Input').or(group(page, 'Choice')).or(group(page, 'Payment'))
            .getByRole('option', { name, exact: true }).click();
    }

    await button(page).click();
    await expect(group(page, 'Recent').getByRole('option')).toHaveText(['Phone', 'Select', 'Stripe']);
    await search(page).fill('te');
    await expect(group(page, 'Recent')).toHaveCount(0);
});

test('reads the select again at each opening and closes outside', async ({ page }) => {
    await open(page);
    await page.evaluate(() => {
        document.querySelector('option[value="stripe"]').disabled = true;
    });
    await button(page).click();
    await expect(page.getByRole('option', { name: 'Stripe' })).toHaveAttribute('aria-disabled', 'true');
    await page.mouse.click(1100, 690);
    await expect(panel(page)).toBeHidden();
});

import { test, expect } from '@playwright/test';

const MARKUP = `
    <label for="bfElementType">Type</label>
    <span class="bfng-type-select" id="bfElementTypeIcon">
        <select id="bfElementType">
            <option value="" disabled selected>Choose</option>
            <optgroup label="Input">
                <option value="bfElementTypeText" class="bfng-type-icon--bfElementTypeText">Text</option>
                <option value="bfElementTypeFile" class="bfng-type-icon--bfElementTypeFile">File</option>
            </optgroup>
            <optgroup label="Choice">
                <option value="bfElementTypeSelect" class="bfng-type-icon--bfElementTypeSelect">Select</option>
            </optgroup>
        </select>
    </span>
    <button id="after">After</button>
    <script>
        window.changes = [];
        document.getElementById('bfElementType').addEventListener('change', function (event) {
            window.changes.push(event.target.value);
        });
    </script>`;

test.describe('quickmode-type-menu.js', () => {
    test.beforeEach(async ({ page }) => {
        await page.setContent(MARKUP);
        await page.addScriptTag({ path: 'media/com_breezingformsng/js/admin/quickmode-type-menu.js' });
    });

    const button = (page) => page.locator('#bfElementTypeButton');
    const list = (page) => page.locator('#bfElementTypeList');
    const activeText = (page) => page.locator('.bfng-type-menu__option.is-active');

    test('replaces the select and keeps the families and the disabled placeholder', async ({ page }) => {
        await expect(page.locator('#bfElementType')).toBeHidden();
        await expect(button(page)).toHaveText('Choose');
        await expect(button(page)).toHaveAttribute('aria-expanded', 'false');

        await button(page).click();

        await expect(list(page)).toBeVisible();
        await expect(page.getByRole('group', { name: 'Input' }).getByRole('option')).toHaveText(['Text', 'File']);
        await expect(page.getByRole('option', { name: 'Choose' })).toHaveAttribute('aria-disabled', 'true');
    });

    test('selects with the mouse and fires the change the editor listens to', async ({ page }) => {
        await button(page).click();
        await page.getByRole('option', { name: 'File' }).click();

        await expect(list(page)).toBeHidden();
        await expect(button(page)).toHaveText('File');
        await expect(page.locator('#bfElementTypeIcon')).toHaveClass(/bfng-type-icon--bfElementTypeFile/);
        expect(await page.evaluate(() => window.changes)).toEqual(['bfElementTypeFile']);
    });

    test('cannot select the placeholder', async ({ page }) => {
        await button(page).click();
        await page.getByRole('option', { name: 'Choose' }).click({ force: true });

        await expect(list(page)).toBeVisible();
        expect(await page.evaluate(() => window.changes)).toEqual([]);
    });

    test('supports the whole keyboard', async ({ page }) => {
        await button(page).focus();
        await page.keyboard.press('ArrowDown');
        await expect(list(page)).toBeVisible();
        await expect(activeText(page)).toHaveText('Text');

        await page.keyboard.press('ArrowUp');
        await expect(activeText(page)).toHaveText('Text');
        await page.keyboard.press('End');
        await expect(activeText(page)).toHaveText('Select');
        await page.keyboard.press('Home');
        await expect(activeText(page)).toHaveText('Text');
        await page.keyboard.press('ArrowDown');
        await expect(activeText(page)).toHaveText('File');
        await expect(button(page)).toHaveAttribute('aria-activedescendant', await activeText(page).getAttribute('id'));

        await page.keyboard.press('Escape');
        await expect(list(page)).toBeHidden();
        expect(await page.evaluate(() => window.changes)).toEqual([]);

        await page.keyboard.press('Enter');
        await page.keyboard.type('sel');
        await expect(activeText(page)).toHaveText('Select');
        await page.keyboard.press('Enter');
        await expect(button(page)).toBeFocused();
        expect(await page.evaluate(() => window.changes)).toEqual(['bfElementTypeSelect']);

        await page.keyboard.press('Enter');
        await page.keyboard.press('Tab');
        await expect(list(page)).toBeHidden();
    });

    test('follows a value set from a script without firing change', async ({ page }) => {
        await page.evaluate(() => {
            const select = document.getElementById('bfElementType');
            select.value = 'bfElementTypeText';
            select.dispatchEvent(new CustomEvent('bfng:type-sync'));
        });

        await expect(button(page)).toHaveText('Text');
        await expect(page.locator('#bfElementTypeIcon')).toHaveClass(/bfng-type-icon--bfElementTypeText/);
        expect(await page.evaluate(() => window.changes)).toEqual([]);
    });

    test('stays inside the viewport and is not clipped by a container', async ({ page }) => {
        await page.evaluate(() => {
            const parent = document.getElementById('bfElementTypeIcon').parentElement;
            parent.style.cssText = 'overflow:hidden;height:60px;margin-top:500px';
            document.documentElement.style.height = '100%';
        });
        await button(page).click();

        const box = await list(page).boundingBox();
        const viewport = page.viewportSize();
        expect(box.y).toBeGreaterThanOrEqual(0);
        expect(box.y + box.height).toBeLessThanOrEqual(viewport.height);
        await page.getByRole('option', { name: 'Select' }).click();
        await expect(button(page)).toHaveText('Select');
    });

    test('closes when the page scrolls', async ({ page }) => {
        await page.evaluate(() => { document.body.style.height = '3000px'; });
        await button(page).click();
        await page.evaluate(() => window.scrollBy(0, 200));

        await expect(list(page)).toBeHidden();
    });

    test('closes on an outside click', async ({ page }) => {
        await button(page).click();
        await page.mouse.click(600, 500);

        await expect(list(page)).toBeHidden();
    });
});

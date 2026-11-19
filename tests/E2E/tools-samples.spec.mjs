import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './login.mjs';

// The "Sample data" card of the Tools screen: the sample forms are ticked, then installed together.
// It keeps the way Joomla lists work: the "check all" box of Joomla, the row-selected highlight of Atum,
// a click on a row to tick it, and a button that stays off until something is ticked.

const ADMIN = 'administrator/index.php';

test.describe('sample data of the Tools screen', () => {
    let page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await loginAsAdmin(page);
        await page.goto(`${ADMIN}?option=com_breezingformsng&view=packages`);
    });

    test.afterAll(async () => {
        await page.close();
    });

    const boxes = () => page.locator('#bf-tools-sample-form input[name="samples[]"]:not(:disabled)');
    const button = () => page.locator('#bf-tools-sample-install');

    test('nothing is ticked at first: the button is off and shows no number', async () => {
        const total = await boxes().count();
        expect(total).toBeGreaterThan(1);

        for (let i = 0; i < total; i++) {
            await expect(boxes().nth(i)).not.toBeChecked();
        }
        await expect(button()).toBeDisabled();
        await expect(page.locator('#bf-tools-sample-count')).toHaveText('');
        // A disabled button says what to do.
        await expect(page.locator('#bf-tools-sample-summary')).toHaveText(/\S/);
        await expect(page.locator('#bf-tools-sample-summary')).toHaveAttribute('aria-live', 'polite');
    });

    test('ticking a box turns the button on, shows the number and highlights the row', async () => {
        await boxes().first().check();

        await expect(button()).toBeEnabled();
        await expect(page.locator('#bf-tools-sample-count')).toHaveText('(1)');
        await expect(page.locator('#bf-tools-sample-summary')).toContainText('1');
        await expect(page.locator('#bf-tools-sample-form [data-sample].row-selected')).toHaveCount(1);

        await boxes().first().uncheck();
        await expect(button()).toBeDisabled();
        await expect(page.locator('#bf-tools-sample-form [data-sample].row-selected')).toHaveCount(0);
    });

    test('a click on the row, outside its box, ticks it', async () => {
        const row = page.locator('#bf-tools-sample-form [data-sample]:has(input[name="samples[]"]:not(:disabled))').first();
        const box = await row.boundingBox();
        expect(box).not.toBeNull();

        // The far right of the row is neither the box, the label nor a link.
        await page.mouse.click(box.x + box.width - 4, box.y + 4);

        await expect(row.locator('input[name="samples[]"]')).toBeChecked();
        await expect(row).toHaveClass(/row-selected/);

        await row.locator('input[name="samples[]"]').uncheck();
    });

    test('the check all box of Joomla ticks every sample that can be installed, and untick them', async () => {
        const checkAll = page.locator('#bf-tools-sample-all');
        await expect(checkAll).toBeVisible();
        await expect(page.locator('label[for="bf-tools-sample-all"]')).toHaveText(/\S/);

        const total = await boxes().count();
        await checkAll.check();

        await expect(page.locator('#bf-tools-sample-count')).toHaveText(`(${total})`);
        await expect(page.locator('#bf-tools-sample-summary')).toContainText(String(total));
        for (let i = 0; i < total; i++) {
            await expect(boxes().nth(i)).toBeChecked();
        }

        await checkAll.uncheck();
        await expect(button()).toBeDisabled();
        for (let i = 0; i < total; i++) {
            await expect(boxes().nth(i)).not.toBeChecked();
        }
    });

    test('the check all box shows a partial choice when only some are ticked', async () => {
        await boxes().first().check();

        const state = await page.locator('#bf-tools-sample-all').evaluate((input) => ({
            checked: input.checked,
            indeterminate: input.indeterminate,
        }));
        expect(state).toEqual({ checked: false, indeterminate: true });

        await boxes().first().uncheck();
    });
});

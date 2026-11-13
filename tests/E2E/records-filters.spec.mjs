import { test, expect } from '@playwright/test';

// The list screens show the search, the filters, Clear and the page size on one
// line, with no "Filter Options" button.

const ADMIN = 'administrator/index.php';
const VIEWS = ['records', 'forms', 'integrator', 'menus', 'pieces', 'scripts'];

test('the filters of the list screens share one line with the search', async ({ browser }) => {
    const page = await browser.newPage({ viewport: { width: 1500, height: 900 } });
    await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
    await page.goto(ADMIN);
    await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
    await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
    await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);

    const centre = async (locator) => {
        const box = await locator.boundingBox();
        expect(box).not.toBeNull();

        return box.y + box.height / 2;
    };

    for (const view of VIEWS) {
        await page.goto(`${ADMIN}?option=com_breezingformsng&view=${view}`);
        const stools = page.locator('.bf-list-filters .js-stools');
        await expect(stools, view).toBeVisible();

        if (process.env.BFNG_SCREENSHOT_DIR) {
            await stools.screenshot({ path: `${process.env.BFNG_SCREENSHOT_DIR}/${view}.png` });
        }

        await expect(page.locator('.js-stools-btn-filter'), view).toBeHidden();

        const bar = [
            stools.locator('#filter_search'),
            stools.locator('.js-stools-field-filter select, .js-stools-field-filter input').first(),
            stools.locator('.js-stools-btn-clear'),
            stools.locator('#list_limit'),
        ];
        const rows = await Promise.all(bar.map(centre));
        for (const row of rows) {
            expect(Math.abs(row - rows[0]), view).toBeLessThan(12);
        }
    }

    // Choosing a status still filters the records: the form is sent, the choice is kept.
    await page.goto(`${ADMIN}?option=com_breezingformsng&view=records`);
    await Promise.all([page.waitForNavigation(), page.locator('#filter_state').selectOption('*')]);
    await expect(page.locator('#filter_state')).toHaveValue('*');
    await page.close();
});

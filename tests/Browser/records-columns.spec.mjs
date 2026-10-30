import { test, expect } from '@playwright/test';

const KEY = 'joomla-tablecolumns-bfng-records';

test.describe('records-columns.js', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('http://bfng.test/', (route) => route.fulfill({ contentType: 'text/html', body: '<!DOCTYPE html>' }));
        await page.goto('http://bfng.test/');
    });

    const load = (page) => page.addScriptTag({ path: 'media/com_breezingformsng/js/admin/records-columns.js' });
    const stored = (page) => page.evaluate((key) => window.localStorage.getItem(key), KEY);

    test('hides Viewed and Exported on a first visit', async ({ page }) => {
        await load(page);
        expect(await stored(page)).toBe('5,6');
    });

    test('keeps the choice of the user, even when every column is shown', async ({ page }) => {
        for (const choice of ['', '3']) {
            await page.evaluate(([key, value]) => window.localStorage.setItem(key, value), [KEY, choice]);
            await load(page);
            expect(await stored(page)).toBe(choice);
        }
    });
});

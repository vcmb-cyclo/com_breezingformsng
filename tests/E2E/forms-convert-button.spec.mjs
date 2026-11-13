import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

// The Convert to QuickMode button only makes sense for a form that is not in
// QuickMode format: it shows when the list holds one.

const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const ADMIN = 'administrator/index.php';

const db = (...args) => execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();

test('the Convert to QuickMode button shows only when the list holds a form that is not in QuickMode format', async ({ browser }) => {
    const quick = Number(db('sample-form', 'convert_button_quick'));
    const legacy = Number(db('sample-form', 'convert_button_legacy'));
    db('set', String(quick), 'template_code_processed', 'QuickMode');
    db('set', String(legacy), 'template_code_processed', '');

    const page = await browser.newPage();
    await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
    await page.goto(ADMIN);
    await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
    await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
    await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);

    const button = page.locator('joomla-toolbar-button[task="forms.convertLegacy"]');
    const list = (search) => page.goto(`${ADMIN}?option=com_breezingformsng&view=forms&filter[search]=${search}`);

    await list('convert_button_quick');
    await expect(page.getByText('Sample convert_button_quick').first()).toBeVisible();
    await expect(button).toHaveCount(0);

    await list('convert_button_legacy');
    await expect(page.getByText('Sample convert_button_legacy').first()).toBeVisible();
    await expect(button).toHaveCount(1);

    await list('convert_button_');
    await expect(button).toHaveCount(1);
    await page.close();
});

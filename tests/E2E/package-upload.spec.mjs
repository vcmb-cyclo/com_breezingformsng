import { test, expect } from '@playwright/test';

const ADMIN = 'administrator/index.php';

test.beforeEach(async ({ page }) => {
    await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
    await page.goto(ADMIN);
    await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
    await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
    await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);
    await page.goto(`${ADMIN}?option=com_breezingformsng&view=packages&layout=import`);
});

test('an absent package prompts for a file', async ({ page }) => {
    await Promise.all([
        page.waitForNavigation(),
        page.locator('#bf-package-import').evaluate((form) => form.submit()),
    ]);
    await expect(page.locator('joomla-alert[type="danger"], .alert-danger'))
        .toContainText('Select a BFNG package file to import.');
});

test('a file rejected by the form size limit is identified as too large', async ({ page }) => {
    await page.locator('#bf-package-file').evaluate((input) => {
        const limit = document.createElement('input');
        limit.type = 'hidden';
        limit.name = 'MAX_FILE_SIZE';
        limit.value = '10';
        input.before(limit);
    });
    await page.setInputFiles('#bf-package-file', {
        name: 'large.json',
        mimeType: 'application/json',
        buffer: Buffer.from('x'.repeat(100)),
    });
    await Promise.all([
        page.waitForNavigation(),
        page.locator('#bf-package-import').evaluate((form) => form.submit()),
    ]);
    await expect(page.locator('joomla-alert[type="danger"], .alert-danger'))
        .toContainText('The file exceeds the allowed upload size.');
});

test('the import confirmation safely handles cancel and confirm', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    const filename = 'invalid-<img src=x>.json';
    await page.setInputFiles('#bf-package-file', {
        name: filename,
        mimeType: 'application/json',
        buffer: Buffer.from('{'),
    });

    const dialog = page.locator('joomla-dialog.joomla-dialog-confirm');
    await page.locator('#bf-package-import').evaluate((form) => form.requestSubmit());
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText(filename);
    await expect(dialog.locator('img')).toHaveCount(0);
    await dialog.locator('[data-button-cancel]').click();
    await expect(dialog).toHaveCount(0);
    await expect(page.locator('#bf-package-import')).toBeVisible();

    await page.locator('#bf-package-import').evaluate((form) => form.requestSubmit());
    await expect(dialog).toBeVisible();
    await Promise.all([
        page.waitForNavigation(),
        dialog.locator('[data-button-ok]').click(),
    ]);
    await expect(page.locator('joomla-alert[type="danger"], .alert-danger'))
        .toContainText(`The file '${filename}' does not contain valid JSON.`);
    expect(errors).toEqual([]);
});

import { test } from '@playwright/test';
test('dbg', async ({ page }) => {
    page.on('console', (m) => console.log('CONSOLE', m.type(), m.text()));
    page.on('pageerror', (e) => console.log('PAGEERROR', e.message));
    page.on('response', (r) => { if (/breezingforms/.test(r.url()) && r.request().method() === 'POST') console.log('RESP', r.status(), r.url()); });
    await page.goto('administrator/index.php');
    await page.fill('#mod-login-username', 'journeyadmin');
    await page.fill('#mod-login-password', 'E2e-Test-123!');
    await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);
    await page.goto('administrator/index.php?option=com_breezingformsng&view=packages&layout=import');
    await page.setInputFiles('#bf-package-file', { name: 'invalid.json', mimeType: 'application/json', buffer: Buffer.from('{') });
    await page.locator('#bf-package-import').evaluate((form) => form.requestSubmit());
    await page.waitForTimeout(3000);
    console.log('DIALOGS', await page.locator('joomla-dialog').count(), page.url());
    console.log('ALERTS', await page.locator('joomla-alert, .alert').allInnerTexts());
});

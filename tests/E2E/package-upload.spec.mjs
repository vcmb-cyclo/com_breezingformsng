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

    // <joomla-dialog> has no size of its own: its native <dialog> is shown in
    // the top layer, so visibility is checked on that inner element.
    const dialog = page.locator('joomla-dialog.joomla-dialog-confirm dialog');
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

test('only the checked items are imported, then listed with a link', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    const script = (name) => ({
        source_id: name === 'e2e_keep' ? 1 : 2,
        data: { published: 1, name, title: `Title <b>${name}</b>`, description: '', type: 'Untyped', code: '// e2e' },
    });
    const packageFile = {
        name: 'selection.json',
        mimeType: 'application/json',
        buffer: Buffer.from(JSON.stringify({
            format: 'breezingformsng-package',
            version: 2,
            package: 'e2e_selection',
            scripts: [script('e2e_keep'), script('e2e_leave')],
            pieces: [],
            menus: [],
            forms: [],
        })),
    };
    await page.setInputFiles('#bf-package-file', packageFile);

    const selection = page.locator('#bf-package-selection');
    await expect(selection).toBeVisible();
    await expect(selection.locator('tbody tr')).toHaveCount(2);
    // Names from the file are text, not markup.
    await expect(selection.locator('tbody b')).toHaveCount(0);
    await selection.locator('input[name="import_select[scripts][]"][value="1"]').uncheck();

    const dialog = page.locator('joomla-dialog.joomla-dialog-confirm dialog');
    await page.locator('#bf-package-import').evaluate((form) => form.requestSubmit());
    await expect(dialog).toContainText('0 form(s), 1 script(s)');
    await Promise.all([
        page.waitForNavigation(),
        dialog.locator('[data-button-ok]').click(),
    ]);

    const report = page.locator('#bf-package-import-report');
    await expect(report).toContainText('e2e_selection');
    await expect(report.locator('tbody tr')).toHaveCount(1);
    const link = report.locator('tbody a');
    await expect(link).toHaveText('Title <b>e2e_keep</b>');
    await expect(link).toHaveAttribute('href', /task=script\.edit&id=\d+/);
    await expect(report).not.toContainText('e2e_leave');
    // The report is shown once.
    await page.reload();
    await expect(page.locator('#bf-package-import-report')).toHaveCount(0);

    // Chosen again, the script already imported is shown as identical and cannot be checked.
    await page.setInputFiles('#bf-package-file', packageFile);
    const kept = page.locator('#bf-import-scripts-0');
    await expect(kept).toBeDisabled();
    await expect(kept).not.toBeChecked();
    await expect(page.locator('#bf-package-selection tbody tr').first()).toContainText('Identical');
    await expect(page.locator('#bf-import-scripts-1')).toBeEnabled();
    await expect(page.locator('#bf-import-scripts-1')).toBeChecked();
    await expect(page.locator('#bf-package-selection tbody tr').nth(1)).toContainText('New');
    expect(errors).toEqual([]);
});

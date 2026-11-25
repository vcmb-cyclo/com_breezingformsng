import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { runAuditAndOpen } from './audit.mjs';
import { loginAsAdmin } from './login.mjs';

// The Extensions screen of the About page: every BreezingForms extension (the former BreezingForms
// too), the identifier first, the description of the extension itself, and an uninstall button.

const ADMIN = 'administrator/index.php';
const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const db = (...args) => execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();

test('the extensions screen lists the whole family, with ID first, and uninstalls one extension', async ({ browser }) => {
    const probe = Number(db('probe-plugin', 'breezingformsprobe'));
    const page = await browser.newPage();

    try {
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await loginAsAdmin(page);
        // The audit lists the leftovers of a former BreezingForms, with the link on their name; what the
        // BreezingForms NG package installs (its module, its content plugin) is never one of them.
        const csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        await runAuditAndOpen(page, csrf);
        await expect(page.locator('#bf-audit-section')).toBeVisible({ timeout: 30_000 });
        const legacy = page.locator('#adminForm li:has([data-bf-select-item="legacy-extensions"])');
        const probeLink = legacy.locator('a:has(code)', { hasText: 'plugin/system/breezingformsprobe' });
        await expect(probeLink).toHaveAttribute('href', new RegExp(`option=com_plugins.*id:${probe}`));
        await expect(legacy.filter({ hasText: 'module/mod_breezingformsng' })).toHaveCount(0);
        await expect(legacy.filter({ hasText: 'plugin/content/breezingformsng' })).toHaveCount(0);
        await expect(legacy.locator('a.btn')).toHaveCount(0);

        await page.goto(`${ADMIN}?option=com_breezingformsng&task=about.display&view=about&layout=extensions`);

        // The title says where the screen is: About, then Extensions.
        await expect(page.locator('.page-title, h1').first()).toContainText(/About.*Extensions/s);

        // The button leads to the Extensions Manager of Joomla, on the BreezingForms extensions.
        const manage = page.locator('#toolbar-about_manage_plugins a, joomla-toolbar-button[id="toolbar-about_manage_plugins"] a, a:has-text("Manage extensions")').first();
        await expect(manage).toHaveAttribute('href', /option=com_installer&view=manage.*breezingforms/);

        const table = page.locator('#bf-extensions-table');
        await expect(table).toBeVisible();

        // The identifier is the first column.
        await expect(table.locator('thead th').first()).toHaveText(/ID/);
        const row = table.locator('tbody tr', { has: page.locator(`button[value="${probe}"]`) });
        await expect(row.locator('td').first()).toHaveText(String(probe));

        // The former BreezingForms is listed, with the description of its own manifest, and the component
        // and the module of BreezingForms NG are listed too.
        await expect(row).toContainText('Probe plugin of the former BreezingForms.');
        await expect(table.locator('tbody')).toContainText('com_breezingformsng');
        await expect(table.locator('tbody')).toContainText('mod_breezingforms');

        // The changelog XML declared by the manifest is linked; an extension without one has no link.
        const componentRow = table.locator('tbody tr', { hasText: 'com_breezingformsng' });
        await expect(componentRow.locator('a[href$="com_breezingformsng_changelog.xml"]')).toHaveCount(1);
        // The plugins and the module shipped with it declare the same one.
        expect(await table.locator('tbody a[href$="com_breezingformsng_changelog.xml"]').count()).toBeGreaterThanOrEqual(9);
        await expect(row.locator('a[href$=".xml"]')).toHaveCount(0);

        // The generic text that used to be repeated on every row is gone.
        await expect(table).not.toContainText('Used by BreezingForms when enabled.');

        // The running component cannot be uninstalled from here.
        await expect(table.locator('tbody tr', { hasText: 'com_breezingformsng' }).locator('button[name="extension_id"]')).toHaveCount(0);

        // A confirmation first, then the extension is uninstalled with its files.
        page.once('dialog', (dialog) => dialog.accept());
        await Promise.all([page.waitForNavigation(), row.locator('button[name="extension_id"]').click()]);

        expect(db('extension-exists', String(probe))).toBe('0');
        await expect(page.locator('#bf-extensions-table tbody')).not.toContainText('breezingformsprobe');
        await expect(page.locator('joomla-alert, .alert-success').first()).toBeVisible();
    } finally {
        if (db('extension-exists', String(probe)) === '1') {
            // The journey failed before the uninstall: leave no probe behind.
            db('extension-delete', String(probe));
        }
        await page.close();
    }
});

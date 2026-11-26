import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { runAuditAndOpen } from './audit.mjs';
import { loginAsAdmin } from './login.mjs';

// The Extensions screen of the About page: every BreezingForms extension (the former BreezingForms
// too), the identifier first, the description of the extension itself, and plugin state controls.

const ADMIN = 'administrator/index.php';
const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const db = (...args) => execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();

test('the extensions screen lists the BF family and lets admins toggle plugins', async ({ browser }) => {
    const probe = Number(db('probe-plugin', 'breezingformsprobe'));
    const page = await browser.newPage();
    let template;

    try {
        template = JSON.parse(db('probe-template'));
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
        await expect(page.locator('#bf-audit-section')).not.toContainText(template.element);

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
        await expect(table.locator('thead th')).toHaveCount(7);
        const row = table.locator('tbody tr', { has: page.locator(`button[data-item-form-id="${probe}"]`) });
        await expect(row.locator('td').first()).toHaveText(String(probe));

        // The former BreezingForms is listed, with the description of its own manifest, and the component
        // and the module of BreezingForms NG are listed too.
        await expect(row).toContainText('Probe plugin of the former BreezingForms.');
        await expect(row.locator('.bf-extension-description'))
            .toHaveAttribute('title', 'Probe plugin of the former BreezingForms.');
        await expect(table.locator('tbody')).toContainText('com_breezingformsng');
        await expect(table.locator('tbody')).toContainText('mod_breezingforms');
        await expect(table.locator('tbody')).not.toContainText(template.element);

        const componentRow = table.locator('tbody tr', { hasText: 'com_breezingformsng' });
        const versionButton = componentRow.locator('button[data-joomla-dialog]');
        const componentId = (await componentRow.locator('td').first().innerText()).trim();
        await expect(versionButton).toBeVisible();
        expect(await table.locator('tbody button[data-joomla-dialog]').count()).toBeGreaterThanOrEqual(9);
        await expect(row.locator('button[data-joomla-dialog]')).toHaveCount(0);

        // Exercise Joomla's AJAX dialog without depending on a published changelog for this candidate.
        await page.route((url) => url.searchParams.get('option') === 'com_installer'
            && url.searchParams.get('task') === 'manage.loadChangelogRaw'
            && url.searchParams.get('eid') === componentId
            && url.searchParams.get('source') === 'manage'
            && url.searchParams.get('format') === 'raw',
        (route) => route.fulfill({ contentType: 'text/html', body: '<p>Component changelog fixture</p>' }));
        await versionButton.click();
        const changelog = page.locator('joomla-dialog dialog[open]');
        await expect(changelog).toContainText('Component changelog fixture');
        await page.keyboard.press('Escape');
        await expect(changelog).toHaveCount(0);

        // The generic text that used to be repeated on every row is gone.
        await expect(table).not.toContainText('Used by BreezingForms when enabled.');

        await expect(table.locator('button[name="extension_id"]')).toHaveCount(0);
        const toggle = row.locator('button[data-item-task="about.setPluginState"]');
        await expect(toggle).toHaveAttribute('aria-pressed', 'true');
        await toggle.click();
        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await page.reload();
        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await toggle.click();
        await expect(toggle).toHaveAttribute('aria-pressed', 'true');
        expect(db('extension-exists', String(probe))).toBe('1');
        expect(db('extension-exists', String(template.id))).toBe('1');
    } finally {
        if (template) {
            db('extension-delete', String(template.id));
        }
        if (db('extension-exists', String(probe)) === '1') {
            db('extension-delete', String(probe));
        }
        await page.close();
    }
});

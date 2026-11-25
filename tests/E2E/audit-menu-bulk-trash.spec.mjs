import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { runAuditAndOpen } from './audit.mjs';
import { loginAsAdmin } from './login.mjs';

// The menu items of the site reported by the audit: the checked ones go to the trash together,
// the others stay where they are.

const ADMIN = 'administrator/index.php';
const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const PREFIX = 'E2E bulk menu';
const db = (...args) => execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();

test('the checked menu items of the audit go to the trash together', async ({ browser }) => {
    db('menu-delete', PREFIX);
    const [first, second, third] = JSON.parse(db('menu-fixture', PREFIX, '3'));
    const page = await browser.newPage();

    try {
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await loginAsAdmin(page);
        const csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        await runAuditAndOpen(page, csrf);

        const box = (id) => page.locator(`input[name="menu_items[]"][value="${id}"]`);
        await expect(box(first)).toBeVisible({ timeout: 30_000 });
        await box(first).check();
        await box(second).check();

        page.once('dialog', (dialog) => dialog.accept());
        await Promise.all([
            page.waitForNavigation(),
            page.getByRole('button', { name: /selected to the trash/i }).click(),
        ]);

        expect(db('menu-published', String(first))).toBe('-2');
        expect(db('menu-published', String(second))).toBe('-2');
        expect(db('menu-published', String(third))).toBe('1');
        await expect(page.locator('joomla-alert, .alert-success').first()).toBeVisible();
    } finally {
        db('menu-delete', PREFIX);
        await page.close();
    }
});

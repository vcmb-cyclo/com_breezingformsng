import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { runAuditAndOpen } from './audit.mjs';
import { loginAsAdmin } from './login.mjs';

// The date of the audit report is shown in the site's configured time zone, not in UTC.

const ADMIN = 'administrator/index.php';
const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const CONFIG = '/var/www/html/configuration.php';
const sh = (command) => execFileSync('docker', ['exec', CONTAINER, 'sh', '-c', command]).toString().trim();
const setSiteTimezone = (timezone) => {
    sh(`sed -i "s#public \\$offset = '[^']*'#public \\$offset = '${timezone}'#" ${CONFIG}`);
    sh('apachectl -k graceful');
};

test('the audit date is shown in local time', async ({ browser }) => {
    const original = sh(`sed -n "s/.*public \\$offset = '\\([^']*\\)'.*/\\1/p" ${CONFIG}`);
    const page = await browser.newPage();

    try {
        setSiteTimezone('Pacific/Auckland');
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await loginAsAdmin(page);
        const csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        await runAuditAndOpen(page, csrf);

        const text = await page.locator('#bf-audit-section .text-muted.small').first().innerText({ timeout: 30_000 });
        expect(text).not.toMatch(/UTC/);
        const shown = text.match(/(\d{4}-\d{2}-\d{2}) (\d{2}):(\d{2}):\d{2}/);
        expect(shown, text).not.toBeNull();

        // The same moment in Auckland, to the minute (the page was just built).
        const expected = new Intl.DateTimeFormat('sv-SE', {
            timeZone: 'Pacific/Auckland', hour12: false,
            year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit',
        }).format(new Date());
        const [expectedDay, expectedTime] = expected.split(' ');
        expect(shown[1]).toBe(expectedDay);
        const minutes = (value) => Number(value.slice(0, 2)) * 60 + Number(value.slice(3, 5));
        expect(Math.abs(minutes(`${shown[2]}:${shown[3]}`) - minutes(expectedTime))).toBeLessThanOrEqual(2);
    } finally {
        setSiteTimezone(original);
        await page.close();
    }
});

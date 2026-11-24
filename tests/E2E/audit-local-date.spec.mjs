import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { loginAsAdmin } from './login.mjs';

// The date of the audit report is shown in the time zone of the site (or of the user), not in UTC.

const ADMIN = 'administrator/index.php';
const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const CONFIG = '/var/www/html/configuration.php';
const sh = (command) => execFileSync('docker', ['exec', CONTAINER, 'sh', '-c', command]).toString().trim();

test('the audit date is shown in local time', async ({ browser }) => {
    const original = sh(`sed -n "s/.*public \\$offset = '\\([^']*\\)'.*/\\1/p" ${CONFIG}`);
    const page = await browser.newPage();

    try {
        sh(`sed -i "s#public \\$offset = '[^']*'#public \\$offset = 'Pacific/Auckland'#" ${CONFIG}`);
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await loginAsAdmin(page);
        const csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        const run = await page.request.post(`${ADMIN}?option=com_breezingformsng&task=about.runAudit`, {
            form: { [csrf]: '1' },
            maxRedirects: 0,
        });
        expect([302, 303]).toContain(run.status());
        await page.goto(`${ADMIN}?option=com_breezingformsng&task=about.display&view=about&layout=audit`);

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
        sh(`sed -i "s#public \\$offset = 'Pacific/Auckland'#public \\$offset = '${original}'#" ${CONFIG}`);
        await page.close();
    }
});

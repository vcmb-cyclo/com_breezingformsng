import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { loginAsAdmin } from './login.mjs';

// "Values without a record" of the database audit: the values of a record that no longer exists.
// They can be deleted from the audit screen; the values of a record that exists are never touched.

const ADMIN = 'administrator/index.php';
const MISSING_RECORD = 1999999999;
const db = (...args) => execFileSync('docker', ['exec', '-i', process.env.BFNG_E2E_CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();

test('the values of a missing record are deleted from the audit, and only those', async ({ browser }) => {
    const form = Number(db('sample-form', 'audit_record_values'));
    const record = Number(db('sample-record', String(form), 'email', 'values@example.invalid'));
    const other = Number(db('sample-record', String(form), 'email', 'values2@example.invalid'));
    const ids = JSON.parse(db('audit-orphan-fixture', String(record), String(other)));
    // The fixture rows: two of existing records, one of an existing record with a missing element,
    // and two (the last ones) of a record that does not exist.
    const orphans = ids.slice(3);
    const kept = ids.slice(0, 3);
    const rows = () => JSON.parse(db('audit-orphan-fixture-rows'));
    const page = await browser.newPage();

    try {
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await loginAsAdmin(page);
        const csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        const task = (name) => `${ADMIN}?option=com_breezingformsng&task=about.${name}&format=json`;

        const noToken = await page.request.post(task('deleteOrphanSubrecords'), { form: { orphan_element_id: String(MISSING_RECORD) } });
        expect(noToken.status()).toBe(403);
        expect(rows()).toEqual(expect.arrayContaining([...kept, ...orphans]));

        const invalid = await page.request.post(task('deleteOrphanSubrecords'), { form: { [csrf]: '1', orphan_element_id: '-1' } });
        expect(invalid.status()).toBe(400);
        const zero = await page.request.post(task('deleteOrphanSubrecords'), { form: { [csrf]: '1', orphan_element_id: '0' } });
        expect(zero.status()).toBe(400);
        expect(rows()).toEqual(expect.arrayContaining([...kept, ...orphans]));

        // The audit shows the values, with a trash button per missing record and one for all of them.
        const run = await page.request.post(`${ADMIN}?option=com_breezingformsng&task=about.runAudit`, {
            form: { [csrf]: '1' },
            maxRedirects: 0,
        });
        expect([302, 303]).toContain(run.status());
        await page.goto(`${ADMIN}?option=com_breezingformsng&task=about.display&view=about&layout=audit`);
        const audit = page.locator('[data-bf-orphan-check="subrecords_without_record"]');
        await expect(audit).toBeAttached({ timeout: 30_000 });
        const trash = audit.locator(`[data-bf-orphan-task="about.deleteOrphanSubrecords"][data-bf-orphan-element="${MISSING_RECORD}"]`);
        await expect(trash).toHaveCount(1);
        await expect(trash.locator('.icon-trash')).toHaveCount(1);
        await expect(audit.locator('#bf-audit-delete-all-orphan-record-values')).toHaveCount(1);

        // The trash button asks first, then deletes the values of that record only.
        page.once('dialog', (dialog) => dialog.accept());
        await trash.click();
        await expect.poll(() => rows().filter((id) => orphans.includes(id)).length, { timeout: 30_000 }).toBe(0);
        expect(rows()).toEqual(expect.arrayContaining(kept));
        await expect(page.locator('[data-bf-orphan-check="subrecords_without_record"]')).toHaveCount(0);
    } finally {
        // The rows are fixtures of this journey only: other journeys count the orphans of the site.
        db('subrecords-delete', ...ids.map(String));
        await page.close();
    }
});

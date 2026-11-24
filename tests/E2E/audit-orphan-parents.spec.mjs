import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { loginAsAdmin } from './login.mjs';

// "Records without a form" and "Elements without a form" of the audit: a trash button for one deleted form,
// a selection (with a select-all box) for several, and never an unnamed action. The elements are deleted; the
// records go to the trash of the records and are deleted from there, like any record.

const ADMIN = 'administrator/index.php';
const db = (...args) => execFileSync('docker', ['exec', '-i', process.env.BFNG_E2E_CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();

test('records and elements of deleted forms are deleted one by one or by selection', async ({ browser }) => {
    const form = Number(db('sample-form', 'audit_parents'));
    const fixture = JSON.parse(db('orphan-parents-fixture', String(form)));
    const [first, second] = fixture.forms;
    const counts = () => JSON.parse(db('orphan-parents-counts', String(fixture.kept)));
    const page = await browser.newPage();

    try {
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await loginAsAdmin(page);
        const csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        const task = (name) => `${ADMIN}?option=com_breezingformsng&task=about.${name}&format=json`;

        // Nothing is deleted when no form is named: a missing id is not "everything".
        for (const name of ['trashOrphanRecords', 'deleteOrphanElements']) {
            const unnamed = await page.request.post(task(name), { form: { [csrf]: '1' } });
            expect(unnamed.status(), name).toBe(400);
            const invalid = await page.request.post(task(name), { form: { [csrf]: '1', 'orphan_ids[]': 'x' } });
            expect(invalid.status(), name).toBe(400);
        }
        expect(counts()).toMatchObject({ elements: { [first]: 2, [second]: 2 }, records: { [first]: 1, [second]: 2 }, values: 3, kept: 1 });

        const run = await page.request.post(`${ADMIN}?option=com_breezingformsng&task=about.runAudit`, {
            form: { [csrf]: '1' },
            maxRedirects: 0,
        });
        expect([302, 303]).toContain(run.status());
        await page.goto(`${ADMIN}?option=com_breezingformsng&task=about.display&view=about&layout=audit`);

        // Elements: the trash button of one form removes only its elements.
        const elements = page.locator('[data-bf-orphan-check="elements_without_form"]');
        await expect(elements).toBeAttached({ timeout: 60_000 });
        page.once('dialog', (dialog) => dialog.accept());
        await elements.locator(`button[data-bf-orphan-task="about.deleteOrphanElements"][data-bf-orphan-element="${first}"]`).click();
        await expect.poll(() => counts().elements[first], { timeout: 30_000 }).toBe(0);
        expect(counts().elements[second]).toBe(2);

        // Records: the bulk button is off until a row is ticked, and the select-all box ticks every row.
        const records = page.locator('[data-bf-orphan-check="records_without_form"]');
        await expect(records).toBeAttached();
        const bulk = records.locator('button[data-bf-orphan-selection]');
        await expect(bulk).toBeDisabled();
        await records.locator('input[data-bf-orphan-select]').first().check();
        await expect(bulk).toBeEnabled();
        await records.locator('input[data-bf-orphan-select-all]').check();
        const ticked = await records.locator('input[data-bf-orphan-select]:checked').count();
        expect(ticked).toBe(await records.locator('input[data-bf-orphan-select]').count());
        await records.locator('input[data-bf-orphan-select-all]').uncheck();
        await expect(bulk).toBeDisabled();

        // Only the two forms of the journey are ticked: the site may hold other deleted forms.
        for (const id of fixture.forms) {
            await records.locator(`input[data-bf-orphan-select][value="${id}"]`).check();
        }
        await expect(bulk).toBeEnabled();
        page.once('dialog', (dialog) => dialog.accept());
        await bulk.click();

        // The records are in the trash, not deleted: they and their values are all still there.
        await expect.poll(() => counts().trashed[first] + counts().trashed[second], { timeout: 30_000 }).toBe(3);
        expect(counts()).toMatchObject({ records: { [first]: 1, [second]: 2 }, values: 3, kept: 1, keptState: 1 });

        // They are no longer listed as orphans: the trash of the records deals with them.
        await page.goto(`${ADMIN}?option=com_breezingformsng&task=about.display&view=about&layout=audit`);
        await expect(page.locator('#bf-audit-section')).toBeVisible({ timeout: 30_000 });
        await expect(page.locator(`[data-bf-orphan-check="records_without_form"] input[data-bf-orphan-select][value="${first}"]`)).toHaveCount(0);
        await expect(page.locator(`[data-bf-orphan-check="records_without_form"] input[data-bf-orphan-select][value="${second}"]`)).toHaveCount(0);
    } finally {
        db('orphan-parents-clean', String(fixture.kept));
        await page.close();
    }
});

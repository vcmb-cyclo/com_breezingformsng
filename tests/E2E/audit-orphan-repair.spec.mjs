import { test, expect, request } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const ADMIN = 'administrator/index.php';
const AUDIT = 'api/index.php/v1/breezingformsng/audit';
const db = (...args) => execFileSync('docker', ['exec', '-i', process.env.BFNG_E2E_CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();

test('orphan repairs preserve existing records and distinguish element zero from all', async ({ baseURL, browser }) => {
    const form1 = Number(db('sample-form', 'audit_orphan_first'));
    const form2 = Number(db('sample-form', 'audit_orphan_second'));
    db('set', String(form1), 'title', 'Zulu audit form');
    db('set', String(form2), 'title', 'Alpha audit form');
    const record1 = Number(db('sample-record', String(form1), 'email', 'one@example.invalid'));
    const record2 = Number(db('sample-record', String(form2), 'email', 'two@example.invalid'));
    const ids = JSON.parse(db('audit-orphan-fixture', String(record1), String(record2)));
    const rows = () => JSON.parse(db('audit-orphan-fixture-rows'));
    const token = db('api-token', process.env.BFNG_E2E_USER);
    const api = await request.newContext({ baseURL, extraHTTPHeaders: { Accept: 'application/json', Authorization: `Bearer ${token}` } });
    const page = await browser.newPage();

    try {
        const report = await api.get(AUDIT);
        expect(report.status()).toBe(200);
        const checks = (await report.json()).data.orphan_checks;
        const check = checks.find((entry) => entry.id === 'subrecords_without_element');
        const group = check.groups.find((entry) => entry.parent_id === 1999999990);
        expect(group).toMatchObject({ form_id: form1, form_title: 'Zulu audit form', forms: 2, count: 3, deletable: 1 });

        const zero = await api.delete(`${AUDIT}/orphan-values/0`);
        expect(zero.status()).toBe(200);
        expect((await zero.json()).data.deleted).toBe(1);
        expect(rows()).toEqual([ids[0], ids[1], ids[2], ids[4]]);

        const invalid = await api.delete(`${AUDIT}/orphan-values?element=-1`);
        expect(invalid.status()).toBe(400);
        expect(rows()).toEqual([ids[0], ids[1], ids[2], ids[4]]);

        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
        await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);
        const csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        const noToken = await page.request.post(`${ADMIN}?option=com_breezingformsng&task=about.deleteOrphanValues&format=json`, {
            form: { orphan_all: '1' },
        });
        expect(noToken.status()).toBe(403);
        expect(rows()).toEqual([ids[0], ids[1], ids[2], ids[4]]);

        await page.request.post(`${ADMIN}?option=com_breezingformsng&task=about.runAudit`, {
            form: { [csrf]: '1' },
            maxRedirects: 0,
        });
        await page.goto(`${ADMIN}?option=com_breezingformsng&task=about.display&view=about`);
        const audit = page.locator('[data-bf-orphan-check="subrecords_without_element"]');
        await expect(audit).toBeAttached();
        await expect(audit.locator('[data-bf-orphan-element="1999999990"]')).toHaveCount(0);
        await expect(audit.locator('#bf-audit-delete-all-orphan-values')).toHaveCount(0);

        const denied = await page.request.post(`${ADMIN}?option=com_breezingformsng&task=about.deleteOrphanValues&format=json`, {
            form: { [csrf]: '1', orphan_element_id: '-1' },
        });
        expect(denied.status()).toBe(500);
        expect(rows()).toEqual([ids[0], ids[1], ids[2], ids[4]]);

        const all = await api.delete(`${AUDIT}/orphan-values`);
        expect(all.status()).toBe(200);
        expect(rows()).toEqual([ids[0], ids[1], ids[2]]);
    } finally {
        await page.close();
        await api.dispose();
    }
});

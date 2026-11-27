import { test, expect, request } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { loginAsAdmin } from './login.mjs';

// A submission with the add-on plugins (Dropbox, Mailchimp, Salesforce) enabled.
// They listen to the events sent after a submission: a defect in those events
// only shows once a plugin is enabled, so every other journey would not see it
// (RC23/RC24: the event getters called themselves until PHP ran out of stack).

const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const SITE = 'index.php?option=com_breezingformsng';
const ADDONS = ['dropbox', 'mailchimp', 'salesforce'];
const TOKEN = /name="([0-9a-f]{32})" value="1"/;

const db = (...args) => execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();
const enabled = (element) => JSON.parse(db('plugin', 'breezingforms_addons', element)).enabled;

// The sample form of the Tools screen is a real, renderable form: it is installed as an administrator does.
const installWidgetsSample = async (browser) => {
    const page = await browser.newPage();
    try {
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto('administrator/index.php');
        await loginAsAdmin(page);
        await page.goto('administrator/index.php?option=com_breezingformsng&view=packages');
        page.on('dialog', (dialog) => dialog.accept());
        await page.locator('#bf-tools-sample-title button').click();
        await page.locator('#bf-tools-sample-form input[name="samples[]"][value="widgets"]').check();
        await Promise.all([page.waitForNavigation(), page.locator('#bf-tools-sample-install').click()]);
    } finally {
        await page.close();
    }
    const form = JSON.parse(db('package-form', 'Sample'));
    expect(form, 'the sample form is installed').toBeTruthy();

    return Number(form.id);
};

test.describe.configure({ mode: 'serial' });

test.describe('a submission with the add-on plugins enabled', () => {
    let api;
    let formId;
    let before;

    test.beforeAll(async ({ baseURL, browser }) => {
        before = Object.fromEntries(ADDONS.map((element) => [element, enabled(element)]));
        for (const element of ADDONS) {
            db('plugin', 'breezingforms_addons', element, '1');
        }
        formId = await installWidgetsSample(browser);
        // Dropbox reads the event only when its option is on; without an access token it uploads nothing.
        db('set', String(formId), 'dropbox_submission_enabled', '1');
        api = await request.newContext({ baseURL });
    });

    test.afterAll(async () => {
        await api?.dispose();
        db('set', String(formId), 'dropbox_submission_enabled', '0');
        for (const element of ADDONS) {
            db('plugin', 'breezingforms_addons', element, before[element]);
        }
    });

    test('the plugins are enabled for this journey', () => {
        for (const element of ADDONS) {
            expect(enabled(element), element).toBe('1');
        }
    });

    test('the form is submitted and stored, with no recursion in the events', async () => {
        const html = await (await api.get(`${SITE}&ff_form=${formId}`)).text();
        const form = html.match(new RegExp(`<form[^>]*name="ff_form${formId}"[^>]*>`))?.[0];
        expect(form, 'the form is on the page').toBeTruthy();
        const token = html.match(TOKEN)?.[1];
        expect(token).toBeTruthy();
        const before = Number(db('records-count', String(formId)));

        const response = await api.post(form.match(/action="([^"]+)"/)[1], {
            form: { ff_task: 'submit', ff_form: String(formId), 'ff_nm_Name[]': 'Ada', [token]: '1' },
        });
        const body = await response.text();

        expect(response.status()).toBe(200);
        expect(body).not.toMatch(/Maximum call stack|Infinite recursion|Allowed memory size/);
        // The sample form notifies the administrator and no address is configured: nothing is mailed to a
        // placeholder, so the submission does not end on a failed mail (status 5).
        expect(body).not.toMatch(/Invalid address|name="ff_status" value="5"/);
        expect(Number(db('records-count', String(formId)))).toBe(before + 1);
    });
});

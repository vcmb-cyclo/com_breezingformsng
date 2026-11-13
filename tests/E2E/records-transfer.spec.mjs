import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

// The transfer of records to another form, through its four screens, and what
// it wrote, read back from the database of the site.

const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const ADMIN = 'administrator/index.php';
const TRASHED = '-2';

const db = (...args) => execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();
const dbJson = (...args) => JSON.parse(db(...args));

test.describe.configure({ mode: 'serial' });

test.describe('records transfer', () => {
    let page;
    let source;
    let target;
    let records;
    const problems = [];

    test.beforeAll(async ({ browser }) => {
        source = Number(db('sample-form', 'xfer_source'));
        target = Number(db('sample-form', 'xfer_target'));
        db('add-element', String(target), 'city', 'City');
        records = ['a@example.invalid', 'b@example.invalid', 'c@example.invalid']
            .map((email) => Number(db('sample-record', String(source), 'email', email)));

        page = await browser.newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        page.on('pageerror', (error) => {
            if (!/Transition was aborted/.test(error.message)) {
                problems.push(`JS: ${error.message}`);
            }
        });
        await page.goto(ADMIN);
        await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
        await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);
    });

    test.afterEach(() => {
        expect(problems.splice(0)).toEqual([]);
    });

    test.afterAll(async () => {
        await page.close();
    });

    test('a transfer without the token of the page is refused and copies nothing', async () => {
        const before = Number(db('records-count', String(target)));
        const answer = await page.request.post(`${ADMIN}?option=com_breezingformsng&task=recordstransfer.transfer`, {
            form: { trash: '1' },
            maxRedirects: 0,
        });

        expect(answer.status()).toBeLessThan(400);
        expect(Number(db('records-count', String(target)))).toBe(before);
        expect(dbJson('record', String(records[0])).state).toBe('1');
    });

    test('a screen opened without a transfer in progress goes back to the list', async () => {
        await page.goto(`${ADMIN}?option=com_breezingformsng&view=recordstransfer&layout=confirm`);

        await expect(page).toHaveURL(/view=records/);
    });

    test('the transfer maps the fields, applies the default value and trashes the originals', async () => {
        await page.goto(`${ADMIN}?option=com_breezingformsng&view=records&filter[form]=${source}`);
        for (const id of records) {
            await page.locator(`input[name="cid[]"][value="${id}"]`).check();
        }
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-transfer button')]);

        // 1. Records: all checked, one is given up, a target form is chosen.
        await expect(page).toHaveURL(/layout=select/);
        const box = (id) => page.locator(`input[name="cid[]"][value="${id}"]`);
        await expect(page.locator('input[name="cid[]"]')).toHaveCount(3);
        for (const id of records) {
            await expect(box(id)).toBeChecked();
        }
        await box(records[2]).uncheck();
        await page.selectOption('#transfer-target', String(target));
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-next button')]);

        // 2. Mapping: the fields with the same name are matched, the other gets a default.
        await expect(page).toHaveURL(/layout=mapping/);
        const maps = page.locator('select[name^="map["]');
        await expect(maps).toHaveCount(2);
        await expect(maps.nth(0).locator('option:checked')).toContainText('Email');
        await expect(maps.nth(1)).toHaveValue('0');
        // The fields carry the icon of their type, as in the tree of the form screen: two target fields and the chosen source field.
        await expect(page.locator('#transferMapping tbody img[src*="icon_text-field.png"]')).toHaveCount(3);
        // The list of source fields carries the icons, and shows the one of the chosen field beside it.
        await expect(page.locator('select[name^="map["]').first().locator('option[class*="bf-icon-"]')).toHaveCount(1);
        await expect(page.locator('select[name^="map["]').first().locator('xpath=preceding-sibling::img')).toBeVisible();
        await expect(page.locator('select[name^="map["]').nth(1).locator('xpath=preceding-sibling::img')).toBeHidden();
        // The names of the two forms open their editor in a new tab.
        const formLinks = page.locator('a[target="_blank"][href*="quickmode.display"]');
        await expect(formLinks).toHaveCount(2);
        await expect(formLinks.first()).toHaveAttribute('rel', /noopener/);
        // The row of an assigned field is colored, and follows the choice of the source field.
        const rows = page.locator('#transferMapping tbody tr');
        await expect(rows.nth(0)).toHaveClass(/bf-transfer-assigned/);
        await expect(rows.nth(1)).not.toHaveClass(/bf-transfer-assigned/);
        const emailSource = await maps.nth(0).inputValue();
        await maps.nth(0).selectOption('0');
        await expect(rows.nth(0)).not.toHaveClass(/bf-transfer-assigned/);
        await expect(page.locator('input[name^="defaults["]').nth(0)).toBeEnabled();
        await maps.nth(0).selectOption(emailSource);
        await expect(rows.nth(0)).toHaveClass(/bf-transfer-assigned/);
        // A default value is for a field that no source field fills.
        await expect(page.locator('input[name^="defaults["]').nth(0)).toBeDisabled();
        await expect(page.locator('input[name^="defaults["]').nth(1)).toBeEnabled();
        await page.locator('input[name^="defaults["]').nth(1).fill('Paris');
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-next button')]);

        // 3. Confirmation: the summary, then the transfer with the originals sent to the trash.
        await expect(page).toHaveURL(/layout=confirm/);
        await expect(page.locator('#transferSummary')).toContainText('Paris');
        // Source field, then target field, then default value.
        await expect(page.locator('#transferSummary thead th')).toHaveText(['Source field', 'Target field', 'Default value', 'Example (first record)']);
        // The example is what the first record gives each field.
        const cells = page.locator('#transferSummary tbody tr').first().locator('th, td');
        await expect(cells.nth(3)).toHaveText('a@example.invalid');
        await expect(page.locator('#transferSummary tbody tr').nth(1).locator('td').nth(2)).toHaveText('Paris');
        await page.check('#transfer-trash');
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-transfer button')]);

        // 4. Report.
        await expect(page).toHaveURL(/layout=report/);
        await expect(page.locator('.alert-success')).toContainText('2');

        expect(Number(db('records-count', String(target)))).toBe(2);
        expect(dbJson('record', String(records[0])).state).toBe(TRASHED);
        expect(dbJson('record', String(records[1])).state).toBe(TRASHED);
        expect(dbJson('record', String(records[2])).state).toBe('1');

        const copy = dbJson('record', 'last', String(target));
        expect(Number(copy.form)).toBe(target);
        expect(dbJson('subrecords', String(copy.id))).toEqual({ email: 'b@example.invalid', city: 'Paris' });
    });

    test('closing the report ends the transfer', async () => {
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-cancel button')]);
        await expect(page).toHaveURL(/view=records/);

        await page.goto(`${ADMIN}?option=com_breezingformsng&view=recordstransfer&layout=report`);
        await expect(page).toHaveURL(/view=records/);
    });
});

test.describe('records transfer, from a deleted form', () => {
    test('the records of a deleted form are copied from the values they kept', async ({ browser }) => {
        const gone = Number(db('sample-form', 'xfer_gone'));
        const target = Number(db('sample-form', 'xfer_gone_target'));
        const record = Number(db('sample-record', String(gone), 'email', 'gone@example.invalid'));
        db('delete-form', String(gone));

        const page = await browser.newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
        await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);

        await page.goto(`${ADMIN}?option=com_breezingformsng&view=records&filter[form]=${gone}`);
        await page.locator(`input[name="cid[]"][value="${record}"]`).check();
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-transfer button')]);

        await expect(page).toHaveURL(/layout=select/);
        await expect(page.locator('.alert-info')).toBeVisible();
        await page.selectOption('#transfer-target', String(target));
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-next button')]);

        // The deleted source form has no editor to open: only the target form is linked.
        await expect(page.locator('a[target="_blank"][href*="quickmode.display"]')).toHaveCount(1);
        // The field of the records is matched by name to the field of the target form.
        await expect(page).toHaveURL(/layout=mapping/);
        await expect(page.locator('select[name^="map["]').first().locator('option:checked')).toContainText('Email');
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-next button')]);
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-transfer button')]);

        await expect(page).toHaveURL(/layout=report/);
        const copy = dbJson('record', 'last', String(target));
        expect(dbJson('subrecords', String(copy.id))).toEqual({ email: 'gone@example.invalid' });
        expect(dbJson('record', String(record)).state).toBe('1');
        await page.close();
    });
});

test.describe('records transfer, required fields of the target form', () => {
    test('a required field starts with its own default value and cannot be left empty', async ({ browser }) => {
        const source = Number(db('sample-form', 'xfer_req_source'));
        const target = Number(db('sample-form', 'xfer_req_target'));
        const city = Number(db('add-element', String(target), 'city', 'City'));
        const record = Number(db('sample-record', String(source), 'email', 'req@example.invalid'));
        // The QuickMode template of the target form: City is required and starts with Lyon.
        const template = Buffer.from(JSON.stringify({
            properties: { type: 'page' },
            children: [{ properties: { type: 'element', dbId: city, required: true, value: 'Lyon' }, children: [] }],
        })).toString('base64');
        db('set', String(target), 'template_code', template);

        const page = await browser.newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
        await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);

        await page.goto(`${ADMIN}?option=com_breezingformsng&view=records&filter[form]=${source}`);
        await page.locator(`input[name="cid[]"][value="${record}"]`).check();
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-transfer button')]);
        await page.selectOption('#transfer-target', String(target));
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-next button')]);

        // The default value of the required field is the one of the target field, and the field is marked.
        await expect(page).toHaveURL(/layout=mapping/);
        await expect(page.locator(`#default-${city}`)).toHaveValue('Lyon');
        await expect(page.locator('#transferMapping tbody .icon-asterisk')).toHaveCount(1);
        await expect(page.locator('#transferMapping tbody .icon-asterisk').first()).toHaveCSS('color', /^rgb\((1[5-9]\d|2\d\d), \d{1,2}, \d{1,2}\)$/);

        // Emptied, with no source field, it blocks the next screen.
        await page.fill(`#default-${city}`, '');
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-next button')]);
        await expect(page).toHaveURL(/layout=mapping/);
        await expect(page.locator('.alert-warning, .alert-message').first()).toContainText('City');

        await page.fill(`#default-${city}`, 'Nice');
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-next button')]);
        await expect(page).toHaveURL(/layout=confirm/);
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-transfer button')]);
        await expect(page).toHaveURL(/layout=report/);

        const copy = dbJson('record', 'last', String(target));
        expect(dbJson('subrecords', String(copy.id))).toEqual({ email: 'req@example.invalid', city: 'Nice' });
        await page.close();
    });
});

test.describe('records transfer, sorted lists', () => {
    test('the target forms and the source fields are listed by name', async ({ browser }) => {
        const source = Number(db('sample-form', 'sortsrc'));
        db('add-element', String(source), 'zeta', 'Zeta');
        db('add-element', String(source), 'alpha', 'Alpha');
        const record = Number(db('sample-record', String(source), 'email', 'sort@example.invalid'));
        const targets = ['sort_b', 'sort_a', 'sort_C'].map((name) => Number(db('sample-form', name)));
        const target = targets[0];

        const page = await browser.newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
        await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);

        await page.goto(`${ADMIN}?option=com_breezingformsng&view=records&filter[form]=${source}`);
        await page.locator(`input[name="cid[]"][value="${record}"]`).check();
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-transfer button')]);

        const forms = await page.locator('#transfer-target option').allTextContents();
        const at = (name) => forms.indexOf(`Sample ${name}`);
        expect(at('sort_a')).toBeGreaterThan(0);
        expect(at('sort_a')).toBeLessThan(at('sort_b'));
        expect(at('sort_b')).toBeLessThan(at('sort_C'));

        await page.selectOption('#transfer-target', String(target));
        await Promise.all([page.waitForNavigation(), page.click('#toolbar-next button')]);
        await expect(page.locator('select[name^="map["]').first().locator('option')).toHaveText([
            '— Nothing —', 'Alpha (alpha)', 'Email (email)', 'Zeta (zeta)',
        ]);
        await page.close();
    });
});

test.describe('records transfer, without the right to create', () => {
    const MANAGER = 'e2e_transfer_manager';
    const PASSWORD = 'E2e-Manager-123!';
    const MANAGER_GROUP = 6;
    let page;

    test.beforeAll(async ({ browser }) => {
        db('create-user', MANAGER, PASSWORD, String(MANAGER_GROUP));
        db('component-rules', JSON.stringify({ 'core.manage': { [MANAGER_GROUP]: 1 }, 'core.create': { [MANAGER_GROUP]: 0 } }));
        page = await (await browser.newContext()).newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await page.fill('#mod-login-username', MANAGER);
        await page.fill('#mod-login-password', PASSWORD);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);
    });

    test.afterAll(async () => {
        db('component-rules', '{}');
        await page.close();
    });

    test('the screens of the transfer are denied and the list offers no Transfer button', async () => {
        const list = await page.goto(`${ADMIN}?option=com_breezingformsng&view=records`);
        expect(list.status()).toBe(200);
        await expect(page.locator('#toolbar-transfer')).toHaveCount(0);

        const view = await page.goto(`${ADMIN}?option=com_breezingformsng&view=recordstransfer`);
        expect(view.status()).toBe(403);

        const csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        const start = await page.request.post(`${ADMIN}?option=com_breezingformsng&task=recordstransfer.start`, {
            form: { 'cid[]': '1', [csrf]: '1' },
            maxRedirects: 0,
        });
        expect(start.status()).toBe(403);
    });
});


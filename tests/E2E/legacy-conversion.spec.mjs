import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

// Run only against the disposable Joomla configured by the E2E runner.
const container = process.env.BFNG_E2E_CONTAINER;
const admin = 'administrator/index.php';

function db(...args) {
    return execFileSync('docker', ['exec', '-i', container, 'php', '--', ...args], {
        input: readFileSync(new URL('./db.php', import.meta.url)),
    }).toString();
}

const snapshot = (id) => JSON.parse(db('legacy-snapshot', String(id)));
const fixture = (mode) => JSON.parse(db('legacy-fixture', mode));

test.describe('legacy form conversion', () => {
    let page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(admin);
        await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
        await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);
    });

    test.afterAll(async () => {
        await page.close();
    });

    const convert = async (form) => {
        await page.goto(`${admin}?option=com_breezingformsng&view=forms`);
        const search = page.locator('#filter_search');
        await search.fill(form.name);
        await search.press('Enter');
        await page.locator(`input[name="cid[]"][value="${form.id}"]`).check();
        const toolbarToggle = page.getByRole('button', { name: 'Toolbar', exact: true });
        if (await toolbarToggle.isVisible()) {
            await toolbarToggle.click();
        }
        const button = page.getByRole('button', { name: /convert to quickmode/i });
        await expect(button).toBeVisible();
        await Promise.all([page.waitForNavigation(), button.click()]);
    };

    for (const mode of ['easy', 'classic']) {
        test(`converts a simple ${mode} form as a separate unpublished copy`, async () => {
            const form = fixture(mode);
            const before = snapshot(form.id);
            await convert(form);
            await expect(page.locator('joomla-alert[type="success"], .alert-success'))
                .toContainText('An unpublished QuickMode copy was created');

            const after = snapshot(form.id);
            expect(after.form).toEqual(before.form);
            expect(after.elements).toEqual(before.elements);
            expect(after.records).toEqual(before.records);
            expect(after.records).toHaveLength(1);
            expect(after.copies).toHaveLength(1);
            const copy = after.copies[0];
            expect(copy.published).toBe('0');
            expect(copy.template_code_processed).toBe('QuickMode');
            expect(copy.records).toBe(0);
            const converted = copy.elements.find((element) => element.name === 'first_name');
            expect(converted).toMatchObject({ type: 'Text', data1: 'initial' });
            expect(converted.id).not.toBe(before.elements[0].id);

            // The copy must open in the editor and keep its element through a save.
            await page.goto(`${admin}?option=com_breezingformsng&task=quickmode.display&form=${copy.id}`);
            await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
            const fields = await page.evaluate(() => {
                const nodes = [];
                window.BFQMApp.getItemsFlattened(window.BFQMApp.dataObject, nodes);
                return nodes.filter((node) => node.properties?.bfName === 'first_name')
                    .map((node) => node.properties);
            });
            expect(fields).toHaveLength(1);
            expect(fields[0]).toMatchObject({ bfType: 'bfTextfield', value: 'initial' });
            const reload = page.waitForEvent('load', { timeout: 45000 });
            await page.locator('#toolbar-apply').click();
            await reload;
            await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
            const saved = snapshot(form.id);
            // The converted copy belongs to QuickModeForms, so its options save
            // must not renumber the original legacy package.
            expect(saved.form).toEqual(before.form);
            expect(saved.elements).toEqual(before.elements);
            expect(saved.records).toEqual(before.records);
            expect(saved.copies[0].elements.filter((element) => element.name === 'first_name')).toHaveLength(1);

            // The copied form renders after it is deliberately published for this fixture.
            db('set', String(copy.id), 'published', '1');
            await page.goto(`index.php?option=com_breezingformsng&ff_form=${copy.id}`);
            await expect(page.locator('[name="ff_nm_first_name[]"]')).toHaveValue('initial');
        });
    }

    test('refuses unsupported fields without creating a copy', async () => {
        const form = fixture('unsupported');
        const before = snapshot(form.id);
        await convert(form);
        const warning = page.locator('joomla-alert[type="warning"], .alert-warning');
        await expect(warning).toContainText(/unsupported element|cannot be converted/i);
        await expect(warning).toContainText('<img src=x onerror=alert(1)>');
        await expect(warning.locator('img')).toHaveCount(0);
        expect(snapshot(form.id)).toEqual(before);
    });

    test('refuses a form-specific mail template without changing the source', async () => {
        const form = fixture('classic');
        const before = snapshot(form.id);
        db('legacy-template-create', form.name);
        try {
            await convert(form);
            await expect(page.locator('joomla-alert[type="warning"], .alert-warning'))
                .toContainText(/template/i);
            expect(snapshot(form.id)).toEqual(before);
        } finally {
            db('legacy-template-remove', form.name);
        }
    });

    test('refuses inconsistent mail notification options', async () => {
        const form = fixture('notifications');
        const before = snapshot(form.id);
        await convert(form);
        await expect(page.locator('joomla-alert[type="warning"], .alert-warning'))
            .toContainText(/settings|options/i);
        expect(snapshot(form.id)).toEqual(before);
    });

    test('the conversion action works at a mobile viewport with dark preference', async () => {
        const form = fixture('classic');
        await page.setViewportSize({ width: 390, height: 844 });
        await page.emulateMedia({ colorScheme: 'dark' });
        try {
            await convert(form);
            await expect(page.locator('joomla-alert[type="success"], .alert-success'))
                .toContainText('An unpublished QuickMode copy was created');
            expect(snapshot(form.id).copies).toHaveLength(1);
        } finally {
            await page.emulateMedia({ colorScheme: null });
            await page.setViewportSize({ width: 1300, height: 1000 });
        }
    });

    test('converts select list options and keeps them through an editor save', async () => {
        const form = fixture('select');
        const before = snapshot(form.id);
        await convert(form);
        await expect(page.locator('joomla-alert[type="success"], .alert-success'))
            .toContainText('An unpublished QuickMode copy was created');
        const after = snapshot(form.id);
        expect(after.form).toEqual(before.form);
        expect(after.elements).toEqual(before.elements);
        expect(after.records).toEqual(before.records);
        expect(after.copies).toHaveLength(1);
        const copy = after.copies[0];
        expect(copy.published).toBe('0');
        expect(copy.elements.find((element) => element.name === 'first_name'))
            .toMatchObject({ type: 'Select List', data2: '0;One;one\n1;Two;two' });

        await page.goto(`${admin}?option=com_breezingformsng&task=quickmode.display&form=${copy.id}`);
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
        const reload = page.waitForEvent('load', { timeout: 45000 });
        await page.locator('#toolbar-apply').click();
        await reload;
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
        const saved = snapshot(form.id);
        expect(saved.copies[0].elements.find((element) => element.name === 'first_name'))
            .toMatchObject({ type: 'Select List', data2: '0;One;one\n1;Two;two' });
        expect(saved.elements).toEqual(before.elements);
        expect(saved.records).toEqual(before.records);

        db('set', String(copy.id), 'published', '1');
        await page.goto(`index.php?option=com_breezingformsng&ff_form=${copy.id}`);
        const select = page.locator('select[name="ff_nm_first_name[]"]');
        await expect(select).toHaveValue('two');
        await expect(select.locator('option')).toHaveText(['One', 'Two']);
    });

    test('groups Classic radio buttons and keeps the selected value through a save', async () => {
        const form = fixture('radio');
        const before = snapshot(form.id);
        expect(before.elements).toHaveLength(2);
        await convert(form);
        await expect(page.locator('joomla-alert[type="success"], .alert-success'))
            .toContainText('An unpublished QuickMode copy was created');
        const after = snapshot(form.id);
        expect(after.form).toEqual(before.form);
        expect(after.elements).toEqual(before.elements);
        expect(after.records).toEqual(before.records);
        expect(after.copies).toHaveLength(1);
        const copy = after.copies[0];
        expect(copy.published).toBe('0');
        expect(copy.records).toBe(0);
        const groups = copy.elements.filter((element) => element.name === 'contact_method');
        expect(groups).toHaveLength(1);
        expect(groups[0]).toMatchObject({ type: 'Radio Group', data2: '1;Email;email\n0;Phone;phone' });

        await page.goto(`${admin}?option=com_breezingformsng&task=quickmode.display&form=${copy.id}`);
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
        const reload = page.waitForEvent('load', { timeout: 45000 });
        await page.locator('#toolbar-apply').click();
        await reload;
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
        const saved = snapshot(form.id);
        expect(saved.form).toEqual(before.form);
        expect(saved.elements).toEqual(before.elements);
        expect(saved.records).toEqual(before.records);
        expect(saved.copies[0].elements.filter((element) => element.name === 'contact_method'))
            .toMatchObject([{ type: 'Radio Group', data2: '1;Email;email\n0;Phone;phone' }]);

        db('set', String(copy.id), 'published', '1');
        await page.goto(`index.php?option=com_breezingformsng&ff_form=${copy.id}`);
        const choices = page.locator('input[type="radio"][name="ff_nm_contact_method[]"]');
        await expect(choices).toHaveCount(2);
        await expect(page.locator('input[type="radio"][name="ff_nm_contact_method[]"][value="email"]')).toBeChecked();
        await expect(page.locator('input[type="radio"][name="ff_nm_contact_method[]"][value="phone"]')).not.toBeChecked();
    });

    test('refuses an ambiguous Classic radio group with multiple checked choices', async () => {
        const form = fixture('radio-ambiguous');
        const before = snapshot(form.id);
        await convert(form);
        await expect(page.locator('joomla-alert[type="warning"], .alert-warning')).toBeVisible();
        expect(snapshot(form.id)).toEqual(before);
    });

    test('rejects a conversion request without a CSRF token', async () => {
        const form = fixture('classic');
        const before = snapshot(form.id);
        await page.request.post(`${admin}?option=com_breezingformsng&task=forms.convertLegacy`, {
            form: { 'cid[]': String(form.id) },
            maxRedirects: 0,
        });
        expect(snapshot(form.id)).toEqual(before);
    });

    test('denies conversion to a logged-in manager without create permission', async ({ browser }) => {
        const form = fixture('classic');
        const before = snapshot(form.id);
        const username = form.name;
        const password = 'E2e-NoCreate-123!';
        // Joomla's own CLI creates and hashes the password for this disposable account.
        execFileSync('docker', [
            'exec', '-e', 'HTTP_HOST=localhost', container, 'php', '/var/www/html/cli/joomla.php', 'user:add',
            `--username=${username}`, '--name=E2E Restricted', `--password=${password}`,
            `--email=${username}@example.invalid`, '--usergroup=Manager', '--no-interaction',
        ]);
        const originalRules = db('legacy-manager-deny');
        const restricted = await browser.newContext();
        try {
            const restrictedPage = await restricted.newPage();
            await restrictedPage.route('**/plg_system_guidedtours/**', (route) => route.abort());
            await restrictedPage.goto(admin);
            await restrictedPage.fill('#mod-login-username', username);
            await restrictedPage.fill('#mod-login-password', password);
            await Promise.all([restrictedPage.waitForNavigation(), restrictedPage.click('#btn-login-submit')]);
            await restrictedPage.goto(`${admin}?option=com_breezingformsng&view=forms`);
            await expect(restrictedPage.getByRole('button', { name: /convert to quickmode/i })).toHaveCount(0);
            const token = await restrictedPage.locator('#adminForm input[type="hidden"]').evaluateAll((inputs) =>
                inputs.find((input) => /^[a-f0-9]{32}$/.test(input.name) && input.value === '1')?.name);
            expect(token).toBeTruthy();
            const response = await restrictedPage.request.post(`${admin}?option=com_breezingformsng&task=forms.convertLegacy`, {
                form: { 'cid[]': String(form.id), [token]: '1' },
                maxRedirects: 0,
            });
            expect(response.status()).toBe(403);
            expect(snapshot(form.id)).toEqual(before);
        } finally {
            await restricted.close();
            db('legacy-manager-restore', originalRules);
        }
    });

    test('rejects an unauthenticated conversion request', async ({ browser }) => {
        const form = fixture('classic');
        const before = snapshot(form.id);
        const guest = await browser.newContext();
        try {
            await guest.request.post(`${admin}?option=com_breezingformsng&task=forms.convertLegacy`, {
                form: { 'cid[]': String(form.id) },
                maxRedirects: 0,
            });
        } finally {
            await guest.close();
        }
        expect(snapshot(form.id)).toEqual(before);
    });
});

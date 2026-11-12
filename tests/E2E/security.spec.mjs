import { test, expect, request } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

// Functional checks of the protections a source test can only see written: each
// request below is really sent, and what the site did with it is read back from
// its database and its files.

const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const ADMIN = 'administrator/index.php';
const SITE = 'index.php?option=com_breezingformsng';

const db = (...args) => execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
    input: readFileSync(new URL('./db.php', import.meta.url)),
}).toString();
const dbJson = (...args) => JSON.parse(db(...args));
const sh = (command) => execFileSync('docker', ['exec', CONTAINER, 'sh', '-c', command]).toString().trim();

const MANAGER = 'e2e_manager';
const PASSWORD = 'E2e-Manager-123!';
const MANAGER_GROUP = 6;
const TOKEN = /name="([0-9a-f]{32})" value="1"/;

test.describe.configure({ mode: 'serial' });

test.describe('security, as the site really answers', () => {
    let admin;
    let textForm;
    let fileForm;
    let record;

    const login = async (page, username, password) => {
        await page.goto(ADMIN);
        await page.fill('#mod-login-username', username);
        await page.fill('#mod-login-password', password);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);
    };

    // A QuickMode form with one element of the given type, published, without notifications.
    const createForm = async (page, name, type, elementName) => {
        const selectNode = async (kind) => {
            const id = await page.evaluate((wanted) => [...document.querySelectorAll('#bfElementExplorer li.jstree-node')]
                .map((node) => node.id)
                .find((nodeId) => window.BFQMApp.treeModel.getNodeType(window.BFQMApp.treeModel.find(nodeId)) === wanted), kind);
            await page.locator(`[id="${id}"] > a`).click();
            await page.waitForFunction((wanted) => window.BFQMApp.treeModel.getNodeType(window.BFQMApp.selectedTreeElement) === wanted, kind);
        };

        await page.goto(`${ADMIN}?option=com_breezingformsng&task=quickmode.display&form=0`);
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
        await page.fill('#bfFormTitle', name);
        await page.fill('#bfFormName', name);
        await page.locator('#bfNewPageButton').click();
        await page.waitForTimeout(500);
        await selectNode('page');
        await page.locator('#bfNewSectionButton').click();
        await page.waitForTimeout(500);
        await selectNode('section');
        await page.locator('#bfNewElementButton').click();
        await selectNode('element');
        await page.waitForTimeout(300);
        await page.locator('#bfElementTypeButton').click();
        await page.locator(`#bfElementTypeList .bfng-type-icon--bfElementType${type}`).first().click();
        await page.waitForTimeout(300);
        await page.fill('#bfElementLabel', elementName);
        await page.fill('#bfElementName', elementName);
        if (type === 'File') {
            // The chunked upload, which the ticket protects, is the HTML5 uploader of the element.
            await page.evaluate(() => {
                const box = document.getElementById('bfElementFileAdvancedHtml5Uploader');
                box.checked = true;
                box.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
        await selectNode('section');

        const reloaded = page.waitForEvent('load', { timeout: 45000 });
        await page.locator('#toolbar-apply').click();
        await reloaded;
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);

        const id = Number(new URL(page.url()).searchParams.get('form'));
        expect(id).toBeGreaterThan(0);
        db('set', String(id), 'published', '1');
        db('set', String(id), 'emailntf', '0');
        db('set', String(id), 'mb_emailntf', '0');

        return id;
    };

    // The form of the site as a visitor gets it: its action, its fields and the token of its session.
    const openForm = async (api, id) => {
        const html = await (await api.get(`${SITE}&ff_form=${id}`)).text();
        const form = html.match(new RegExp(`<form[^>]*name="ff_form${id}"[^>]*>`))?.[0];
        expect(form, 'the form is on the page').toBeTruthy();

        return {
            action: form.match(/action="([^"]+)"/)[1],
            token: html.match(TOKEN)?.[1],
            html,
        };
    };

    const records = (id) => Number(db('records-count', String(id)));

    test.beforeAll(async ({ browser }) => {
        // Two QuickMode forms are built through the editor.
        test.setTimeout(240000);
        admin = await browser.newPage();
        await admin.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await login(admin, process.env.BFNG_E2E_USER, process.env.BFNG_E2E_PASSWORD);

        textForm = await createForm(admin, 'e2e_sec_text', 'Text', 'firstname');
        fileForm = await createForm(admin, 'e2e_sec_file', 'File', 'attachment');
    });

    test.afterAll(async () => {
        await admin.close();
    });

    test.describe('the CSRF token of the site submission', () => {
        let api;

        test.beforeAll(async ({ baseURL }) => {
            api = await request.newContext({ baseURL });
        });

        test.afterAll(async () => {
            await api.dispose();
        });

        const submit = async (fields, form) => api.post(form.action, {
            form: { ff_task: 'submit', ff_form: String(textForm), 'ff_nm_firstname[]': 'Ada', ...fields },
        });

        test('a submission with the token of its page makes a record', async () => {
            const form = await openForm(api, textForm);
            expect(form.token).toBeTruthy();
            const before = records(textForm);

            const response = await submit({ [form.token]: '1' }, form);

            expect(response.status()).toBe(200);
            expect(records(textForm)).toBe(before + 1);
            record = dbJson('record', 'last', String(textForm));
        });

        test('a submission without a token is rejected and stores nothing', async () => {
            const form = await openForm(api, textForm);
            const before = records(textForm);

            const response = await submit({}, form);

            expect(response.status()).toBe(403);
            expect(records(textForm)).toBe(before);
        });

        test('a submission with an invalid token is rejected and stores nothing', async () => {
            const form = await openForm(api, textForm);
            const before = records(textForm);

            for (const token of ['0'.repeat(32), form.token.split('').reverse().join('')]) {
                const response = await submit({ [token]: '1' }, form);
                expect(response.status(), token).toBe(403);
            }
            expect(records(textForm)).toBe(before);
        });

        test('the token of another session is rejected', async ({ baseURL }) => {
            const other = await request.newContext({ baseURL });
            const stranger = await openForm(other, textForm);
            await other.dispose();
            const form = await openForm(api, textForm);
            const before = records(textForm);

            const response = await submit({ [stranger.token]: '1' }, form);

            expect(response.status()).toBe(403);
            expect(records(textForm)).toBe(before);
        });
    });

    test.describe('a user the permissions of the component do not allow', () => {
        let page;
        let csrf;

        const ajax = (task, fields) => page.evaluate(async ([url, data]) => {
            const body = new FormData();
            Object.entries(data).forEach(([key, value]) => body.append(key, value));
            const response = await fetch(url, { method: 'POST', body, credentials: 'same-origin' });

            return { status: response.status, text: (await response.text()).slice(0, 300) };
        }, [`/${ADMIN}?option=com_breezingformsng&task=${task}`, fields]);

        test.beforeAll(async ({ browser }) => {
            db('create-user', MANAGER, PASSWORD, String(MANAGER_GROUP));
            page = await browser.newPage();
            await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
            await login(page, MANAGER, PASSWORD);
            csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        });

        test.afterAll(async () => {
            db('component-rules', '{}');
            await page.close();
        });

        test('without the right to manage the component, its screens are denied', async () => {
            db('component-rules', '{}');
            expect(csrf).toMatch(/^[0-9a-f]{32}$/);

            for (const view of ['records', 'forms', 'about']) {
                const response = await page.goto(`${ADMIN}?option=com_breezingformsng&view=${view}`);
                expect(response.status(), view).toBe(403);
                await expect(page.locator('main, body')).not.toContainText('Audit information');
            }
        });

        test('without the right to manage the component, a state change is denied and changes nothing', async () => {
            db('component-rules', '{}');

            const answer = await ajax('records.setState', { record_id: String(record.id), state: '2', [csrf]: '1' });

            expect(answer.status).toBe(403);
            expect(dbJson('record', String(record.id)).state).toBe('1');
        });

        test('allowed to manage but not to change states, a record cannot be archived', async () => {
            db('component-rules', JSON.stringify({ 'core.manage': { [MANAGER_GROUP]: 1 }, 'core.edit.state': { [MANAGER_GROUP]: 0 } }));

            const list = await page.goto(`${ADMIN}?option=com_breezingformsng&view=records`);
            expect(list.status()).toBe(200);

            const answer = await ajax('records.setState', { record_id: String(record.id), state: '2', [csrf]: '1' });

            expect(answer.status).toBe(403);
            expect(dbJson('record', String(record.id)).state).toBe('1');
        });

        test('a record of a visitor is not opened for edition without the right to edit', async () => {
            db('component-rules', JSON.stringify({ 'core.manage': { [MANAGER_GROUP]: 1 }, 'core.edit': { [MANAGER_GROUP]: 0 } }));

            await page.goto(`${ADMIN}?option=com_breezingformsng&task=record.edit&id=${record.id}`);

            await expect(page.locator('form#adminForm [name^="jform"], #record-section-values')).toHaveCount(0);
        });

        test('the same user, once allowed, does change the state: the denials above come from the permissions', async () => {
            db('component-rules', JSON.stringify({ 'core.manage': { [MANAGER_GROUP]: 1 }, 'core.edit.state': { [MANAGER_GROUP]: 1 } }));

            const answer = await ajax('records.setState', { record_id: String(record.id), state: '2', [csrf]: '1' });

            expect(answer.status).toBe(200);
            expect(dbJson('record', String(record.id)).state).toBe('2');
            await ajax('records.setState', { record_id: String(record.id), state: '1', [csrf]: '1' });
            expect(dbJson('record', String(record.id)).state).toBe('1');
        });
    });

    test.describe('the upload of a file on the site', () => {
        let api;
        let form;
        const upload = (fields, name = 'report.txt') => api.post(`${SITE}&flashUpload=1`, {
            multipart: {
                form: String(fileForm),
                itemName: 'attachment',
                offset: '0',
                name,
                ...fields,
                Filedata: { name, mimeType: 'text/plain', buffer: Buffer.from('payload') },
            },
        });
        const tmpFiles = () => sh("find /var/www/html/tmp -type f -name '*_flashtmp' 2>/dev/null | sort");
        const strays = () => sh("find /var /tmp /var/www -xdev -type f \\( -name '*evil*' -o -name '*passwd*' \\) -not -path '/var/www/html/tmp/*' -not -path '/var/lib/*' -not -path '/var/cache/*' -not -path '*/vendor/*' 2>/dev/null | sort");

        test.beforeAll(async ({ baseURL }) => {
            api = await request.newContext({ baseURL });
            form = await openForm(api, fileForm);
        });

        test.afterAll(async () => {
            sh("rm -rf /var/www/html/tmp/*_flashtmp /var/www/html/tmp/*/*_flashtmp /var/www/html/tmp/*/chunks 2>/dev/null || true");
            await api.dispose();
        });

        test('the page of the form issues the ticket of its session', async () => {
            expect(form.html).toMatch(/name="bfFlashUploadTicket" value="[0-9a-f]{32}"/);
        });

        test('an upload with a ticket the session never got is refused and stores nothing', async () => {
            const before = tmpFiles();

            const response = await upload({ bfFlashUploadTicket: 'f'.repeat(32) });

            // The request is ignored as it was before: an empty answer, and no file.
            expect(response.status()).toBe(200);
            expect(await response.text()).toBe('');
            expect(tmpFiles()).toBe(before);
        });

        test('an upload with a ticket that is no ticket is refused and stores nothing', async () => {
            const before = tmpFiles();

            for (const ticket of ['../../../evil', 'x'.repeat(32), '']) {
                await upload({ bfFlashUploadTicket: ticket });
            }

            expect(tmpFiles()).toBe(before);
            expect(strays()).toBe('');
        });

        test('an element name made of a path is refused and stores nothing', async () => {
            const ticket = form.html.match(/name="bfFlashUploadTicket" value="([0-9a-f]{32})"/)[1];
            const before = tmpFiles();

            for (const itemName of ['../../evil', '..\\..\\evil', 'a/b']) {
                await upload({ bfFlashUploadTicket: ticket, itemName });
            }

            expect(tmpFiles()).toBe(before);
            expect(strays()).toBe('');
        });

        test('a file named as a path stays in the temporary folder, under a neutral name', async () => {
            const ticket = form.html.match(/name="bfFlashUploadTicket" value="([0-9a-f]{32})"/)[1];
            const before = strays();

            for (const name of ['../../../../evil.php', '..\\..\\evil.php', '/etc/passwd', 'evil.php%00.txt']) {
                const response = await upload({ bfFlashUploadTicket: ticket }, name);
                expect(response.status(), name).toBe(200);
            }

            // Nothing outside the temporary folder of the uploads, whatever the name asked for.
            expect(strays()).toBe(before);
            const stored = tmpFiles().split('\n').filter(Boolean);
            expect(stored.length).toBeGreaterThan(0);
            for (const file of stored) {
                expect(file).toMatch(/^\/var\/www\/html\/tmp\/breezingformsng\/uploads\/[^/\\]+_flashtmp$/);
                expect(file).not.toContain('..');
                // Whatever the name, the file cannot be run as a script.
                expect(file).not.toMatch(/\.php$/);
            }
            // The path and the null byte are gone from the names: only their file name is left.
            expect([...new Set(stored.map((file) => file.split('/').pop().split('_attachment_')[0]))].sort())
                .toEqual(['evil.php', 'evilphp00.txt', 'passwd']);
        });
    });

    test.describe('the payment of a Stripe form', () => {
        test('a webhook for a form without a Stripe element is refused', async ({ baseURL }) => {
            const api = await request.newContext({ baseURL });
            const before = records(textForm);

            const response = await api.post(`${SITE}&stripeWebhook=1&form_id=${textForm}`, {
                headers: { 'Content-Type': 'application/json', 'Stripe-Signature': 't=1,v1=00' },
                data: { id: 'evt_1', type: 'checkout.session.completed' },
            });

            expect(response.status()).toBe(400);
            expect(records(textForm)).toBe(before);
            expect(dbJson('record', String(record.id)).paypal_tx_id).toBe('');
            await api.dispose();
        });

        test('a payment is stored once: its replay changes nothing', async () => {
            const result = dbJson('pay-twice', String(record.id));

            expect(result.first).toBe(true);
            expect(result.second).toBe(false);
            expect(result.paypal_tx_id).toBe('Stripe: pi_first');
            expect(result.paypal_payment_date).toBe('2026-01-01 10:00:00');
            expect(dbJson('record', String(record.id)).paypal_tx_id).toBe('Stripe: pi_first');
        });
    });

    test.describe('a package whose references do not hold together', () => {
        const form = (sourceId, name) => ({
            source_id: sourceId,
            data: { name, title: name, published: 1, pages: 1 },
            elements: [],
        });
        // The package column holds 30 characters.
        const packageOf = (kind, parts) => {
            const name = `e2e-${kind}-${String(Date.now()).slice(-7)}`;

            return { format: 'breezingformsng-package', version: 3, package: name, ...parts(name) };
        };
        const written = (payload) => ({
            forms: dbJson('forms-count', payload.package).package,
            rules: Number(db('rules-count', payload.package)),
        });
        const DANGER = 'joomla-alert[type="danger"], .alert-danger';

        // As a user sends it: the page reads the file and asks the server for a
        // preview first, and answers Import once that is done. Its answer to the
        // preview is part of what is sent, so the import waits for it.
        const throughThePage = async (payload) => {
            await admin.goto(`${ADMIN}?option=com_breezingformsng&view=packages&layout=import`);
            await admin.setInputFiles('#bf-package-file', {
                name: 'integrity.json',
                mimeType: 'application/json',
                buffer: Buffer.from(JSON.stringify(payload)),
            });
            await expect(admin.locator('joomla-toolbar-button[task="packages.import"] button')).toBeEnabled({ timeout: 20000 });
            await Promise.all([
                admin.waitForNavigation(),
                admin.locator('#bf-package-file').evaluate((input) => input.form.submit()),
            ]);

            return (await admin.locator('joomla-alert').first().innerText()).replace(/\s+/g, ' ');
        };

        // As a script sends it, without the page: the server alone decides.
        const directly = async (payload) => {
            await admin.goto(`${ADMIN}?option=com_breezingformsng&view=packages&layout=import`);

            return admin.evaluate(async (json) => {
                const form = document.getElementById('bf-package-import');
                const data = new FormData(form);
                data.set('package_file', new File([json], 'integrity.json', { type: 'application/json' }));
                const html = await (await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' })).text();

                return html.match(/"joomla\.messages":(\[.*?\]),"system\.paths"/s)?.[1] ?? '';
            }, JSON.stringify(payload));
        };

        const inconsistent = {
            'two forms sharing a source id': (name) => ({ forms: [form(1, `${name}_a`), form(1, `${name}_b`)] }),
            'two entries of Integrator rules for one form': (name) => ({
                forms: [form(1, name)],
                integrator: [{ form_source_id: 1, rules: [] }, { form_source_id: 1, rules: [] }],
            }),
            'a rule that is no rule': (name) => ({ forms: [form(1, name)], integrator: [{ form_source_id: 1, rules: ['not a rule'] }] }),
        };

        test('a consistent package is imported, by the page and by a script', async () => {
            for (const send of [throughThePage, directly]) {
                const payload = packageOf('ok', (name) => ({ forms: [form(1, name)] }));

                await send(payload);

                expect(written(payload)).toEqual({ forms: 1, rules: 0 });
            }
        });

        for (const [problem, parts] of Object.entries(inconsistent)) {
            test(`${problem}: rejected by the page, and nothing is left behind`, async () => {
                const payload = packageOf('bad', parts);
                const before = dbJson('forms-count').all;

                const message = await throughThePage(payload);

                expect(message).toContain("The file 'integrity.json' is not a valid BFNG package.");
                expect(written(payload)).toEqual({ forms: 0, rules: 0 });
                expect(dbJson('forms-count').all).toBe(before);
            });
        }

        for (const [problem, parts] of Object.entries({
            ...inconsistent,
            'Integrator rules of a form the file does not hold': (name) => ({
                forms: [form(1, name)],
                integrator: [{ form_source_id: 99, rules: [] }],
            }),
        })) {
            test(`${problem}: rejected by the server alone, and nothing is left behind`, async () => {
                const payload = packageOf('bad', parts);
                const before = dbJson('forms-count').all;

                const messages = await directly(payload);

                expect(messages).toContain("The file 'integrity.json' is not a valid BFNG package.");
                expect(written(payload)).toEqual({ forms: 0, rules: 0 });
                expect(dbJson('forms-count').all).toBe(before);
            });
        }

        // The page lets the user import part of a file: Integrator rules of a form that is
        // not chosen are skipped. Rules of a form the file does not hold look the same, so
        // through the page the form is imported and the rules, which cannot be used, are not.
        test('through the page, Integrator rules of a form the file does not hold are skipped, not written', async () => {
            const payload = packageOf('orp', (name) => ({ forms: [form(1, name)], integrator: [{ form_source_id: 99, rules: [] }] }));

            const message = await throughThePage(payload);

            expect(message).toContain('has been imported');
            expect(written(payload)).toEqual({ forms: 1, rules: 0 });
        });
    });
});

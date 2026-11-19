import { test, expect, request } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { submitLogin } from './login.mjs';

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
        await submitLogin(page, username, password);
    };

    // A QuickMode form with one element of the given type, published, without notifications.
    const createForm = async (page, name, type, elementName) => {
        const selectNode = async (kind) => {
            const id = await (await page.waitForFunction((wanted) => [...document.querySelectorAll('#bfElementExplorer li.jstree-node')]
                .map((node) => node.id)
                .find((nodeId) => window.BFQMApp.treeModel.getNodeType(window.BFQMApp.treeModel.find(nodeId)) === wanted) || false, kind, { timeout: 30000 })).jsonValue();
            await page.locator(`[id="${id}"] > a`).click();
            await page.waitForFunction((wanted) => window.BFQMApp.treeModel.getNodeType(window.BFQMApp.selectedTreeElement) === wanted, kind);
        };

        await page.goto(`${ADMIN}?option=com_breezingformsng&task=quickmode.display&form=0`);
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
        await page.fill('#bfFormTitle', name);
        await page.fill('#bfFormName', name);
        await page.locator('#bfNewPageButton').click();
        await selectNode('page');
        await page.locator('#bfNewSectionButton').click();
        await selectNode('section');
        await page.locator('#bfNewElementButton').click();
        await selectNode('element');
        await page.locator('#bfElementTypeButton').click();
        await page.locator(`#bfElementTypeList .bfng-type-icon--bfElementType${type}`).first().click();
            await expect(page.locator('#bfElementTypeList')).toBeHidden();
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

    test('QuickMode refuses a save after another edit and rolls back its checkout', async () => {
        const formId = String(textForm);
        const url = `${ADMIN}?option=com_breezingformsng&task=quickmode.display&form=${formId}`;
        await admin.goto(url);
        await admin.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
        const before = dbJson('form', formId);
        const newTitle = 'e2e_sec_text_current';

        // Simulate a newer revision already saved and checked in.
        db('set', formId, 'title', newTitle);
        try {
            await admin.fill('#bfFormTitle', 'e2e_sec_text_stale');
            const response = admin.waitForResponse((answer) => answer.request().method() === 'POST'
                && answer.request().postData()?.includes('task=quickmode.doAjaxSave')
                && answer.status() === 409);
            await admin.locator('#toolbar-apply').click();
            await response;

            const current = dbJson('form', formId);
            expect(current.title).toBe(newTitle);
            expect(current.template_code).toBe(before.template_code);
            expect(current.version).toBe(before.version);
            expect(Number(current.checked_out ?? 0)).toBe(0);
        } finally {
            db('set', formId, 'title', before.title);
        }
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
        const integratorFixtures = [];

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
            for (const ruleId of integratorFixtures) {
                db('delete-integrator-rule', String(ruleId));
            }
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

        test('the audit summary requires a valid token and component permission', async () => {
            db('component-rules', '{}');

            const withoutToken = await ajax('about.auditSummary', {});
            const invalidToken = await ajax('about.auditSummary', { ['0'.repeat(32)]: '1' });
            const denied = await ajax('about.auditSummary', { [csrf]: '1' });

            expect(withoutToken.status).toBe(403);
            expect(invalidToken.status).toBe(403);
            expect(denied.status).toBe(403);
            expect(denied.text).not.toContain('issues_total');

            db('component-rules', JSON.stringify({ 'core.manage': { [MANAGER_GROUP]: 1 } }));
            const allowed = await ajax('about.auditSummary', { [csrf]: '1' });

            expect(allowed.status).toBe(200);
            const payload = JSON.parse(allowed.text);
            expect(payload.data).toEqual(expect.objectContaining({ issues: expect.any(Number), errors: expect.any(Number) }));
            expect(payload.data).not.toHaveProperty('report');
            expect(payload.data).not.toHaveProperty('tables');
        });

        test('allowed to manage but not to change states, a record cannot be archived', async () => {
            db('component-rules', JSON.stringify({ 'core.manage': { [MANAGER_GROUP]: 1 }, 'core.edit.state': { [MANAGER_GROUP]: 0 } }));

            const list = await page.goto(`${ADMIN}?option=com_breezingformsng&view=records`);
            expect(list.status()).toBe(200);

            const answer = await ajax('records.setState', { record_id: String(record.id), state: '2', [csrf]: '1' });

            expect(answer.status).toBe(403);
            expect(dbJson('record', String(record.id)).state).toBe('1');
        });

        test('form options and the QuickMode lock require the form ACLs', async () => {
            db('component-rules', JSON.stringify({
                'core.manage': { [MANAGER_GROUP]: 1 },
                'core.edit': { [MANAGER_GROUP]: 0 },
                'core.edit.state': { [MANAGER_GROUP]: 0 },
            }));

            const before = dbJson('form', String(textForm));
            const deniedSave = await ajax('forms.saveOptions', {
                id: String(textForm), title: 'e2e_unauthorized_title', [csrf]: '1',
            });
            const deniedDebug = await ajax('forms.setDebug', {
                id: String(textForm), state: '1', [csrf]: '1',
            });

            expect(deniedSave.status).toBe(403);
            expect(deniedDebug.status).toBe(403);
            expect(dbJson('form', String(textForm)).title).toBe(before.title);
            expect(dbJson('form', String(textForm)).debug_mode).toBe(before.debug_mode);

            db('set', String(textForm), 'checked_out', '0');
            await page.goto(`${ADMIN}?option=com_breezingformsng&task=quickmode.display&form=${textForm}`);

            expect(new URL(page.url()).searchParams.get('view')).toBe('forms');
            expect(Number(dbJson('form', String(textForm)).checked_out ?? 0)).toBe(0);

            // A valid edit session can render the editor, but a cross-site GET
            // cannot acquire the lock because rendering no longer mutates state.
            db('component-rules', JSON.stringify({
                'core.manage': { [MANAGER_GROUP]: 1 },
                'core.edit': { [MANAGER_GROUP]: 1 },
                'core.edit.state': { [MANAGER_GROUP]: 0 },
            }));
            const editor = await page.goto(`${ADMIN}?option=com_breezingformsng&task=quickmode.display&form=${textForm}`);

            expect(editor.status()).toBe(200);
            expect(Number(dbJson('form', String(textForm)).checked_out ?? 0)).toBe(0);

            const allowedEdit = await ajax('forms.saveOptions', {
                id: String(textForm), title: before.title, published: '0', [csrf]: '1',
            });

            expect(allowedEdit.status).toBe(200);
            expect(Number(dbJson('form', String(textForm)).published)).toBe(Number(before.published));
        });

        test('Integrator state and permanent deletion each require their Joomla ACL right', async () => {
            const ruleId = Number(db('create-integrator-rule', String(textForm)));
            integratorFixtures.push(ruleId);
            expect(ruleId).toBeGreaterThan(0);

            db('component-rules', JSON.stringify({
                'core.manage': { [MANAGER_GROUP]: 1 },
                'breezingformsng.code': { [MANAGER_GROUP]: 1 },
                'core.edit.state': { [MANAGER_GROUP]: 0 },
                'core.delete': { [MANAGER_GROUP]: 0 },
            }));

            const list = await page.goto(`${ADMIN}?option=com_breezingformsng&view=integrator`);
            expect(list.status()).toBe(200);

            const deniedToggle = await ajax('integrator.setPublished', {
                id: String(ruleId), state: '0', [csrf]: '1',
            });
            const deniedTrash = await ajax('integrator.trash', {
                'cid[]': String(ruleId), [csrf]: '1',
            });

            expect(deniedToggle.status).toBe(403);
            expect(deniedTrash.status).toBe(403);
            expect(db('integrator-rule-state', String(ruleId))).toBe('1');

            // Seed the trash directly, then prove the user still cannot empty it.
            db('set-integrator-rule-state', String(ruleId), '-2');
            const deniedDelete = await ajax('integrator.remove', {
                'cid[]': String(ruleId), [csrf]: '1',
            });

            expect(deniedDelete.status).toBe(403);
            expect(db('integrator-rule-state', String(ruleId))).toBe('-2');

            // About's bulk action must enforce the same right as the list.
            await ajax('about.emptyItemsTrash', { trash_type: 'integrator', [csrf]: '1' });
            expect(db('integrator-rule-state', String(ruleId))).toBe('-2');
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
    test.describe('the code of a form, for a user who may edit forms but not code', () => {
        let page;
        let csrf;
        const CODE_WARNING = 'The changes to the form’s code';
        const CODE = ['script1code', 'script2code', 'piece1code', 'piece2code', 'piece3code', 'piece4code'];
        const STORED = Object.fromEntries(CODE.map((column) => [column, `// stored ${column}`]));
        const rules = (code) => JSON.stringify({
            'core.manage': { [MANAGER_GROUP]: 1 },
            'core.edit': { [MANAGER_GROUP]: 1 },
            'breezingformsng.code': { [MANAGER_GROUP]: code },
        });
        const codeOf = () => {
            const form = dbJson('form', String(textForm));

            return Object.fromEntries(CODE.map((column) => [column, form[column]]));
        };
        // The save of the options page, as the browser sends it.
        const saveOptions = (fields) => page.evaluate(async ([url, data]) => {
            const body = new FormData();
            Object.entries(data).forEach(([key, value]) => body.append(key, value));
            const response = await fetch(url, { method: 'POST', body, credentials: 'same-origin' });

            return { status: response.status, text: (await response.text()).slice(0, 300) };
        }, [`/${ADMIN}?option=com_breezingformsng&task=forms.saveOptions`, {
            id: String(textForm), title: dbJson('form', String(textForm)).title, [csrf]: '1', ...fields,
        }]);

        test.beforeAll(async ({ browser }) => {
            db('create-user', MANAGER, PASSWORD, String(MANAGER_GROUP));
            page = await browser.newPage();
            await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
            await login(page, MANAGER, PASSWORD);
            csrf = await page.evaluate(() => Joomla.getOptions('csrf.token'));
        });

        test.beforeEach(() => {
            for (const [column, code] of Object.entries(STORED)) {
                db('set', String(textForm), column, code);
            }
            db('set', String(textForm), 'checked_out', '0');
        });

        test.afterAll(async () => {
            for (const column of CODE) {
                db('set', String(textForm), column, '');
            }
            db('component-rules', '{}');
            await page.close();
        });

        test('a changed piece or script is not stored, and the page says so', async () => {
            db('component-rules', rules(0));

            const answer = await saveOptions({
                piece1code: '<?php echo "evil";', script1code: 'alert("evil")', title: 'e2e_code_title',
            });

            expect(answer.status).toBe(200);
            expect(JSON.parse(answer.text).success).toBe(true);
            expect(codeOf()).toEqual(STORED);
            // The rest of the options is saved, and the next page tells what was left out.
            expect(dbJson('form', String(textForm)).title).toBe('e2e_code_title');
            await page.goto(`${ADMIN}?option=com_breezingformsng&view=forms`);
            await expect(page.locator('joomla-alert[type="warning"]', { hasText: CODE_WARNING })).toHaveCount(1);
            db('set', String(textForm), 'title', 'e2e_sec_text');
        });

        test('the same code posted again is no change: no warning, nothing lost', async () => {
            db('component-rules', rules(0));

            // The options page posts the six code fields, as stored.
            const answer = await saveOptions(STORED);

            expect(answer.status).toBe(200);
            expect(codeOf()).toEqual(STORED);
            await page.goto(`${ADMIN}?option=com_breezingformsng&view=forms`);
            await expect(page.locator('joomla-alert', { hasText: CODE_WARNING })).toHaveCount(0);
        });

        // FormsController::postedOptions() gives every code field the value null when it is not posted:
        // FormCodeGuard::apply() must read that as no change, not as a change to "".
        test('a save that does not post the code at all leaves it alone without a warning', async () => {
            db('component-rules', rules(0));

            const answer = await saveOptions({});

            expect(answer.status).toBe(200);
            expect(codeOf()).toEqual(STORED);
            await page.goto(`${ADMIN}?option=com_breezingformsng&view=forms`);
            await expect(page.locator('joomla-alert', { hasText: CODE_WARNING })).toHaveCount(0);
        });

        test('with the permission to edit code, the same request stores it', async () => {
            db('component-rules', rules(1));

            const answer = await saveOptions({ piece1code: '// changed piece', script1code: '// changed script' });

            expect(answer.status).toBe(200);
            expect(codeOf()).toEqual({ ...STORED, piece1code: '// changed piece', script1code: '// changed script' });
            await page.goto(`${ADMIN}?option=com_breezingformsng&view=forms`);
            await expect(page.locator('joomla-alert', { hasText: CODE_WARNING })).toHaveCount(0);
        });
    });

    test.describe('the upload field of a submission', () => {
        const UPLOADS = '/var/www/html/media/breezingforms/uploads';
        let api;
        let form;
        let createdUploads = false;
        // The name of a file is stripped of its hyphens when stored: the patterns look for the stem.
        const stored = (stem) => sh(`find /var/www /tmp -type f -name '*${stem}*' 2>/dev/null | sort`);
        const send = (file, mimeType = 'application/octet-stream') => api.post(form.action, {
            multipart: {
                ff_task: 'submit',
                ff_form: String(fileForm),
                [form.token]: '1',
                bfFlashUploadTicket: form.html.match(/name="bfFlashUploadTicket" value="([0-9a-f]{32})"/)?.[1] ?? '',
                'ff_nm_attachment[]': { name: file, mimeType, buffer: Buffer.from('e2e payload') },
            },
        });

        test.beforeAll(async ({ baseURL }) => {
            api = await request.newContext({ baseURL });
            // A new installation has no upload folder yet: the administrator creates the one of {ff_uploads}.
            createdUploads = sh(`test -d ${UPLOADS} && echo present || (mkdir -p ${UPLOADS} && chown www-data:www-data ${UPLOADS} && echo created)`) === 'created';
            db('set-element', String(fileForm), 'attachment', 'data2', '');
        });

        test.afterAll(async () => {
            sh(`rm -f ${UPLOADS}/e2eshell* ${UPLOADS}/*e2eplain* ${UPLOADS}/*e2eshell*`);
            if (createdUploads) {
                sh(`rm -rf ${UPLOADS}`);
            }
            await api.dispose();
        });

        test.beforeEach(async () => {
            form = await openForm(api, fileForm);
        });

        test('an ordinary file is accepted when the field allows any type, and is stored', async () => {
            const before = records(fileForm);

            const response = await send('e2e-plain.txt', 'text/plain');

            expect(response.status()).toBe(200);
            expect(await response.text()).not.toContain('has an invalid extension');
            expect(records(fileForm)).toBe(before + 1);
            expect(stored('e2eplain')).toContain(`${UPLOADS}/`);
        });

        test('a script, alone or hidden behind a second extension, is refused and nothing is stored', async () => {
            for (const name of ['e2e-shell.php', 'e2e-shell.php.jpg', 'e2e-shell.phtml', 'e2e-shell.PHP', 'e2e-shell.php5.txt']) {
                form = await openForm(api, fileForm);
                const before = records(fileForm);

                const response = await send(name);

                expect(response.status(), name).toBe(200);
                expect(await response.text(), name).toContain('has an invalid extension');
                expect(stored('e2eshell'), name).toBe('');
                expect(sh(`find ${UPLOADS} -type f | sort`), name).not.toMatch(/shell/);
                expect(records(fileForm), name).toBe(before);
            }
        });

        test('a script is also refused when the field lists the extensions it allows', async () => {
            db('set-element', String(fileForm), 'attachment', 'data2', 'jpg, png');
            try {
                for (const name of ['e2e-shell.php', 'e2e-shell.php.jpg']) {
                    form = await openForm(api, fileForm);
                    const before = records(fileForm);

                    const response = await send(name, 'image/jpeg');

                    expect(await response.text(), name).toContain('has an invalid extension');
                    expect(stored('e2eshell'), name).toBe('');
                    expect(records(fileForm), name).toBe(before);
                }
                form = await openForm(api, fileForm);
                const accepted = await send('e2e-plain.jpg', 'image/jpeg');
                expect(await accepted.text()).not.toContain('has an invalid extension');
            } finally {
                db('set-element', String(fileForm), 'attachment', 'data2', '');
                sh(`rm -f ${UPLOADS}/*e2eplain*`);
            }
        });
    });

    test.describe('the links of a double opt-in', () => {
        let api;
        const TOKEN_OF_THE_MAIL = 'aB3dE5fG7hJ9kL1mN3pQ';
        const link = (kind, id, token) => `index.php?option=com_breezingformsng&${kind}=true&id=${id}&token=${token}`;
        const THANKS = {
            opt_in: 'Thank you for verifying your submission',
            opt_out: 'Thank you for opting out',
        };

        test.beforeAll(async ({ baseURL }) => {
            api = await request.newContext({ baseURL });
        });

        test.afterAll(async () => {
            await api.dispose();
        });

        test('an empty or a wrong token confirms nothing, for a record without a token as for one with a token', async () => {
            const withoutToken = Number(db('opt-record', String(textForm), ''));
            const withToken = Number(db('opt-record', String(textForm), TOKEN_OF_THE_MAIL));
            const pristine = dbJson('opt-state', String(withoutToken));
            const pristineWithToken = dbJson('opt-state', String(withToken));
            expect(pristine).toEqual({ opted: '0', opt_ip: '', opt_date: null, opt_token: '' });

            for (const kind of ['opt_in', 'opt_out']) {
                for (const token of ['', 'wrongwrongwrongwrong', '0', "' OR ''='"]) {
                    for (const id of [withoutToken, withToken]) {
                        const response = await api.get(link(kind, id, encodeURIComponent(token)));
                        const text = await response.text();

                        expect(text, `${kind} ${id} [${token}]`).not.toContain(THANKS[kind]);
                    }
                }
            }
            // A link without any token parameter at all.
            for (const kind of ['opt_in', 'opt_out']) {
                const text = await (await api.get(`index.php?option=com_breezingformsng&${kind}=true&id=${withoutToken}`)).text();
                expect(text).not.toContain(THANKS[kind]);
            }

            expect(dbJson('opt-state', String(withoutToken))).toEqual(pristine);
            expect(dbJson('opt-state', String(withToken))).toEqual(pristineWithToken);
        });

        // The token is compared in SQL with BINARY: the case insensitive collation of the column must not let the
        // token of the mail, lower cased, confirm the record. The tokens hold upper and lower case letters.
        test('a token that differs from the one of the mail by its letter case confirms nothing', async () => {
            const id = Number(db('opt-record', String(textForm), TOKEN_OF_THE_MAIL));

            const text = await (await api.get(link('opt_in', id, TOKEN_OF_THE_MAIL.toLowerCase()))).text();

            expect(text).not.toContain(THANKS.opt_in);
            expect(dbJson('opt-state', String(id)).opted).toBe('0');
        });

        test('the link of the mail confirms the record, and the opt-out link withdraws it', async () => {
            const id = Number(db('opt-record', String(textForm), TOKEN_OF_THE_MAIL));

            const confirmed = await (await api.get(link('opt_in', id, TOKEN_OF_THE_MAIL))).text();

            expect(confirmed).toContain(THANKS.opt_in);
            const state = dbJson('opt-state', String(id));
            expect(state.opted).toBe('1');
            expect(state.opt_ip).not.toBe('');
            expect(state.opt_date).not.toBeNull();

            // Another record's token does not withdraw this one.
            const other = Number(db('opt-record', String(textForm), 'Zz9yX8wV7uT6sR5qP4oN'));
            expect(await (await api.get(link('opt_out', id, 'Zz9yX8wV7uT6sR5qP4oN'))).text()).not.toContain(THANKS.opt_out);
            expect(dbJson('opt-state', String(id)).opted).toBe('1');
            expect(dbJson('opt-state', String(other)).opted).toBe('0');

            const withdrawn = await (await api.get(link('opt_out', id, TOKEN_OF_THE_MAIL))).text();

            expect(withdrawn).toContain(THANKS.opt_out);
            expect(dbJson('opt-state', String(id)).opted).toBe('0');
        });
    });

    test.describe('a package imported by a user who may configure the component but not edit code', () => {
        let page;
        const rules = (code) => JSON.stringify({
            'core.manage': { [MANAGER_GROUP]: 1 },
            'core.edit': { [MANAGER_GROUP]: 1 },
            'core.create': { [MANAGER_GROUP]: 1 },
            'breezingformsng.configuration': { [MANAGER_GROUP]: 1 },
            'breezingformsng.code': { [MANAGER_GROUP]: code },
        });
        const library = (sourceId, name) => ({
            source_id: sourceId,
            data: { published: 1, name, title: name, description: '', type: 'Untyped', code: '// e2e code' },
        });
        const packageWithCode = (kind) => {
            const name = `e2e-${kind}-${String(Date.now()).slice(-7)}`;

            return {
                format: 'breezingformsng-package',
                version: 3,
                package: name,
                scripts: [library(1, `${name}_script`)],
                pieces: [library(1, `${name}_piece`)],
                forms: [{
                    source_id: 1,
                    data: {
                        name, title: name, published: 1, pages: 1,
                        script1cond: 1, script1id: 1, script1code: 'alert("evil")',
                        piece1cond: 1, piece1id: 1, piece1code: '<?php echo "evil";',
                        piece3code: '// piece three', script2code: '// script two',
                    },
                    elements: [],
                }],
            };
        };
        // As a script sends it, without the page: the server alone decides.
        const importAs = async (payload) => {
            await page.goto(`${ADMIN}?option=com_breezingformsng&view=packages&layout=import`);

            return page.evaluate(async (json) => {
                const form = document.getElementById('bf-package-import');
                const data = new FormData(form);
                data.set('package_file', new File([json], 'code.json', { type: 'application/json' }));
                const html = await (await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' })).text();

                return html.match(/"joomla\.messages":(\[.*?\]),"system\.paths"/s)?.[1] ?? '';
            }, JSON.stringify(payload));
        };

        test.beforeAll(async ({ browser }) => {
            db('create-user', MANAGER, PASSWORD, String(MANAGER_GROUP));
            page = await browser.newPage();
            await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
            await login(page, MANAGER, PASSWORD);
        });

        test.afterAll(async () => {
            db('component-rules', '{}');
            await page.close();
        });

        test('the scripts, the pieces and the code of the forms of the package are left out', async () => {
            db('component-rules', rules(0));
            const payload = packageWithCode('nocode');

            const messages = await importAs(payload);

            expect(messages).toContain('has been imported');
            const written = dbJson('package-code', payload.package);
            expect(written.scripts).toBe(0);
            expect(written.pieces).toBe(0);
            // The form itself is imported, without any of its code.
            expect(written.forms).toHaveLength(1);
            // The columns of a new form hold NULL or an empty string: no code either way.
            expect(Object.fromEntries(Object.entries(written.forms[0]).map(([column, value]) => [column, value ?? ''])))
                .toEqual({
                    name: payload.package, script1code: '', script2code: '', piece1code: '', piece2code: '', piece3code: '', piece4code: '',
                });
        });

        test('the same package, imported with the permission to edit code, brings its code', async () => {
            db('component-rules', rules(1));
            const payload = packageWithCode('code');

            const messages = await importAs(payload);

            expect(messages).toContain('has been imported');
            const written = dbJson('package-code', payload.package);
            expect(written.scripts).toBe(1);
            expect(written.pieces).toBe(1);
            expect(written.forms[0]).toEqual(expect.objectContaining({
                script1code: 'alert("evil")', piece1code: '<?php echo "evil";', piece3code: '// piece three', script2code: '// script two',
            }));
        });
    });
});

import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const ADMIN = 'administrator/index.php';

function db(...args) {
    return execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
        input: readFileSync(new URL('./db.php', import.meta.url)),
    }).toString();
}

const formRow = (id) => JSON.parse(db('form', String(id)));

test.describe.configure({ mode: 'serial' });

test.describe('forms', () => {
    let page;
    let formId;
    const problems = [];

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        // The tour of a new account takes the browser back to the dashboard.
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        page.on('pageerror', (error) => {
            // A view transition cut short by leaving the page says nothing about it.
            if (!/Transition was aborted/.test(error.message)) {
                problems.push(`JS: ${error.message} ${(error.stack || '').split('\n').slice(1, 3).join(' ')}`);
            }
        });
        page.on('response', (response) => {
            if (response.status() >= 400 && /com_breezingformsng/.test(response.url())) {
                problems.push(`${response.status()}: ${response.url()}`);
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

    const openQuickMode = async () => {
        await page.goto(`${ADMIN}?option=com_breezingformsng&task=quickmode.display&form=${formId}`);
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
    };

    const saveQuickMode = async () => {
        const reloaded = page.waitForEvent('load', { timeout: 45000 });
        await page.locator('#toolbar-apply').click();
        await reloaded;
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
    };

    const selectNode = async (type) => {
        const id = await page.evaluate((wanted) => [...document.querySelectorAll('#bfElementExplorer li.jstree-node')]
            .map((node) => node.id)
            .find((nodeId) => window.BFQMApp.treeModel.getNodeType(window.BFQMApp.treeModel.find(nodeId)) === wanted), type);
        await page.locator(`[id="${id}"] > a`).click();
        await page.waitForFunction((wanted) => window.BFQMApp.treeModel.getNodeType(window.BFQMApp.selectedTreeElement) === wanted, type);
    };

    const setEditor = (id, value) => page.evaluate(async ([editorId, text]) => {
        const { JoomlaEditor } = await import('editor-api');
        JoomlaEditor.get(editorId).setValue(text);
    }, [id, value]);

    test('a new form is created, with an element as old forms store it', async () => {
        await page.goto(`${ADMIN}?option=com_breezingformsng&task=quickmode.display&form=0`);
        await page.waitForFunction(() => window.BFQMApp && window.BFQMApp.selectedTreeElement);
        await page.fill('#bfFormTitle', 'E2E form');
        await page.fill('#bfFormName', 'e2eform');

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
        await page.locator('#bfElementTypeList [role=option] .bfng-type-icon--bfElementTypeText, #bfElementTypeList .bfng-type-icon--bfElementTypeText').first().click();
        await page.waitForTimeout(300);
        await page.fill('#bfElementLabel', 'First name');
        await page.fill('#bfElementName', 'firstname');
        await selectNode('section');

        // Old forms have no such keys, and PHP then shows notices during the save.
        await page.evaluate(() => {
            const app = window.BFQMApp;
            const elements = [];
            app.getItemsFlattened(app.dataObject, elements);
            elements.forEach((element) => {
                delete element.properties.password;
                delete element.properties.mailback;
                delete element.properties.mailbackAsSender;
            });
        });

        await saveQuickMode();

        formId = Number(new URL(page.url()).searchParams.get('form'));
        expect(formId).toBeGreaterThan(0);
        expect(formRow(formId).title).toBe('E2E form');
    });

    test('each QuickMode save makes a version, shows it and keeps the options', async () => {
        await openQuickMode();
        const before = Number(formRow(formId).version);
        await expect(page.locator('#bfOptionsFieldsWrap #jf_title')).toHaveCount(1);

        for (const step of [1, 2]) {
            await page.fill('#bfFormTitle', `E2E form ${step}`);
            await saveQuickMode();

            const row = formRow(formId);
            expect(Number(row.version)).toBe(before + step);
            expect(row.title).toBe(`E2E form ${step}`);
            await expect(page.locator('h1.page-title')).toContainText(`(version ${before + step})`);
            await expect(page.locator('#bfOptionsFieldsWrap #jf_title')).toHaveCount(1);
        }

        expect(formRow(formId).history).toBeGreaterThan(0);
    });

    for (const [type, link, key] of [
        ['page', 'bfPropertiesIntroModal', 'pageIntro'],
        ['section', 'bfPropertiesDescriptionModal', 'description'],
    ]) {
        test(`the ${type} text editor shows the text and saves it`, async () => {
            await openQuickMode();
            await selectNode(type);
            await page.evaluate((property) => {
                window.BFQMApp.selectedTreeElement.properties[property] = '<p>Stored text</p>';
            }, key);

            await page.locator(`a[href="#${link}"]`).click();
            const frame = page.frameLocator(`#${link} iframe`);
            await expect(frame.locator('input[type=submit]').first()).toBeVisible();
            const content = page.frames().find((candidate) => /quickmode\.editor/.test(candidate.url()));
            await expect.poll(() => content.evaluate(async () => {
                const { JoomlaEditor } = await import('editor-api');
                return JoomlaEditor.get('bfEditor')?.getValue() ?? '';
            })).toContain('Stored text');

            await content.evaluate(async () => {
                const { JoomlaEditor } = await import('editor-api');
                JoomlaEditor.get('bfEditor').setValue('<p>Edited text</p>');
            });
            await frame.locator('input[type=submit]').first().click();

            await expect(page.locator(`#${link}`)).not.toHaveClass(/show/);
            expect(await page.evaluate((property) => window.BFQMApp.selectedTreeElement.properties[property], key)).toContain('Edited text');
        });
    }

    test('the classic options save every editor and only what the screen posts', async () => {
        // A column no option screen shows must survive the save.
        db('set', String(formId), 'prevwidth', '1234');

        await page.goto(`${ADMIN}?option=com_breezingformsng&view=forms&layout=edit&id=${formId}&advanced=1`);
        await page.locator('#tab-scripts').click();
        await page.locator('#s1c2').check();
        await setEditor('jf_script1code', 'function ff_e2eform_init() {}');

        await page.locator('#tab-email').click();
        await page.selectOption('#jf_emaillog', '2');
        await page.selectOption('#jf_emailxml', '3');
        await page.locator('#etype1').check();
        await expect(page.locator('#bfEmailTemplate')).toBeVisible();
        await setEditor('jf_email_custom_template', '<p>Admin body {firstname:value}</p>');

        await page.locator('#tab-email-user').click();
        await page.selectOption('#jf_mb_emaillog', '1');
        await page.selectOption('#jf_mb_emailxml', '4');
        await expect(page.locator('#bfMbEmailDefault pre')).toBeVisible();
        await page.locator('#mbetype1').check();
        await setEditor('jf_mb_email_custom_template', '<p>User body {firstname:value}</p>');

        await Promise.all([page.waitForNavigation(), page.evaluate(() => window.Joomla.submitbutton('forms.save'))]);

        const row = formRow(formId);
        expect(row.script1code).toBe('function ff_e2eform_init() {}');
        expect(row.emaillog).toBe('2');
        expect(row.emailxml).toBe('3');
        expect(row.email_type).toBe('1');
        expect(row.email_custom_template).toContain('Admin body');
        expect(row.mb_emaillog).toBe('1');
        expect(row.mb_emailxml).toBe('4');
        expect(row.mb_email_type).toBe('1');
        expect(row.mb_email_custom_template).toContain('User body');
        expect(row.prevwidth).toBe('1234');
    });

    test('a QuickMode save keeps the email settings', async () => {
        await openQuickMode();
        await saveQuickMode();

        const row = formRow(formId);
        expect(row.email_custom_template).toContain('Admin body');
        expect(row.mb_email_custom_template).toContain('User body');
        expect(row.emaillog).toBe('2');
        expect(row.mb_emailxml).toBe('4');
    });

    test('a submission on the site makes a record', async () => {
        // The notifications stay off the journey.
        db('set', String(formId), 'emailntf', '0');
        db('set', String(formId), 'mb_emailntf', '0');
        db('set', String(formId), 'published', '1');
        const before = formRow(formId).records;

        await page.goto(`index.php?option=com_breezingformsng&ff_form=${formId}`);
        await page.locator('[name="ff_nm_firstname[]"]').fill('Ada');
        await Promise.all([page.waitForNavigation(), page.evaluate(() => window.ff_validate_submit())]);

        expect(formRow(formId).records).toBe(before + 1);
    });
});

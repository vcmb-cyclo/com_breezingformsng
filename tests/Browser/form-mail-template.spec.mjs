import { test, expect } from '@playwright/test';

const MARKUP = `
    <script type="importmap">{"imports": {"editor-api": "data:text/javascript,export const JoomlaEditor = { get: (id) => ({ getValue: () => 'edited', replaceSelection: (value) => { window.inserted.push([id, value]); } }) };"}}</script>
    <script>window.inserted = [];</script>
    <input type="radio" name="mb_email_type" id="default" value="0" data-bf-mail-template-toggle="template" checked>
    <input type="radio" name="mb_email_type" id="custom" value="1" data-bf-mail-template-toggle="template">
    <form id="adminForm"><textarea id="jf_mb_email_custom_template">stale</textarea></form>
    <div id="template" hidden>
        <button type="button" id="field" data-bf-mail-placeholder="{name:value}" data-bf-mail-target="jf_template">{name:value}</button>
    </div>`;

test.describe('form-mail-template.js', () => {
    test.beforeEach(async ({ page }) => {
        await page.setContent(MARKUP);
        await page.addScriptTag({ path: 'media/com_breezingformsng/js/admin/form-mail-template.js', type: 'module' });
        await page.waitForLoadState('networkidle');
    });

    test('shows the template only for a custom email', async ({ page }) => {
        await expect(page.locator('#template')).toBeHidden();
        await page.locator('#custom').check();
        await expect(page.locator('#template')).toBeVisible();
        await page.locator('#default').check();
        await expect(page.locator('#template')).toBeHidden();
    });

    test('inserts the clicked placeholder into the editor', async ({ page }) => {
        await page.locator('#custom').check();
        await page.locator('#field').click();
        await expect.poll(() => page.evaluate(() => window.inserted)).toEqual([['jf_template', '{name:value}']]);
    });

    test('syncs the template before the form is submitted', async ({ page }) => {
        await page.evaluate(() => document.getElementById('adminForm').dispatchEvent(new CustomEvent('breezingformsng:form-submit')));
        await expect(page.locator('#jf_mb_email_custom_template')).toHaveValue('edited');
    });
});

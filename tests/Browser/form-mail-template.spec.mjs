import { test, expect } from '@playwright/test';

const MARKUP = `
    <script type="importmap">{"imports": {"editor-api": "data:text/javascript,export const JoomlaEditor = { get: (id) => (id === 'plain' ? undefined : { getValue: () => 'edited ' + id, replaceSelection: (value) => { window.inserted.push([id, value]); } }) };"}}</script>
    <script>window.inserted = [];</script>
    <input type="radio" name="mb_email_type" id="default" value="0" data-bf-mail-template-toggle="template" data-bf-mail-default-toggle="preview" checked>
    <input type="radio" name="mb_email_type" id="custom" value="1" data-bf-mail-template-toggle="template" data-bf-mail-default-toggle="preview">
    <form id="adminForm"><textarea id="jf_mb_email_custom_template">stale</textarea><textarea id="jf_script1code">stale</textarea><textarea id="plain">kept</textarea></form>
    <div id="preview">Default body</div>
    <div id="template" hidden>
        <button type="button" id="field" data-bf-mail-placeholder="{name:value}" data-bf-mail-target="jf_template">{name:value}</button>
    </div>`;

test.describe('form-mail-template.js', () => {
    test.beforeEach(async ({ page }) => {
        await page.setContent(MARKUP);
        await page.addScriptTag({ path: 'media/com_breezingformsng/js/admin/form-mail-template.js', type: 'module' });
        await page.waitForLoadState('networkidle');
    });

    test('always shows a body: the default preview or the custom template', async ({ page }) => {
        await expect(page.locator('#template')).toBeHidden();
        await expect(page.locator('#preview')).toBeVisible();
        await page.locator('#custom').check();
        await expect(page.locator('#template')).toBeVisible();
        await expect(page.locator('#preview')).toBeHidden();
        await page.locator('#default').check();
        await expect(page.locator('#template')).toBeHidden();
        await expect(page.locator('#preview')).toBeVisible();
    });

    test('inserts the clicked placeholder into the editor', async ({ page }) => {
        await page.locator('#custom').check();
        await page.locator('#field').click();
        await expect.poll(() => page.evaluate(() => window.inserted)).toEqual([['jf_template', '{name:value}']]);
    });

    test('syncs every editor of the form before it is submitted', async ({ page }) => {
        await page.evaluate(() => document.getElementById('adminForm').dispatchEvent(new CustomEvent('breezingformsng:form-submit')));
        await expect(page.locator('#jf_mb_email_custom_template')).toHaveValue('edited jf_mb_email_custom_template');
        await expect(page.locator('#jf_script1code')).toHaveValue('edited jf_script1code');
        await expect(page.locator('#plain')).toHaveValue('kept');
    });
});

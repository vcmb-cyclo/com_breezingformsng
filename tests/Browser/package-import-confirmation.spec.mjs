import { readFileSync } from 'node:fs';
import { test, expect } from '@playwright/test';

const template = readFileSync('administrator/components/com_breezingformsng/tmpl/packages/import.php', 'utf8');
// The script block of our own layout is cut out by position: no HTML is
// parsed or filtered here, so no tag pattern is needed.
const scriptStart = template.indexOf('<script>');
const scriptEnd = template.indexOf('</script>', scriptStart);
if (scriptStart === -1 || scriptEnd === -1) {
    throw new Error('Expected a <script> block in package import template');
}
const script = template.slice(scriptStart + '<script>'.length, scriptEnd)
    .replace("import('joomla.dialog')", 'Promise.resolve({ default: window.testDialog })');

test('confirmation cancels and submits without interpreting the filename as HTML', async ({ page }) => {
    await page.setContent(`
        <joomla-toolbar-button task="packages.import"><button type="button">Import</button></joomla-toolbar-button>
        <form id="bf-package-import"><input id="bf-package-file" type="file"></form>
        <script>
            window.Joomla = { Text: { _: function (key) { return key + ' %s'; } } };
            window.submissions = 0;
            HTMLFormElement.prototype.submit = function () { window.submissions++; };
            // Model Joomla 6.2's inline content and restoration on destroy.
            window.testDialog = { confirm: function (content) {
                return new Promise(function (resolve) {
                    const dialog = document.createElement('div');
                    dialog.id = 'dialog';
                    const parent = content instanceof HTMLElement ? content.parentElement : null;
                    const nextSibling = content instanceof HTMLElement ? content.nextSibling : null;
                    if (content instanceof HTMLElement) { dialog.appendChild(content); }
                    else { dialog.innerHTML = content; }
                    for (const result of [false, true]) {
                        const button = document.createElement('button');
                        button.id = result ? 'confirm' : 'cancel';
                        button.onclick = function () {
                            if (content instanceof HTMLElement) { parent.insertBefore(content, nextSibling); }
                            dialog.remove();
                            resolve(result);
                        };
                        dialog.appendChild(button);
                    }
                    document.body.appendChild(dialog);
                });
            } };
        </script>
    `);
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.addScriptTag({ content: script });
    const filename = 'invalid-<img src=x>.json';
    await page.setInputFiles('#bf-package-file', {
        name: filename,
        mimeType: 'application/json',
        buffer: Buffer.from('{'),
    });
    await expect(page.locator('joomla-toolbar-button button')).toBeEnabled();

    for (const button of ['cancel', 'confirm']) {
        await page.locator('#bf-package-import').evaluate((form) => form.requestSubmit());
        await expect(page.locator('#dialog')).toContainText(filename);
        await expect(page.locator('#dialog img')).toHaveCount(0);
        await page.locator(`#${button}`).click();
        await expect(page.locator('#dialog')).toHaveCount(0);
        await expect.poll(() => page.evaluate(() => window.submissions)).toBe(button === 'confirm' ? 1 : 0);
    }
    expect(errors).toEqual([]);
});

test('changing files clears the old choices and blocks submission until the current preview finishes', async ({ page }) => {
    await page.setContent(`
        <joomla-toolbar-button task="packages.import"><button type="button">Import</button></joomla-toolbar-button>
        <form id="bf-package-import">
            <input id="bf-package-file" name="package_file" type="file">
            <div id="bf-package-selection" hidden><div data-tables></div></div>
        </form>
        <script>
            window.Joomla = { Text: { _: (key) => key }, renderMessages: () => {} };
            window.submissions = 0;
            HTMLFormElement.prototype.submit = function () { window.submissions++; };
            window.previews = [];
            window.fetch = () => new Promise((resolve, reject) => window.previews.push({ resolve, reject }));
        </script>
    `);
    await page.addScriptTag({ content: script });
    const choose = (name, title) => page.setInputFiles('#bf-package-file', {
        name,
        mimeType: 'application/json',
        buffer: Buffer.from(JSON.stringify({ package: name, forms: [{ source_id: 1, data: { name: title, title } }] })),
    });
    const resolve = (index) => page.evaluate((i) => {
        window.previews[i].resolve({ json: async () => ({ success: false }) });
    }, index);
    const button = page.locator('joomla-toolbar-button button');

    await choose('a.json', 'Form A');
    await expect.poll(() => page.evaluate(() => window.previews.length)).toBe(1);
    await resolve(0);
    await expect(page.locator('#bf-package-selection')).toContainText('Form A');
    await expect(button).toBeEnabled();

    await choose('b.json', 'Form B');
    await expect.poll(() => page.evaluate(() => window.previews.length)).toBe(2);
    await expect(button).toBeDisabled();
    await expect(page.locator('#bf-package-selection')).toBeHidden();
    await expect(page.locator('input[name="import_selected"]')).toHaveCount(0);
    await page.locator('#bf-package-import').evaluate((form) => form.requestSubmit());
    expect(await page.evaluate(() => window.submissions)).toBe(0);

    await choose('c.json', 'Form C');
    await expect.poll(() => page.evaluate(() => window.previews.length)).toBe(3);
    await resolve(2);
    await expect(page.locator('#bf-package-selection')).toContainText('Form C');
    await expect(button).toBeEnabled();
    await page.evaluate(() => window.previews[1].reject(new Error('late failure')));
    await expect(page.locator('#bf-package-selection')).toContainText('Form C');
    await expect(page.locator('#bf-import-forms-0')).toBeChecked();
});

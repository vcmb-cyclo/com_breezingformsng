import { readFileSync } from 'node:fs';
import { test, expect } from '@playwright/test';

const template = readFileSync('administrator/components/com_breezingformsng/tmpl/packages/import.php', 'utf8');
const scriptMatch = template.match(/<script\b[^>]*>([\s\S]*?)<\/script\s*>/i);
if (!scriptMatch) {
    throw new Error('Expected a <script> block in package import template');
}
const script = scriptMatch[1]
    .replace("import('joomla.dialog')", 'Promise.resolve({ default: window.testDialog })');

test('confirmation cancels and submits without interpreting the filename as HTML', async ({ page }) => {
    await page.setContent(`
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

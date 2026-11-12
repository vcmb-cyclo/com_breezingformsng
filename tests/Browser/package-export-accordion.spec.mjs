import { readFileSync } from 'node:fs';
import { test, expect } from '@playwright/test';

const template = readFileSync('administrator/components/com_breezingformsng/tmpl/packages/export.php', 'utf8');
const scriptStart = template.indexOf('<script>');
const scriptEnd = template.indexOf('</script>', scriptStart);
if (scriptStart === -1 || scriptEnd === -1) {
    throw new Error('Expected a <script> block in package export template');
}
const script = template.slice(scriptStart + '<script>'.length, scriptEnd)
    .replace(/<\?= json_encode\(\$this->profiles, [\s\S]*?\); \?>/, '{ existing: { assigned: true }, other: { assigned: true } }');

test('new package opens its information and the user can close it', async ({ page }) => {
    await page.setContent(`
        <form id="bf-package-export">
            <select id="bf-package-choice" name="selected_package">
                <option value="">Choose</option><option value="" data-new>New</option>
                <option value="existing">Existing</option><option value="other">Other</option>
            </select>
            <div id="bf-package-new"><input id="bf-package" name="package"></div>
            <div id="bf-package-details"><div id="bf-package-info-content" class="collapse"></div></div>
            <div id="bf-selections"></div><div id="bf-package-audit"></div>
            <div id="bf-package-contents">
                <section data-bf-content-section><span data-bf-count></span>
                    <table><tbody><tr data-bf-package="existing"><td>Form A</td></tr>
                    <tr data-bf-package="other"><td>Form B</td></tr></tbody></table>
                </section>
                <section data-bf-content-section><span data-bf-count></span>
                    <table><tbody><tr data-bf-package="other"><td>Script B</td></tr></tbody></table>
                </section>
            </div>
            ${['name', 'version', 'title', 'author', 'email', 'url', 'description', 'copyright']
                .map((field) => `<input id="bf-meta-${field}" name="metadata[${field}]">`).join('')}
            <input name="task" value="packages.export">
        </form>
        <script>
            window.bootstrap = { Collapse: { getOrCreateInstance: (element) => ({
                show: () => element.classList.add('show')
            }) } };
        </script>
    `);
    await page.addScriptTag({ content: script });
    const info = page.locator('#bf-package-info-content');
    await expect(info).not.toHaveClass(/show/);
    await page.locator('#bf-package-choice').selectOption({ index: 1 });
    await expect(info).toHaveClass(/show/);
    await info.evaluate((element) => element.classList.remove('show'));
    await page.locator('#bf-package-choice').selectOption({ index: 2 });
    await expect(info).not.toHaveClass(/show/);
    await expect(page.locator('#bf-selections')).toHaveClass(/d-none/);
    await expect(page.locator('#bf-package-contents')).not.toHaveClass(/d-none/);
    await expect(page.locator('[data-bf-package="existing"]')).not.toHaveClass(/d-none/);
    await expect(page.locator('[data-bf-package="other"]').first()).toHaveClass(/d-none/);
    await expect(page.locator('[data-bf-content-section]').nth(1)).toHaveClass(/d-none/);
    await page.locator('#bf-package-choice').selectOption({ index: 3 });
    await expect(page.locator('[data-bf-package="existing"]')).toHaveClass(/d-none/);
    await expect(page.locator('[data-bf-package="other"]').first()).not.toHaveClass(/d-none/);
    await expect(page.locator('[data-bf-content-section]').nth(1)).not.toHaveClass(/d-none/);
    await page.locator('#bf-package-choice').selectOption({ index: 1 });
    await expect(info).toHaveClass(/show/);
    await expect(page.locator('#bf-package-contents')).toHaveClass(/d-none/);
    await expect(page.locator('#bf-selections')).not.toHaveClass(/d-none/);
});

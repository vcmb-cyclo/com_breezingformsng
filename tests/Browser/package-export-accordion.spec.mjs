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
            <div id="bf-selections">
                <input type="checkbox" name="forms[]" value="1" data-bf-package="existing">
                <input type="checkbox" name="forms[]" value="2" data-bf-package="other">
            </div><div id="bf-package-audit"></div>
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
    // An existing package preselects its own items, and only them.
    await expect(page.locator('[data-bf-package="existing"]')).toBeChecked();
    await expect(page.locator('[data-bf-package="other"]')).not.toBeChecked();
    await page.locator('#bf-package-choice').selectOption({ index: 3 });
    await expect(page.locator('[data-bf-package="existing"]')).not.toBeChecked();
    await expect(page.locator('[data-bf-package="other"]')).toBeChecked();
    await page.locator('#bf-package-choice').selectOption({ index: 1 });
    await expect(info).toHaveClass(/show/);
    await expect(page.locator('#bf-selections')).not.toHaveClass(/d-none/);
});

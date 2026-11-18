import { readFileSync } from 'node:fs';
import { test, expect } from '@playwright/test';

const script = readFileSync('media/com_breezingformsng/js/admin/about.js', 'utf8');
const css = readFileSync('media/com_breezingformsng/css/about.css', 'utf8');
const template = readFileSync('administrator/components/com_breezingformsng/tmpl/about/audit.php', 'utf8');
// The real dialog of the template, its PHP echoes replaced by labels.
const dialog = template.slice(template.indexOf('<dialog id="bf-orphan-reassign-dialog"'), template.indexOf('</dialog>') + 9)
    .replace(/<\?php echo htmlspecialchars\(Text::_\('([A-Z_]+)'\), ENT_QUOTES, 'UTF-8'\); \?>/g, '$1')
    .replace(/<\?php echo htmlspecialchars\(Route::_\([^)]*\), ENT_QUOTES, 'UTF-8'\); \?>/g, '/record?id=')
    .replace(/<\?php echo Text::_\('([A-Z_]+)'\); \?>/g, '$1');
const preview = {
    success: true,
    message: '',
    data: {
        from: 'Prenom',
        to: 'Prenom',
        truncated: false,
        rows: [
            { id: 1, record: 7, value: 'Jean', current: '', action: 'move' },
            { id: 2, record: 8, value: 'Marie', current: 'Marie', action: 'duplicate' },
            { id: 3, record: 9, value: 'Paul', current: 'Pierre', action: 'taken' },
        ],
    },
};

// Joomla loads the script in the page head, before the dialog exists: the page
// below does the same, so a lookup at load time would silently disable the icon.
const page = (html) => `<!doctype html><html><head><meta charset="utf-8">
    <style>${css}</style>
    <style>.card { position: relative; display: flex; flex-direction: column; } .card-body { flex: 1 1 auto; } .btn-close { display: inline-block; width: 16px; height: 16px; }</style>
    <script>window.Joomla = { getOptions: () => 'token', Text: { _: (key) => key }, renderMessages() {} };</script>
    <script>${script}</script></head><body><form id="adminForm">
    <div id="bf-audit-orphan-messages"></div>
    <div id="bf-audit-orphan-references"><details open data-bf-orphan-check="subrecords_without_element">
        <summary>Values without an element</summary>
        <select id="bf-orphan-target-99001"><option value="0">Choose</option><option value="1512" selected>Prenom (Prénom) #1512</option></select>
        <button type="button" id="preview" data-bf-orphan-preview data-bf-orphan-target-select="bf-orphan-target-99001" data-bf-orphan-element="99001">Reassign</button>
    </details></div></form>${html}</body></html>`;

test('the reassign icon lists the values with a checkbox and sends only the checked ones', async ({ page: browserPage }) => {
    const posts = [];

    await browserPage.route('http://bf.test/administrator/page', (route) => route.fulfill({ contentType: 'text/html', body: page(dialog) }));
    await browserPage.route('http://bf.test/administrator/index.php?**', (route) => {
        const request = route.request();
        posts.push({ url: request.url(), body: request.postData() ?? '' });
        route.fulfill({ contentType: 'application/json', body: JSON.stringify(request.url().includes('previewOrphanReassignment')
            ? preview
            : { success: true, message: '1 value reassigned.', data: { deleted: 1, remaining: 0 } }) });
    });
    await browserPage.route('http://bf.test/administrator/page?refresh', (route) => route.fulfill({ contentType: 'text/html', body: page(dialog) }));
    await browserPage.goto('http://bf.test/administrator/page');

    const modal = browserPage.locator('#bf-orphan-reassign-dialog');
    // Bootstrap's .card sets display and position: it must not sit on the dialog itself, or the
    // closed dialog stays visible and the open one is no longer a centred modal.
    await expect(modal).toBeHidden();
    await browserPage.locator('#preview').click();
    await expect(modal).toBeVisible();
    expect(await modal.evaluate((element) => getComputedStyle(element).position)).toBe('fixed');
    // Stored name and id of the missing element, then the target as shown in the list.
    await expect(modal.locator('[data-bf-reassign-summary]')).toHaveText('Prenom #99001 → Prenom (Prénom) #1512');
    // The title and the summary are on two lines.
    const title = await modal.locator('#bf-orphan-reassign-title').boundingBox();
    const summary = await modal.locator('[data-bf-reassign-summary]').boundingBox();
    expect(summary.y).toBeGreaterThan(title.y + title.height - 2);
    const boxes = modal.locator('[data-bf-reassign-rows] input[type="checkbox"]');
    await expect(boxes).toHaveCount(3);
    await expect(boxes.nth(0)).toBeChecked();
    await expect(boxes.nth(1)).toBeChecked();
    await expect(boxes.nth(2)).not.toBeChecked();
    await expect(boxes.nth(2)).toBeDisabled();
    // The value the target element already holds is shown, so the administrator sees what is "already assigned".
    const cells = modal.locator('[data-bf-reassign-rows] tr:nth-child(3) td');
    await expect(cells.nth(2)).toHaveText('Paul');
    await expect(cells.nth(3)).toHaveText('Pierre');
    await expect(modal.locator('[data-bf-reassign-rows] tr:nth-child(1) td').nth(3)).toHaveText('');
    expect(posts[0].url).toContain('task=about.previewOrphanReassignment');
    expect(posts[0].body).toContain('orphan_element_id=99001');
    expect(posts[0].body).toContain('orphan_target_id=1512');

    await boxes.nth(1).uncheck();
    await modal.locator('[data-bf-reassign-apply]').click();
    await expect.poll(() => posts.length).toBeGreaterThan(1);
    expect(posts[1].url).toContain('task=about.reassignOrphanValuesToElement');
    const body = new URLSearchParams(posts[1].body);
    expect(body.getAll('orphan_values[]')).toEqual(['1']);
    expect(body.get('orphan_target_id')).toBe('1512');
});

test('cancelling the dialog changes nothing', async ({ page: browserPage }) => {
    const posts = [];

    await browserPage.route('http://bf.test/administrator/page', (route) => route.fulfill({ contentType: 'text/html', body: page(dialog) }));
    await browserPage.route('http://bf.test/administrator/index.php?**', (route) => {
        posts.push(route.request().url());
        route.fulfill({ contentType: 'application/json', body: JSON.stringify(preview) });
    });
    await browserPage.goto('http://bf.test/administrator/page');
    await browserPage.locator('#preview').click();
    await expect(browserPage.locator('#bf-orphan-reassign-dialog')).toBeVisible();
    await browserPage.locator('.btn-secondary[data-bf-reassign-cancel]').click();
    await expect(browserPage.locator('#bf-orphan-reassign-dialog')).toBeHidden();
    await expect(browserPage.locator('#bf-orphan-reassign-dialog')).not.toHaveAttribute('open', '');
    expect(posts).toHaveLength(1);
});

test('the cross closes the dialog, and a long list is flagged and scrolls', async ({ page: browserPage }) => {
    const many = {
        success: true,
        message: '',
        data: {
            from: 'Prenom',
            to: 'Prenom',
            truncated: true,
            rows: Array.from({ length: 500 }, (_, index) => ({ id: index + 1, record: index + 1, value: `Value ${index + 1}`, current: '', action: 'move' })),
        },
    };

    await browserPage.route('http://bf.test/administrator/page', (route) => route.fulfill({ contentType: 'text/html', body: page(dialog) }));
    await browserPage.route('http://bf.test/administrator/index.php?**', (route) => route.fulfill({ contentType: 'application/json', body: JSON.stringify(many) }));
    await browserPage.goto('http://bf.test/administrator/page');
    await browserPage.locator('#preview').click();
    const modal = browserPage.locator('#bf-orphan-reassign-dialog');
    await expect(modal).toBeVisible();
    await expect(modal.locator('[data-bf-reassign-rows] tr')).toHaveCount(500);
    await expect(modal.locator('[data-bf-reassign-note]')).not.toBeEmpty();
    // The list scrolls inside the dialog, which stays within the window.
    const box = await modal.boundingBox();
    expect(box.height).toBeLessThanOrEqual(browserPage.viewportSize().height);
    expect(await modal.locator('.bf-orphan-reassign-list').evaluate((element) => getComputedStyle(element).overflowY)).toBe('auto');
    await modal.locator('.btn-close').click();
    await expect(modal).toBeHidden();
});

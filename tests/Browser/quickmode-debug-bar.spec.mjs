import { test, expect } from '@playwright/test';

const markup = `<div class="bfng-debug"><div class="bfng-debug__bar"><span class="bfng-debug__form">Form</span>
    <span class="bfng-debug__help-box">
    <button type="button" class="bfng-debug__help" aria-label="How to hide this bar">?</button>
    <span class="bfng-debug__tooltip" aria-hidden="true">How to hide this bar</span></span></div></div>
    <p style="margin-block-start: 10rem">Outside</p>`;

for (const width of [375, 1200]) {
    test(`the help of the debug bar shows at once, ${width}px wide`, async ({ page }) => {
        await page.setViewportSize({ width, height: 600 });
        await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>${markup}</body></html>`);
        await page.addStyleTag({ path: 'media/com_breezingformsng/css/site/quickmode-debug-bar.css' });
        await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });

        const help = page.locator('.bfng-debug__help');
        const tooltip = page.locator('.bfng-debug__tooltip');
        await expect(tooltip).toBeHidden();

        await help.hover();
        await expect(tooltip).toBeVisible();

        const box = await tooltip.boundingBox();
        expect(box.x).toBeGreaterThanOrEqual(0);
        expect(box.x + box.width).toBeLessThanOrEqual(width);

        // The pointer travels from the button down to the tooltip, which stays.
        await page.mouse.move(box.x + box.width - 5, box.y + box.height - 5, { steps: 20 });
        await expect(tooltip).toBeVisible();

        await page.locator('p').hover();
        await expect(tooltip).toBeHidden();

        await help.click();
        await page.locator('p').hover();
        await expect(tooltip).toBeVisible();

        await page.keyboard.press('Escape');
        await expect(tooltip).toBeHidden();

        await help.focus();
        await expect(tooltip).toBeVisible();
    });
}

for (const shown of ['1', '0']) {
    test(`the technical names follow the checkbox of the bar (${shown})`, async ({ page }) => {
        await page.route('http://bar.test/**', (route) => route.fulfill({ contentType: 'text/html', body: `<!doctype html>
            <div class="bfng-debug" data-bfng-debug-color="auto" data-bfng-debug-names="${shown}">
            <input type="checkbox" data-bfng-debug-names-url="/form${shown === '1' ? '?bf_debug_names=0' : ''}"${shown === '1' ? ' checked' : ''}>
            <table><tr data-bfng-field="Name"></tr></table></div><div class="bfFormDiv"><form><input name="ff_nm_Name[]"></form></div>` }));
        await page.goto(`http://bar.test/form?lang=fr${shown === '0' ? '&bf_debug_names=0' : ''}`);
        await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });
        // The page is already loaded: the script waits for no further event.
        await expect(page.locator('.bfng-debug-field-name')).toHaveCount(Number(shown));

        await page.locator('input[type=checkbox]').click();
        await expect(page).toHaveURL(`http://bar.test/form?lang=fr${shown === '1' ? '&bf_debug_names=0' : ''}`);
    });
}

test('each bar annotates its own form when fields share a name', async ({ page }) => {
    const instance = `<div class="bfng-debug" data-bfng-debug-color="auto">
        <table><tr data-bfng-field="Name"></tr></table></div>
        <div class="bfFormDiv"><form><input name="ff_nm_Name[]"></form></div>`;
    await page.setContent(instance + instance);
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });
    for (const form of await page.locator('form').all()) {
        await expect(form.locator('.bfng-debug-field-name')).toHaveText('Name');
    }
});

test('the tag of a field shows its type icon and its facts below it', async ({ page }) => {
    await page.setContent(`<!doctype html><div class="bfng-debug" id="bfng-debug-1" data-bfng-debug-color="auto">
        <table><thead><tr><th>Name</th><th>Label</th><th>Type</th><th>Required</th><th>Validation</th></tr></thead>
        <tbody><tr data-bfng-field="Mail" data-bfng-id="12" data-bfng-type="Email" data-bfng-type-name="E-mail address">
        <td>Mail</td><td>Your <b>mail</b></td><td>bfTextfield</td><td>Yes</td><td></td></tr></tbody></table></div>
        <div class="bfFormDiv"><form><div class="bfElemWrap"><label>Your mail</label><input name="ff_nm_Mail[]"></div></form></div>
        <p style="margin-block-start: 12rem">Outside</p>`);
    for (const sheet of ['bfng-tokens', 'field-type-icons', 'site/quickmode-debug-bar']) {
        await page.addStyleTag({ path: `media/com_breezingformsng/css/${sheet}.css` });
    }
    // The rule of Cassiopeia for every tooltip.
    await page.addStyleTag({ content: `[role="tooltip"]:not(.show) { max-width: 100%; margin: .5em; color: #000;
        background: #fff; }` });
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });

    const tag = page.locator('.bfng-debug-field-name');
    const tooltip = page.getByRole('tooltip');
    await expect(tag).toHaveText('Mail');
    const icon = await tag.locator('.bfng-debug-field-icon').evaluate((element) => {
        const style = getComputedStyle(element, '::before');
        return [style.maskImage, style.backgroundColor, getComputedStyle(element).backgroundColor];
    });
    expect(icon[0]).toContain('data:image/svg+xml');
    // The e-mail colour of the editor, darkened, straight on the tag.
    expect(icon[1]).toBe('color(srgb 0.400196 0.0586275 0.196275)');
    expect(icon[2]).toBe('rgba(0, 0, 0, 0)');

    await expect(tooltip).toBeHidden();
    await tag.hover();
    await expect(tooltip.locator('span')).toHaveText([
        'Name : Mail (#12)', 'Label : Your mail', 'Type : E-mail address (bfTextfield)', 'Required : Yes',
    ]);
    const box = await tooltip.boundingBox();
    expect(box.y).toBeGreaterThanOrEqual((await tag.boundingBox()).y + 10);
    // As wide as its text, not as the small tag.
    expect(box.width).toBeGreaterThan(150);
    await expect(tooltip).toHaveCSS('background-color', 'rgb(33, 37, 41)');

    await page.locator('p').hover();
    await expect(tooltip).toBeHidden();
    await page.keyboard.press('Tab');
    await tag.focus();
    await expect(tooltip).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(tooltip).toBeHidden();
});

const movable = `<div class="bfng-debug" id="bfng-debug-7" data-bfng-debug-color="auto" data-bfng-form="7"
    data-bfng-save-url="/save" data-bfng-token="tok" data-bfng-revision="rev" data-bfng-move-hint="Drag to move"
    data-bfng-language="zz-ZZ" data-bfng-save-failed="Not saved" data-bfng-pending-text="Unsaved: %d"
    data-bfng-note-label="Version note" data-bfng-save-label="Save" data-bfng-cancel-label="Cancel">
    <table><thead><tr><th>Name</th></tr></thead><tbody>
    <tr data-bfng-field="A" data-bfng-parent="0.0.0"><td>A</td></tr>
    <tr data-bfng-field="B" data-bfng-parent="0.0.0"><td>B</td></tr>
    <tr data-bfng-field="C" data-bfng-parent="0.0.1"><td>C</td></tr></tbody></table></div>
    <div class="bfFormDiv"><form>${['A', 'B', 'C'].map((name) => `<div class="bfElemWrap" id="wrap${name}"
    style="padding: 20px"><label>${name}</label><input name="ff_nm_${name}[]"></div>`).join('')}</form></div>`;

async function openMovable(page, answer) {
    const posts = [];
    await page.route('http://bar.test/**', (route) => {
        if (route.request().method() === 'POST') {
            posts.push(Object.fromEntries(new URLSearchParams(route.request().postData())));
            return route.fulfill(answer);
        }
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html>${movable}` });
    });
    await page.goto('http://bar.test/form');
    await page.addStyleTag({ path: 'media/com_breezingformsng/css/site/quickmode-debug-bar.css' });
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });

    return posts;
}

const tagOf = (page, name) => page.locator('.bfng-debug-field-name', { hasText: name });

const order = (page) => page.locator('.bfElemWrap').evaluateAll((blocks) => blocks.map((block) => block.id));

test('a dragged tag moves its field on the page only, until Save', async ({ page }) => {
    const posts = await openMovable(page, { contentType: 'application/json', body: '{"success":true}' });
    const panel = page.locator('.bfng-debug-pending');

    // Another section takes no drop.
    await tagOf(page, 'A').dragTo(page.locator('#wrapC'));
    await expect(panel).toHaveCount(0);

    await page.locator('input[name="ff_nm_B[]"]').fill('typed');
    await tagOf(page, 'B').dragTo(page.locator('#wrapA'), { targetPosition: { x: 50, y: 5 } });
    await expect(panel.getByRole('status')).toHaveText('Unsaved: 1');
    expect(await order(page)).toEqual(['wrapB', 'wrapA', 'wrapC']);
    expect(posts).toEqual([]);

    await panel.getByRole('button', { name: 'Cancel' }).click();
    await expect(panel).toHaveCount(0);
    expect(await order(page)).toEqual(['wrapA', 'wrapB', 'wrapC']);
    await expect(page.locator('input[name="ff_nm_B[]"]')).toHaveValue('typed');

    await tagOf(page, 'B').dragTo(page.locator('#wrapA'), { targetPosition: { x: 50, y: 5 } });
    // The page reloads without any warning once saved.
    const dialogs = [];
    page.on('dialog', (dialog) => { dialogs.push(dialog.type()); dialog.accept(); });
    const reloaded = page.waitForEvent('load');
    await panel.getByRole('button', { name: 'Save' }).click();
    await reloaded;
    expect(dialogs).toEqual([]);
    expect(posts).toEqual([{ formId: '7', revision: 'rev', tok: '1', language: 'zz-ZZ', note: '', edits: '[]',
        moves: '[{"field":"B","target":"A","position":"before"}]' }]);
});

test('Alt and an arrow pile up moves, and a refusal keeps them', async ({ page }) => {
    const posts = await openMovable(page, {
        status: 409, contentType: 'application/json', body: '{"success":false,"message":"The form has changed."}',
    });
    const panel = page.locator('.bfng-debug-pending');

    // Already last of its section.
    await tagOf(page, 'B').focus();
    await page.keyboard.press('Alt+ArrowDown');
    await expect(panel).toHaveCount(0);

    await tagOf(page, 'A').focus();
    await page.keyboard.press('Alt+ArrowDown');
    await expect(tagOf(page, 'A')).toBeFocused();
    await page.keyboard.press('Alt+ArrowUp');
    await page.keyboard.press('Alt+ArrowDown');
    await expect(panel.getByRole('status')).toHaveText('Unsaved: 3');
    expect(await order(page)).toEqual(['wrapB', 'wrapA', 'wrapC']);
    expect(posts).toEqual([]);

    await panel.getByRole('button', { name: 'Save' }).click();
    await expect(panel.getByRole('alert')).toHaveText('The form has changed.');
    await expect(panel.getByRole('status')).toHaveText('Unsaved: 3');
    expect(JSON.parse(posts[0].moves).map((move) => [move.field, move.target, move.position]))
        .toEqual([['A', 'B', 'after'], ['A', 'B', 'before'], ['A', 'B', 'after']]);

    // Leaving the page now asks first.
    const asked = page.waitForEvent('dialog');
    page.evaluate(() => window.location.reload()).catch(() => {});
    const dialog = await asked;
    expect(dialog.type()).toBe('beforeunload');
    await dialog.dismiss();
});

test('the tag of a group of choices stands above its question', async ({ page }) => {
    await page.setContent(`<!doctype html><div class="bfng-debug" data-bfng-debug-color="auto">
        <table><tr data-bfng-field="Radio"></tr><tr data-bfng-field="Boxes"></tr></table></div>
        <div class="bfFormDiv"><form>
        <div class="bfElemWrap"><fieldset><legend>Your choice</legend>
            <label><input type="radio" name="ff_nm_Radio[]">One</label></fieldset></div>
        <div class="bfElemWrap"><label id="bfOther">Intro</label><span id="bfLabel9">Your boxes</span>
            <label><input type="checkbox" name="ff_nm_Boxes[]">Box</label></div></form></div>`);
    await page.addStyleTag({ path: 'media/com_breezingformsng/css/site/quickmode-debug-bar.css' });
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });

    await expect(page.locator('.bfng-debug-field + fieldset legend')).toHaveText('Your choice');
    await expect(page.locator('.bfng-debug-field + #bfLabel9')).toHaveCount(1);
    const tag = await page.locator('.bfng-debug-field-name').first().boundingBox();
    expect(tag.y + tag.height).toBeLessThanOrEqual((await page.locator('legend').boundingBox()).y + 1);
});

test('the window of a field waits for the bar, which saves everything with a note', async ({ page }) => {
    const texts = { length: 'Length', min: 'Minimum', max: 'Maximum', date: 'Dates', open: 'Click to edit.',
        title: 'Edit the field', label: 'Label', hint: 'Help', required: 'Required', locked: 'Set in the editor.',
        language: '', apply: 'Apply', cancel: 'Cancel' };
    const edit = (values) => JSON.stringify({ label: 'Name', labelDefault: '', hint: '', hintDefault: '',
        required: false, requirable: true, limits: [], ...values }).replaceAll('"', '&quot;');
    const markup = movable
        .replace('data-bfng-save-url="/save"', `data-bfng-save-url="/save"
            data-bfng-edit-texts="${JSON.stringify(texts).replaceAll('"', '&quot;')}"`)
        .replace('data-bfng-field="A"', `data-bfng-field="A" data-bfng-edit="${edit({ limits: [
            { key: 'maxLength', name: 'length', input: 'number', value: '10' },
            { key: 'widgetMin', name: 'min', input: 'date', value: '' }] })}"`)
        .replace('data-bfng-field="B"', `data-bfng-field="B" data-bfng-edit="${edit({ requirable: false })}"`);
    const posts = [];
    let answer = { status: 400, contentType: 'application/json', body: '{"success":false,"message":"A refused."}' };
    await page.route('http://bar.test/**', (route) => {
        if (route.request().method() === 'POST') {
            posts.push(Object.fromEntries(new URLSearchParams(route.request().postData())));
            return route.fulfill(answer);
        }
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html>${markup}` });
    });
    await page.goto('http://bar.test/form');
    await page.addStyleTag({ path: 'media/com_breezingformsng/css/site/quickmode-debug-bar.css' });
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });

    const dialog = page.getByRole('dialog');
    const panel = page.locator('.bfng-debug-pending');
    await tagOf(page, 'B').click();
    await expect(dialog.getByRole('checkbox')).toBeDisabled();
    await expect(dialog).toContainText('Set in the editor.');
    // Applied without any change: nothing to save.
    await dialog.getByRole('button', { name: 'Apply' }).click();
    await expect(dialog).toHaveCount(0);
    await expect(panel).toHaveCount(0);

    await tagOf(page, 'A').focus();
    await page.keyboard.press('Enter');
    await expect(dialog.getByRole('heading')).toHaveText('Edit the field : A');
    await dialog.getByLabel('Label').fill('Last name');
    await dialog.getByLabel('Help').fill('As on your card');
    await dialog.getByLabel('Required').check();
    await expect(dialog.getByLabel('Length')).toHaveValue('10');
    await dialog.getByLabel('Length').fill('40');
    await expect(dialog.getByLabel('Minimum')).toHaveAttribute('placeholder', '2026-12-31 / now');
    await dialog.getByLabel('Minimum').fill('now');
    await dialog.getByRole('button', { name: 'Apply' }).click();
    await expect(tagOf(page, 'A')).toBeFocused();
    await expect(panel.getByRole('status')).toHaveText('Unsaved: 1');
    await expect(page.locator('.bfng-debug-field--changed')).toHaveText(/^A/);
    expect(posts).toEqual([]);

    // The window shows what is waiting; Cancel brings the stored settings back.
    await tagOf(page, 'A').click();
    await expect(dialog.getByLabel('Label')).toHaveValue('Last name');
    await page.keyboard.press('Escape');
    await panel.getByRole('button', { name: 'Cancel' }).click();
    await expect(page.locator('.bfng-debug-field--changed')).toHaveCount(0);
    await tagOf(page, 'A').click();
    await expect(dialog.getByLabel('Label')).toHaveValue('Name');
    await dialog.getByLabel('Label').fill('Last name');
    await dialog.getByRole('button', { name: 'Apply' }).click();

    // A move joins the edit; both are posted together with the note.
    await tagOf(page, 'A').focus();
    await page.keyboard.press('Alt+ArrowDown');
    await expect(panel.getByRole('status')).toHaveText('Unsaved: 2');
    await panel.getByLabel('Version note').fill('New order');
    await panel.getByRole('button', { name: 'Save' }).click();
    await expect(panel.getByRole('alert')).toHaveText('A refused.');
    await expect(panel.getByLabel('Version note')).toHaveValue('New order');

    answer = { contentType: 'application/json', body: '{"success":true}' };
    const reloaded = page.waitForEvent('load');
    await panel.getByLabel('Version note').press('Enter');
    await reloaded;
    expect(posts).toHaveLength(2);
    expect(posts[1]).toEqual({ formId: '7', language: 'zz-ZZ', note: 'New order', revision: 'rev', tok: '1',
        moves: '[{"field":"A","target":"B","position":"after"}]', edits: posts[1].edits });
    expect(JSON.parse(posts[1].edits)).toEqual([{ field: 'A', label: 'Last name', hint: '', required: '0',
        limits: { maxLength: '10', widgetMin: '' } }]);
});

test('theme changes only update the current page from inside its bar', async ({ page }) => {
    const select = `<select data-bfng-debug-theme><option></option><option value="/form?bf_debug_theme=carte">Carte</option>
        <option value="https://elsewhere.test/form">External</option><option value="/other">Other page</option></select>`;
    await page.route('http://bar.test/**', (route) => route.fulfill({ contentType: 'text/html',
        body: select + '<div class="bfng-debug" data-bfng-debug-color="auto">' + select + '</div>' }));
    const current = 'http://bar.test/form?lang=en';
    await page.goto(current);
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-debug-bar.js' });
    await page.locator('select').first().selectOption({ label: 'Carte' });
    await expect(page).toHaveURL(current);
    const theme = page.locator('.bfng-debug select');
    for (const label of ['External', 'Other page']) {
        await theme.selectOption({ label });
        await expect(page).toHaveURL(current);
    }
    await theme.selectOption({ label: 'Carte' });
    await expect(page).toHaveURL(current + '&bf_debug_theme=carte');
});

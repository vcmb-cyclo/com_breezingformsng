import { test, expect } from '@playwright/test';

// WCAG 2.2 contrast of the VCMB QuickMode themes, in a light and a dark host page: 4.5:1 for text (3:1 for large
// text, SC 1.4.3) and 3:1 for the border and the focus indicator of the controls (SC 1.4.11, 2.4.13).
const themes = ['vcmb_j5', 'vcmb_j6'];
const hosts = {
    light: { scheme: 'light', background: '#ffffff', color: '#212529' },
    dark: { scheme: 'dark', background: '#212529', color: '#dee2e6' },
};

const markup = `<div class="bfQuickMode" id="bfQuickModeForm">
    <div class="bfPageIntro">Please fill in the form below.</div>
    <fieldset><legend>Contact</legend>
        <section class="bfElemWrap"><label for="name">Name <span class="bfRequired">*</span></label>
            <input id="name" type="text" value="Jane"></section>
        <section class="bfElemWrap bfLabelLeft" id="leftRow"><label for="mail">Email</label>
            <input id="mail" type="email" value="jane@">
            <small class="bfHint">We will send your confirmation here</small>
            <span class="bfErrorMessage">Please enter a valid email address</span></section>
        <section class="bfElemWrap"><label for="msg">Message</label><textarea id="msg">Hello</textarea></section>
        <section class="bfElemWrap"><label for="pick">Choice</label>
            <select id="pick"><option>One</option><option>Two</option></select></section>
        <fieldset class="bfInline"><legend>Options</legend>
            <section class="bfElemWrap"><label for="opt">Option</label><input id="opt" type="text"></section>
        </fieldset>
        <button type="button" class="bfPrevButton">Previous</button>
        <button type="submit" class="bfSubmitButton">Send</button>
    </fieldset>
</div>`;

const measure = () => {
    const parse = (value) => {
        const channels = value.match(/[\d.]+/g)?.map(Number) ?? [];
        if (value.startsWith('color(')) return { rgb: channels.slice(0, 3).map((channel) => channel * 255), alpha: channels[3] ?? 1 };
        return { rgb: channels.slice(0, 3), alpha: channels[3] ?? 1 };
    };
    const luminance = ([r, g, b]) => {
        const [lr, lg, lb] = [r, g, b].map((channel) => {
            const value = channel / 255;
            return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
        });
        return 0.2126 * lr + 0.7152 * lg + 0.0722 * lb;
    };
    const ratio = (a, b) => {
        const [high, low] = [luminance(a), luminance(b)].sort((x, y) => y - x);
        return (high + 0.05) / (low + 0.05);
    };
    const paint = (element) => {
        for (let current = element; current; current = current.parentElement) {
            const { rgb, alpha } = parse(getComputedStyle(current).backgroundColor);
            if (rgb.length === 3 && alpha > 0.95) return rgb;
        }
        return [255, 255, 255];
    };
    const name = (element) => `${element.tagName.toLowerCase()}${element.className ? `.${element.className}` : ''}`
        + ` "${(element.innerText || element.value || element.id || '').trim().slice(0, 24)}"`;
    const failures = [];
    const check = (label, element, foreground, background, minimum) => {
        const value = ratio(foreground, background);
        if (value < minimum) failures.push(`${label} ${name(element)}: ${value.toFixed(2)} < ${minimum}`);
    };

    document.querySelectorAll('#bfQuickModeForm :is(legend, label, .bfPageIntro, .bfRequired, .bfErrorMessage, .bfHint, button, input, textarea, select)')
        .forEach((element) => {
            const style = getComputedStyle(element);
            const size = parseFloat(style.fontSize);
            const large = size >= 24 || (size >= 18.66 && Number(style.fontWeight) >= 700);
            const background = element.matches('input, textarea, select, button') && parse(style.backgroundColor).alpha > 0.95
                ? parse(style.backgroundColor).rgb
                : paint(element);
            check('text', element, parse(style.color).rgb, background, large ? 3 : 4.5);

            if (element.matches('input[type=text], input[type=email], textarea, select')) {
                const border = parse(style.borderTopColor).rgb;
                check('border', element, border, paint(element.parentElement), 3);
            }
        });

    return failures;
};

const focusFailures = () => {
    const parse = (value) => (value.match(/[\d.]+/g) ?? []).slice(0, 3).map(Number);
    const luminance = ([r, g, b]) => {
        const [lr, lg, lb] = [r, g, b].map((channel) => {
            const value = channel / 255;
            return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
        });
        return 0.2126 * lr + 0.7152 * lg + 0.0722 * lb;
    };
    const paint = (element) => {
        for (let current = element; current; current = current.parentElement) {
            const channels = getComputedStyle(current).backgroundColor.match(/[\d.]+/g)?.map(Number) ?? [];
            if (channels.length === 3 || (channels.length === 4 && channels[3] > 0.95)) return channels.slice(0, 3);
        }
        return [255, 255, 255];
    };
    const failures = [];

    document.querySelectorAll('#bfQuickModeForm :is(input, textarea, select, button)').forEach((element) => {
        element.focus({ focusVisible: true });
        const style = getComputedStyle(element);

        if (style.outlineStyle === 'none' || parseFloat(style.outlineWidth) < 2) {
            failures.push(`focus ${element.tagName.toLowerCase()}.${element.className}: no outline of at least 2px`);

            return;
        }

        // An outline drawn inside the control (negative offset) sits on the control itself, not on its surroundings.
        const behind = parseFloat(style.outlineOffset) < 0 ? paint(element) : paint(element.parentElement);
        const [high, low] = [luminance(parse(style.outlineColor)), luminance(behind)].sort((x, y) => y - x);
        const value = (high + 0.05) / (low + 0.05);

        if (value < 3) failures.push(`focus ${element.tagName.toLowerCase()}.${element.className}: ${value.toFixed(2)} < 3`);
    });

    return failures;
};

for (const theme of themes) {
    for (const [hostName, host] of Object.entries(hosts)) {
        test(`${theme} keeps WCAG contrast in a ${hostName} page`, async ({ page }) => {
            await page.emulateMedia({ colorScheme: host.scheme });
            await page.setContent(`<!doctype html><html data-bs-theme="${host.scheme}"><head><meta charset="utf-8">
                <style>body { margin: 1rem; background: ${host.background}; color: ${host.color}; }</style></head>
                <body>${markup}</body></html>`);
            await page.addStyleTag({ path: `media/breezingforms/themes/${theme}/theme.css` });

            expect([...await page.evaluate(measure), ...await page.evaluate(focusFailures)]).toEqual([]);
        });
    }
}

test('vcmb_j6 puts the label close to its field, on the same line, with the help visible under the field', async ({ page }) => {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"><style>body { margin: 1rem; }</style></head>
        <body>${markup}</body></html>`);
    await page.addStyleTag({ path: 'media/breezingforms/themes/vcmb_j6/theme.css' });

    const row = await page.evaluate(() => {
        const box = (selector) => document.querySelector(selector).getBoundingClientRect();
        const section = box('#leftRow');
        const label = box('#leftRow label');
        const field = box('#leftRow input');
        const hint = box('#leftRow .bfHint');

        return {
            labelShare: label.width / section.width,
            gap: field.left - label.right,
            fieldShare: field.width / section.width,
            centresApart: Math.abs((label.top + label.height / 2) - (field.top + field.height / 2)),
            hintBelow: hint.top >= field.bottom,
            hintAligned: Math.abs(hint.left - field.left) < 1,
        };
    });

    expect(row.labelShare).toBeLessThan(0.3);
    expect(row.gap).toBeLessThan(40);
    expect(row.fieldShare).toBeGreaterThan(0.65);
    expect(row.centresApart).toBeLessThan(4);
    expect(row.hintBelow).toBe(true);
    expect(row.hintAligned).toBe(true);

    // The active field gets a soft ring in the blue, never a highlighted background.
    await page.focus('#name');
    const focused = await page.evaluate(() => {
        const style = getComputedStyle(document.querySelector('#name'));

        return { shadow: style.boxShadow, background: style.backgroundColor };
    });
    expect(focused.shadow).not.toBe('none');
    expect(focused.background).toBe('rgb(255, 255, 255)');
});

test('vcmb_j6 draws the fieldsets in light grey and keeps the blue for their title', async ({ page }) => {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>${markup}</body></html>`);
    await page.addStyleTag({ path: 'media/breezingforms/themes/vcmb_j6/theme.css' });

    const colours = await page.evaluate(() => {
        const fieldset = getComputedStyle(document.querySelector('#bfQuickModeForm > fieldset'));
        const legend = getComputedStyle(document.querySelector('#bfQuickModeForm > fieldset > legend'));

        return { border: fieldset.borderTopColor, width: fieldset.borderTopWidth, legend: legend.color };
    });
    expect(colours.width).toBe('1px');
    expect(colours.border).toBe('rgb(213, 221, 229)');
    expect(colours.legend).toBe('rgb(23, 74, 120)');
});

test('vcmb_j6 lays out the rows the form script shows, keeps hidden ones hidden, and does not stretch checkboxes', async ({ page }) => {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>
        <div class="bfQuickMode"><fieldset>
            <section id="shown" class="bfElemWrap bfLabelLeft" style="display: block;">
                <label>Participants</label><span class="ff_elem bfSummarize" style="display: table-cell">4</span></section>
            <section id="hidden" class="bfElemWrap bfLabelLeft" style="display:none">
                <label>Secret</label><input type="text"></section>
            <section id="hidden2" class="bfElemWrap bfLabelLeft" style="display: none;">
                <label>Secret</label><input type="text"></section>
            <section id="check" class="bfElemWrap bfLabelLeft" style="display: block;">
                <label for="agree">I accept the registration terms and the cancellation policy</label>
                <input id="agree" type="checkbox"><small class="bfHint">The seat is confirmed by email</small></section>
        </fieldset></div></body></html>`);
    await page.addStyleTag({ path: 'media/breezingforms/themes/vcmb_j6/theme.css' });

    const state = await page.evaluate(() => {
        const box = (selector) => document.querySelector(selector).getBoundingClientRect();
        const display = (selector) => getComputedStyle(document.querySelector(selector)).display;

        return {
            shown: display('#shown'),
            hidden: [display('#hidden'), display('#hidden2')],
            valueBesideLabel: Math.abs(box('#shown .bfSummarize').top - box('#shown label').top) < 12
                && box('#shown .bfSummarize').left > box('#shown label').right,
            checkboxWidth: box('#agree').width,
            hintUnderCheckbox: Math.abs(box('#check .bfHint').left - box('#agree').left) < 1,
        };
    });

    expect(state.shown).toBe('grid');
    expect(state.hidden).toEqual(['none', 'none']);
    expect(state.valueBesideLabel).toBe(true);
    expect(state.checkboxWidth).toBeLessThan(30);
    expect(state.hintUnderCheckbox).toBe(true);
});

test('vcmb_j6 keeps the phone layout and ignores the rollover colour of the form', async ({ page }) => {
    await page.setViewportSize({ width: 500, height: 800 });
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>
        <div class="bfQuickMode"><fieldset>
            <section id="plain" class="bfElemWrap bfLabelLeft"><label for="a">Last name</label><input id="a" type="text"></section>
            <section id="active" class="bfElemWrap bfLabelLeft bfRolloverBg" style="background: rgb(255, 255, 204);">
                <label for="b">First name</label><input id="b" type="text"></section>
            <section id="blurred" class="bfElemWrap bfLabelLeft" style="background: rgba(0, 0, 0, 0);">
                <label for="c">Email</label><input id="c" type="text"></section>
        </fieldset></div></body></html>`);
    await page.addStyleTag({ path: 'media/breezingforms/themes/vcmb_j6/theme.css' });

    const rows = await page.evaluate(() => ['plain', 'active', 'blurred'].map((id) => {
        const section = document.getElementById(id).getBoundingClientRect();
        const label = document.querySelector(`#${id} label`).getBoundingClientRect();
        const field = document.querySelector(`#${id} input`).getBoundingClientRect();

        return {
            labelAbove: label.bottom <= field.top + 1,
            fieldShare: field.width / section.width,
            background: getComputedStyle(document.getElementById(id)).backgroundColor,
        };
    }));

    // Same layout whatever the inline style the form script left on the row: label above, field on the full width.
    rows.forEach((row) => {
        expect(row.labelAbove).toBe(true);
        expect(row.fieldShare).toBeGreaterThan(0.9);
        expect(row.background).toBe('rgba(0, 0, 0, 0)');
    });
});

import { test, expect } from '@playwright/test';
import { readdirSync } from 'node:fs';

// Classic themes style every button of the form: the buttons of the phone widget must keep their own look.
const themes = readdirSync('media/breezingforms/themes', { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name);
const library = 'media/com_breezingformsng/libraries/intl-tel-input';

const measure = async (page, theme) => {
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8"></head><body>
        <div class="bfFormDiv" data-bf-theme="${theme}"><form class="bfQuickMode">
            <section class="bfElemWrap bfLabelTop"><label for="phone">Phone</label><input id="phone" type="tel"></section>
        </form></div></body></html>`);

    const styles = ['components/com_breezingformsng/themes/quickmode/system.css', 'media/com_breezingformsng/css/bfng-widgets.css',
        `${library}/css/intlTelInput.min.css`];

    if (theme) {
        styles.push(`media/breezingforms/themes/${theme}/theme.css`);
    }

    for (const path of styles) {
        await page.addStyleTag({ path });
    }

    await page.addScriptTag({ path: `${library}/js/intlTelInputWithUtils.min.js` });
    await page.evaluate(() => window.intlTelInput(document.querySelector('#phone'), { initialCountry: 'fr', separateDialCode: true }));

    return page.evaluate(() => {
        const country = document.querySelector('.iti__selected-country');

        return [country, ...country.querySelectorAll('*')].map((node) => {
            const style = getComputedStyle(node);
            const box = node.getBoundingClientRect();

            return {
                node: node.className,
                background: style.backgroundImage.replace(/.*\//, '').slice(0, 20),
                color: style.backgroundColor,
                border: style.borderTopWidth,
                padding: style.padding,
                margin: style.margin,
                width: Math.round(box.width),
                height: Math.round(box.height),
            };
        });
    });
};

for (const theme of themes) {
    test(`the country button of the phone field keeps its look with ${theme}`, async ({ page }) => {
        const reference = await measure(page, '');

        const themed = await measure(page, theme);

        // VCMB J6 gives every button the height of its fields, the country button included.
        const ignored = theme === 'vcmb_j6' ? ['height'] : [];
        const comparable = (parts) => parts.map((part) => Object.fromEntries(
            Object.entries(part).filter(([property]) => !ignored.includes(property))));

        expect(comparable(themed)).toEqual(comparable(reference));
    });
}

import { test, expect } from '@playwright/test';

for (const script of ['quickmode-calendar-responsive.js', 'quickmode-calendar-responsive-legacy-style.js']) {
    test(`${script} watches year and month independently`, async ({ page }) => {
        await page.setContent(`
            <div class="bfCalendarResponsiveContainertest">
                <select class="picker__select--year"><option>2024</option><option selected>2025</option><option>2026</option></select>
                <select class="picker__select--month"><option selected>1</option><option>2</option></select>
            </div>
        `);
        await page.evaluate(() => {
            window.bfPickerMinusYearIcon = 'minus.png';
            window.bfPickerPlusYearIcon = 'plus.png';
            window.timers = new Map();
            let nextTimer = 0;
            window.setInterval = (callback) => {
                const id = ++nextTimer;
                window.timers.set(id, callback);
                return id;
            };
            window.clearInterval = (id) => window.timers.delete(id);
            window.JQuery = (selector) => {
                const elements = document.querySelectorAll(selector);
                return {
                    length: elements.length,
                    get: (index) => elements[index],
                    before: (html) => elements[0].insertAdjacentHTML('beforebegin', html),
                    after: (html) => elements[0].insertAdjacentHTML('afterend', html),
                    on: (event, handler) => elements[0].addEventListener(event, handler),
                    val: () => elements[0].value,
                };
            };
        });
        await page.addScriptTag({ path: `media/com_breezingformsng/js/site/${script}` });

        const result = await page.evaluate(() => {
            window.bf_add_yearscroller('test');
            const tick = () => [...window.timers.values()].forEach((callback) => callback());
            const remaining = () => [...window.timers.keys()];
            tick();
            const unchanged = remaining();
            document.querySelector('.picker__select--year').value = '2026';
            tick();
            const afterYear = remaining();
            document.querySelector('.picker__select--month').value = '2';
            tick();
            return { unchanged, afterYear, afterMonth: remaining() };
        });

        expect(result).toEqual({ unchanged: [1, 2], afterYear: [2], afterMonth: [] });
    });
}

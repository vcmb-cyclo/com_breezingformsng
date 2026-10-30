import { test, expect } from '@playwright/test';

const MARKUP = `<!DOCTYPE html>
    <div id="subhead-container" style="height: 90px"></div>
    <div id="bfQuickModeWrapper">
        <input type="checkbox" id="triggerScrollable">
        <input type="checkbox" id="scrollElementList">
    </div>`;

test.describe('jquery.scroll.js', () => {
    const open = async (page) => {
        await page.goto('http://bfng.test/');
        await page.addScriptTag({ path: 'administrator/components/com_breezingformsng/libraries/jquery/jquery.scroll.js' });
    };

    test.beforeEach(async ({ page }) => {
        await page.route('http://bfng.test/', (route) => route.fulfill({ contentType: 'text/html', body: MARKUP }));
        await open(page);
    });

    test('applies each switch to its column', async ({ page }) => {
        const wrapper = page.locator('#bfQuickModeWrapper');

        await page.locator('#triggerScrollable').check();
        await expect(wrapper).toHaveClass('bfng-split--docked');
        await page.locator('#scrollElementList').check();
        await expect(wrapper).toHaveClass('bfng-split--docked bfng-split--tree-scroll');
        await page.locator('#triggerScrollable').uncheck();
        await expect(wrapper).toHaveClass('bfng-split--tree-scroll');
    });

    test('keeps the switches after a reload', async ({ page }) => {
        await page.locator('#scrollElementList').check();
        await open(page);

        await expect(page.locator('#scrollElementList')).toBeChecked();
        await expect(page.locator('#triggerScrollable')).not.toBeChecked();
        await expect(page.locator('#bfQuickModeWrapper')).toHaveClass('bfng-split--tree-scroll');
    });

    test('docks under the toolbar', async ({ page }) => {
        await expect.poll(() => page.locator('#bfQuickModeWrapper').evaluate((el) => el.style.getPropertyValue('--bfng-dock-top'))).toBe('90px');
    });
});

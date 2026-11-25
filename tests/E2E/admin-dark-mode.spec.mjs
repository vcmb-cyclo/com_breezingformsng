import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './login.mjs';

const ADMIN = 'administrator/index.php';

async function darkThemeIssues(page) {
    await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'dark');
    await expect(page.locator('html')).toHaveAttribute('data-color-scheme', 'dark');
    return page.evaluate(() => {
        const visible = (element) => {
            const rect = element.getBoundingClientRect();
            const style = getComputedStyle(element);
            return rect.width >= 24 && rect.height >= 14
                && style.display !== 'none' && style.visibility !== 'hidden'
                && Number(style.opacity) > 0;
        };
        const rgbLuminance = (color) => {
            const channels = color.match(/[\d.]+/g)?.slice(0, 3).map(Number);
            if (!channels || channels.length !== 3) return 0;
            const [r, g, b] = channels.map((channel) => {
                const value = channel / 255;
                return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
            });
            return 0.2126 * r + 0.7152 * g + 0.0722 * b;
        };
        const paintedBackground = (element) => {
            for (let current = element; current; current = current.parentElement) {
                const background = getComputedStyle(current).backgroundColor;
                const values = background.match(/[\d.]+/g)?.map(Number);
                if (values?.length === 4 && values[3] < 0.95) continue;
                if (values?.length === 3 || values?.length === 4) return { color: background, owner: current };
            }
            return { color: 'rgb(0, 0, 0)', owner: element };
        };
        const describe = (element) => {
            const text = (element.innerText || element.value || element.getAttribute('aria-label') || element.tagName)
                .trim().replace(/\s+/g, ' ').slice(0, 70);
            const classes = typeof element.className === 'string' ? element.className.trim() : '';
            const foreground = getComputedStyle(element).color;
            const background = paintedBackground(element).color;
            return `${element.tagName.toLowerCase()}${classes ? `.${classes.replace(/\s+/g, '.')}` : ''}`
                + ` (text ${foreground}, background ${background}): ${text}`;
        };

        return [...document.querySelectorAll('main *')]
            .filter((element) => visible(element)
                && ((element.children.length === 0 && element.innerText.trim())
                    || element.matches('input, select, textarea, button')))
            .flatMap((element) => {
                const foreground = getComputedStyle(element).color;
                const surface = paintedBackground(element);
                const lightSurface = rgbLuminance(surface.color) >= 0.78;
                const lightTextOnLightSurface = rgbLuminance(foreground) >= 0.72;
                const largeLightSurface = surface.owner.getBoundingClientRect().width >= 120
                    && surface.owner.getBoundingClientRect().height >= 28
                    && !surface.owner.matches('button, .btn, .badge, .alert');
                if (!lightSurface) return [];
                if (lightTextOnLightSurface) return ['Light text on light surface: ' + describe(element)];
                return largeLightSurface ? ['Large light surface: ' + describe(element)] : [];
            });
    });
}

test('administrator screens use dark surfaces with readable text in dark mode', async ({ page, baseURL }) => {
    test.setTimeout(180000);
    await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
    await page.context().addCookies([{ name: 'userColorScheme', value: 'dark', url: baseURL }]);
    await page.goto(ADMIN);
    await loginAsAdmin(page);
    await page.goto(`${ADMIN}?option=com_breezingformsng`);

    // Start with every screen linked from the component's own administrator menu.
    const routes = await page.locator('a[href*="option=com_breezingformsng"]:visible').evaluateAll((links) => {
        const origin = location.origin;
        return [...new Set(links.map((link) => new URL(link.href, location.href)))]
            .filter((url) => url.origin === origin
                && url.pathname.endsWith('/administrator/index.php')
                && (url.searchParams.has('view') || url.searchParams.get('task') === 'quickmode.display'))
            .map((url) => `${url.pathname}${url.search}`);
    });
    expect(routes.length, 'component menu routes should be discoverable').toBeGreaterThan(0);

    // Include common edit paths reached from list screens when rows are present.
    const visited = new Set();
    const failures = [];
    for (const route of routes) {
        if (visited.has(route)) continue;
        visited.add(route);
        await page.goto(route, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('main'), `administrator screen did not load: ${route}`).toBeVisible();

        const issues = await darkThemeIssues(page);
        if (issues.length) failures.push(`${route}\n  ${issues.join('\n  ')}`);

        const editRoutes = await page.locator('main a[href*="option=com_breezingformsng"]:visible').evaluateAll((links) => links
            .map((link) => new URL(link.href, location.href))
            .filter((url) => url.origin === location.origin
                && url.pathname.endsWith('/administrator/index.php')
                && /(?:layout=edit|task=quickmode\.display)/.test(url.search))
            .map((url) => `${url.pathname}${url.search}`));
        for (const editRoute of editRoutes) {
            if (visited.has(editRoute)) continue;
            visited.add(editRoute);
            await page.goto(editRoute, { waitUntil: 'domcontentloaded' });
            await expect(page.locator('main'), `administrator screen did not load: ${editRoute}`).toBeVisible();
            const editIssues = await darkThemeIssues(page);
            if (editIssues.length) failures.push(`${editRoute}\n  ${editIssues.join('\n  ')}`);
        }
    }

    expect(failures, `Dark theme issues:\n${failures.join('\n')}`).toEqual([]);
});

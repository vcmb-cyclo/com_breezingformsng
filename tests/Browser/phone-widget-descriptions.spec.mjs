import { expect, test } from '@playwright/test';

test('phone validation keeps the existing descriptions and removes only its error', async ({ page }) => {
    const assets = 'media/com_breezingformsng';
    await page.route('http://localhost/', (route) => route.fulfill({
        contentType: 'text/html; charset=utf-8',
        body: `<html lang="en"><body>
          <label for="phone">Phone</label>
          <input type="tel" name="phone" id="phone" data-bfng-widget="phone"
            data-bfng-options='{"defaultCountry":"fr"}' aria-describedby="hint format">
          <small id="hint">Call back number.</small><small id="format">Include area code.</small>
          <script src="/${assets}/js/site/bfng-widgets.js"></script>
        </body></html>`,
    }));
    await page.route('**/media/com_breezingformsng/**', (route) =>
        route.fulfill({ path: new URL(route.request().url()).pathname.slice(1) }));
    await page.goto('http://localhost/');
    const phone = page.locator('#phone_tel');
    const hint = 'Call back number. Include area code.';
    await expect(phone).toHaveAccessibleDescription(hint);
    await phone.fill('1');
    await phone.blur();
    await expect(phone).toHaveAccessibleDescription(hint + ' Enter a valid phone number.');
    await phone.fill('0612345678');
    await phone.blur();
    await expect(phone).toHaveAccessibleDescription(hint);
    await expect(phone).toHaveAttribute('aria-invalid', 'false');
});

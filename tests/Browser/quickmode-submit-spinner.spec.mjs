import { test, expect } from '@playwright/test';

const SCRIPTS = 'media/com_breezingformsng/js/site/';

// The OnePage submit button with an upload field: its onclick runs
// bfDoFlashUpload(), which calls bfSubmitSpinner.stop() at once when the
// validation fails.
test.beforeEach(async ({ page }) => {
    await page.setContent(`
        <script>
            var JQuery = function () { var o = { css: function () { return o; }, html: function () { return o; } }; return o; };
            var error = 'Required field missing';
            var validationResult = 'Required field missing';
            function ff_validation() { return validationResult; }
            function ff_validationFocus() {}
            window.alert = function () {};
        </script>
        <button type="button" id="bfSubmitButton" onclick="this.disabled=true;bfDoFlashUpload();"><span>Submit</span></button>
    `);
    await page.addScriptTag({ path: SCRIPTS + 'quickmode-submit-spinner.js' });
    await page.addScriptTag({ path: SCRIPTS + 'quickmode-flash-upload-onepage.js' });
    await page.evaluate(() => bfSubmitSpinner.bind('#bfSubmitButton'));
});

test('a refused upload validation leaves the button usable', async ({ page }) => {
    const button = page.locator('#bfSubmitButton');

    for (let attempt = 0; attempt < 2; attempt++) {
        await button.click();

        await expect(button).toBeEnabled();
        await expect(button).not.toHaveAttribute('aria-busy');
        await expect(button.locator('.bf-submit-spinner')).toHaveCount(0);
    }
});

test('an accepted validation keeps the spinner while uploading', async ({ page }) => {
    const button = page.locator('#bfSubmitButton');

    await page.evaluate(() => { validationResult = ''; });
    await button.click();

    await expect(button).toBeDisabled();
    await expect(button).toHaveAttribute('aria-busy', 'true');
    await expect(button.locator('.bf-submit-spinner')).toHaveCount(1);
});

import { test, expect } from '@playwright/test';
import { createHash } from 'node:crypto';

// The widget finds the secret number of the challenge and puts "salt:number" in the field.
for (const [salt, number] of [['0123456789abcdef0123456789abcdef', 0], ['f'.repeat(32), 4321], ['a1'.repeat(16), 59999]]) {
    test(`solves the challenge whose number is ${number}`, async ({ page }) => {
        const options = {
            salt, target: createHash('sha256').update(salt + number).digest('hex'), max: 60000,
            working: 'Checking…', done: 'Passed',
        };
        await page.setContent(`<div class="bfFormDiv"><form><input type="text" name="ff_nm_Robot[]" data-bfng-widget="robot"
            data-bfng-options='${JSON.stringify(options)}'><button type="reset">Reset</button></form></div>`);
        await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/bfng-widgets.js' });

        await expect(page.getByRole('status')).toHaveText('Passed', { timeout: 20000 });
        await expect(page.locator('input')).toHaveValue(`${salt}:${number}`);
        await page.getByRole('button', { name: 'Reset' }).click();
        await expect(page.locator('input')).toHaveValue(`${salt}:${number}`);
    });
}

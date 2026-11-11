import { execFileSync } from 'node:child_process';
import { test, expect } from '@playwright/test';

test('upload validation identifies the file, limit and allowed extensions as text', async ({ page }) => {
    const php = String.raw`require 'components/com_breezingformsng/src/Service/Rendering/QuickMode/QuickModeUploadValidationScriptBuilder.php'; echo Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeUploadValidationScriptBuilder::build(4, 'jpg,png', 'File %1$s exceeds %2$s bytes', 'File %1$s: allowed %2$s', "\n");`;
    const script = execFileSync('php', ['-r', php], { encoding: 'utf8' });

    const messages = await page.evaluate((generated) => {
        const alerts = [];
        const validate = new Function('files', 'i', 'JQuery', 'bfUploadImageThumb', 'alerts', `
            var error = false;
            var bfFlashUploadersLength = 0;
            var alert = (message) => alerts.push(message);
            ${generated}
        `);
        const files = [{ id: 'first', name: 'C:\\fakepath\\a%2$s<img>.exe', size: 5 }];
        validate(files, 0, () => ({ remove() {} }), () => {}, alerts);
        return alerts;
    }, script);

    expect(messages).toEqual([
        'File a%2$s<img>.exe exceeds 4 bytes',
        'File a%2$s<img>.exe: allowed jpg, png',
    ]);
});

test('standard file validation escapes filenames before showing an inline error', async ({ page }) => {
    const php = String.raw`require 'components/com_breezingformsng/src/Service/Rendering/FileExtensionsCheckBuilder.php'; $row = (object) ['id'=>5, 'type'=>'File Upload', 'data2'=>'jpg,png', 'page'=>1]; [$script] = (new Vcmb\Component\BreezingformsNG\Site\Service\Rendering\FileExtensionsCheckBuilder())->build([$row], 1, json_encode('File %1$s: allowed %2$s'), true); echo $script;`;
    const script = execFileSync('php', ['-r', php], { encoding: 'utf8' });
    await page.setContent('<input id="ff_elem5"><div id="error"></div>');
    await page.locator('#ff_elem5').fill('C:\\fakepath\\<img src=x>.exe');
    const result = await page.evaluate((generated) => {
        window.bfUseErrorAlerts = true;
        window.ff_currentpage = 1;
        window.bfShowErrors = (message) => { document.querySelector('#error').innerHTML = message; };
        return new Function(`${generated}; return checkFileExtensions();`)();
    }, script);

    expect(result).toBe(false);
    await expect(page.locator('#error')).toHaveText('File <img src=x>.exe: allowed jpg, png');
    await expect(page.locator('#error img')).toHaveCount(0);
});

import { test, expect } from '@playwright/test';

test('Bootstrap validation marks fields, gives each one a message and links the summary', async ({ page }) => {
    await page.setContent(`
        <div class="bfFormDiv">
            <form class="bfQuickMode">
                <div class="bfElemWrap">
                    <label class="form-label" for="ff_elem42">Name <i class="fa-solid fa-asterisk"></i></label>
                    <input class="form-control" id="ff_elem42" name="ff_nm_name[]">
                </div>
                <div class="bfElemWrap"><input id="ff_elem43" name="ff_nm_email[]"></div>
                <div class="bfElemWrap"><input id="ff_elem44" name="ff_nm_city[]"></div>
                <div class="bfElemWrap"><select class="form-select" id="country"><option>Country</option></select></div>
                <div class="bfErrorMessage alert alert-danger" data-title-singular="1 field to correct" data-title-plural="%d fields to correct"></div>
            </form>
        </div>
    `);
    await page.addStyleTag({ path: 'media/com_breezingformsng/css/theme-carte.css' });
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-error-alerts-bootstrap.js' });

    await page.evaluate(() => {
        window.bfShowDefaultErrors = true;
        window.bfErrorPageScoped = false;
        window.ff_currentpage = 1;
        window.inlineErrorElements = [
            ['name', 'Name is empty and invalid'],
            ['email', 'Email is required'],
            ['city', 'City is required'],
        ];
        window.ff_getElementByName = (name) => document.querySelector(`[name="ff_nm_${name}[]"]`);
        window.ff_getPageByName = () => 2;
        window.ff_switchpage = (pageNumber) => { window.ff_currentpage = pageNumber; };
        window.bfShowErrors('Name is empty and invalid');
    });

    const name = page.locator('#ff_elem42');
    const summary = page.locator('.bfErrorMessage');

    await expect(name).toHaveClass(/is-invalid/);
    await expect(name).toHaveAttribute('aria-invalid', 'true');
    await expect(name).toHaveAttribute('aria-describedby', 'ff_elem42Feedback');
    await expect(page.locator('#ff_elem42Feedback')).toHaveText('Name is empty and invalid');
    await expect(summary.locator('strong')).toHaveText('3 fields to correct');
    await expect(summary.locator('li a').first()).toHaveAttribute('href', '#ff_elem42');
    await expect(summary.locator('li a')).toHaveText([
        'Name is empty and invalid',
        'Email is required',
        'City is required',
    ]);
    await expect(summary.locator('li a').nth(1)).toHaveAttribute('href', '#ff_elem43');
    await expect(summary.locator('li a').nth(2)).toHaveAttribute('href', '#ff_elem44');
    await expect(name).toHaveCSS('border-top-color', 'rgb(179, 38, 30)');
    await expect(page.locator('.fa-asterisk')).toHaveCSS('color', 'rgb(122, 84, 19)');
    await expect(summary).toHaveCSS('background-color', 'rgb(253, 242, 241)');
    await expect(summary).toHaveCSS('color', 'rgb(179, 38, 30)');

    const select = page.locator('#country');
    await expect.poll(() => select.evaluate((element) => getComputedStyle(element.closest('.bfFormDiv'))
        .getPropertyValue('--bf-select-arrow').trim())).toBe('#5b5853');
    await page.locator('.bfFormDiv').evaluate((element) => element.setAttribute('data-bs-theme', 'dark'));
    await expect.poll(() => select.evaluate((element) => getComputedStyle(element.closest('.bfFormDiv'))
        .getPropertyValue('--bf-select-arrow').trim())).toBe('#f2f2f0');
    await expect(select).toHaveCSS('background-image', /linear-gradient/);

    await summary.locator('li a').first().click();
    await expect.poll(() => page.evaluate(() => window.ff_currentpage)).toBe(2);
    await expect(name).toBeFocused();

    await page.evaluate(() => {
        window.inlineErrorElements = [];
        window.bfShowErrors('');
    });

    await expect(name).not.toHaveClass(/is-invalid/);
    await expect(name).not.toHaveAttribute('aria-invalid');
    await expect(name).not.toHaveAttribute('aria-describedby');
    await expect(page.locator('.bf-field-error')).toHaveCount(0);
});

test('field and summary errors render untrusted validator text as text', async ({ page }) => {
    await page.setContent(`
        <form class="bfQuickMode">
            <div class="bfElemWrap"><input id="ff_elem43" name="ff_nm_name[]"></div>
            <div class="bfErrorMessage" data-title-singular="1 field to correct" data-title-plural="%d fields to correct"></div>
        </form>
    `);
    await page.addScriptTag({ path: 'media/com_breezingformsng/js/site/quickmode-error-alerts-bootstrap.js' });
    await page.evaluate(() => {
        window.bfShowDefaultErrors = true;
        window.bfErrorPageScoped = false;
        window.ff_currentpage = 1;
        window.inlineErrorElements = [['name', '<img src=x onerror=alert(1)>']];
        window.ff_getElementByName = (name) => document.querySelector(`[name="ff_nm_${name}[]"]`);
        window.bfShowErrors('<img src=x onerror=alert(1)>');
    });

    await expect(page.locator('.bf-field-error')).toHaveText('<img src=x onerror=alert(1)>');
    await expect(page.locator('.bf-field-error img')).toHaveCount(0);
    await expect(page.locator('.bfErrorSummaryList img')).toHaveCount(0);
});

test('the Bootstrap theme follows system dark mode unless the form explicitly opts into light mode', async ({ page }) => {
    await page.emulateMedia({ colorScheme: 'dark' });
    await page.setContent(`
        <div class="bfFormDiv">
            <form class="bfQuickMode">
                <input class="form-control" id="dark-input">
                <select class="form-select" id="dark-select"><option>Dark select</option></select>
            </form>
        </div>
        <div class="bfFormDiv" data-bs-theme="light">
            <form class="bfQuickMode"><input class="form-control" id="light-input"></form>
        </div>
    `);
    await page.addStyleTag({ path: 'media/com_breezingformsng/css/theme-carte.css' });

    await expect(page.locator('.bfFormDiv').first().locator('.bfQuickMode')).toHaveCSS('background-color', 'rgb(29, 35, 43)');
    await expect(page.locator('#dark-input')).toHaveCSS('background-color', 'rgb(38, 45, 54)');
    await expect.poll(() => page.locator('#dark-select').evaluate((element) => getComputedStyle(element.closest('.bfFormDiv'))
        .getPropertyValue('--bf-select-arrow').trim())).toBe('#f2f2f0');
    await expect(page.locator('#light-input')).toHaveCSS('background-color', 'rgb(243, 242, 242)');
});

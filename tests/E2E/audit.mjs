import { expect } from '@playwright/test';

// Runs the database audit and opens its report. The report is kept in the user's session and is read once:
// a background request of the page still in flight (statistics, keep-alive) can write the session after the
// audit request and drop it. So the page is quiet first, and the audit is run again when the report is not there.
const ADMIN = 'administrator/index.php';

export async function runAuditAndOpen(page, csrf) {
    await page.waitForLoadState('networkidle');

    for (let attempt = 1; attempt <= 3; attempt += 1) {
        const run = await page.request.post(`${ADMIN}?option=com_breezingformsng&task=about.runAudit`, {
            form: { [csrf]: '1' },
            maxRedirects: 0,
        });
        // The audit run answers with a redirect: anything else means it did not run.
        expect([302, 303]).toContain(run.status());
        await page.goto(`${ADMIN}?option=com_breezingformsng&task=about.display&view=about&layout=audit`);

        // "No database audit has been run yet" is the only notice of an administrator's audit page without a report.
        if (await page.locator('#bf-audit-section .alert-info').count() === 0) {
            // The sections of the report are accordions, closed by default: open them so their controls can be used.
            await page.evaluate(() => {
                document.querySelectorAll('#bf-audit-section details').forEach((details) => { details.open = true; });
            });

            return;
        }
    }
}

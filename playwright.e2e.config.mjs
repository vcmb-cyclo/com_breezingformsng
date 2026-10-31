import { defineConfig, devices } from '@playwright/test';

// Real journeys on an installed Joomla: run through scripts/joomla-e2e.sh.
export default defineConfig({
    testDir: 'tests/E2E',
    workers: 1,
    timeout: 90000,
    forbidOnly: !!process.env.CI,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: process.env.BFNG_E2E_URL,
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'], viewport: { width: 1300, height: 1000 } } },
    ],
});

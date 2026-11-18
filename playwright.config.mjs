import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/Browser',
    forbidOnly: !!process.env.CI,
    // No retries: a failure is a synchronisation or isolation defect to fix, not to hide.
    retries: 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'off',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
});

import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/Browser',
    forbidOnly: !!process.env.CI,
    reporter: process.env.CI ? 'github' : 'list',
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
});

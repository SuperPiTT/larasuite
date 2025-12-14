import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright E2E configuration for Larasuite
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
    testDir: './tests/Playwright',

    /* Run tests in files in parallel */
    fullyParallel: true,

    /* Fail the build on CI if you accidentally left test.only in the source code */
    forbidOnly: !!process.env.CI,

    /* Retry on CI only */
    retries: process.env.CI ? 2 : 0,

    /* Opt out of parallel tests on CI */
    workers: process.env.CI ? 1 : undefined,

    /* Reporter to use */
    reporter: [
        ['html', { outputFolder: 'tests/Playwright/reports' }],
        ['list'],
    ],

    /* Shared settings for all the projects below */
    use: {
        /* Base URL for all tests */
        baseURL: process.env.APP_URL || 'http://larasuite.test',

        /* Collect trace when retrying the failed test */
        trace: 'on-first-retry',

        /* Take screenshot on failure */
        screenshot: 'only-on-failure',

        /* Video recording */
        video: 'retain-on-failure',
    },

    /* Configure projects for browsers */
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    /* Output directory for test artifacts */
    outputDir: 'tests/Playwright/results',

    /* Maximum time one test can run */
    timeout: 30 * 1000,

    /* Maximum time expect() should wait */
    expect: {
        timeout: 5000,
    },
});

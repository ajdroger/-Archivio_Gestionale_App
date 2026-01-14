import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/E2E',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: 'html',
    use: {
        baseURL: 'http://localhost:8080/MCAG_Militare-Civile-Archivio-Gestionale/public',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
    ],
    webServer: {
        command: 'php -S localhost:8080 -t public',
        url: 'http://localhost:8080/MCAG_Militare-Civile-Archivio-Gestionale/public/login',
        reuseExistingServer: !process.env.CI,
        timeout: 120 * 1000,
    },
});

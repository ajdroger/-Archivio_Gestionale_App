import { test, expect } from '@playwright/test';

test('login flow with valid credentials', async ({ page }) => {
    // 1. Visit Login Page
    await page.goto('/login');

    // Verify we are on login page
    await expect(page).toHaveTitle(/Login/);

    // 2. Fill Credentials (Default Admin from migrations)
    await page.fill('#username', 'admin');
    await page.fill('#password', 'password');

    // 3. Submit
    await page.click('button[type="submit"]');

    // 4. Verify Redirection
    // Depending on whether 2FA is forced or if it goes straight to dashboard
    // We expect URL to NOT be /login anymore.
    // Ideally, check for dashboard or 2fa page.
    await expect(page).not.toHaveURL(/.*\/login$/);

    // Optionally check for success element (e.g. sidebar or welcome message)
    // await expect(page.getByText('Fratellanza Militare')).toBeVisible();
});

test('login flow with invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.fill('#username', 'admin');
    await page.fill('#password', 'wrongpassword');
    await page.click('button[type="submit"]');

    // Expect error message
    await expect(page.locator('.alert-danger')).toBeVisible();
});

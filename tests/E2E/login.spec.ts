import { test, expect } from '@playwright/test';

/**
 * E2E Tests - Login Flow
 * Tests the complete authentication flow including 2FA
 */

test.describe('Authentication Flow', () => {
    test('should display login page', async ({ page }) => {
        await page.goto('/login');

        await expect(page).toHaveTitle(/Login|Fratellanza/i);
        await expect(page.locator('input[name="username"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('should show error on invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[name="username"]', 'invalid_user');
        await page.fill('input[name="password"]', 'wrong_password');
        await page.click('button[type="submit"]');

        // Should stay on login page or show error
        await expect(page.locator('.alert-danger, .error, .is-invalid')).toBeVisible({ timeout: 5000 });
    });

    test('should have CSRF token in form', async ({ page }) => {
        await page.goto('/login');

        // Check for CSRF token (hidden input)
        const csrfToken = page.locator('input[name="csrf_name"], input[name="csrf_value"]');
        await expect(csrfToken.first()).toBeAttached();
    });

    test('should redirect unauthenticated users to login', async ({ page }) => {
        await page.goto('/');

        // Should be redirected to login
        await expect(page).toHaveURL(/login/);
    });
});

test.describe('Security Headers', () => {
    test('should have security headers', async ({ page }) => {
        const response = await page.goto('/login');
        const headers = response?.headers();

        // Check for security headers
        expect(headers?.['x-frame-options']).toBe('DENY');
        expect(headers?.['x-content-type-options']).toBe('nosniff');
        expect(headers?.['x-xss-protection']).toBe('1; mode=block');
        expect(headers?.['referrer-policy']).toBe('strict-origin-when-cross-origin');
    });

    test('should have Content-Security-Policy', async ({ page }) => {
        const response = await page.goto('/login');
        const csp = response?.headers()?.['content-security-policy'];

        expect(csp).toBeDefined();
        expect(csp).toContain("default-src 'self'");
    });
});

test.describe('Rate Limiting', () => {
    test('should allow normal login attempts', async ({ page }) => {
        await page.goto('/login');

        // First attempt should work (not rate limited)
        await page.fill('input[name="username"]', 'test');
        await page.fill('input[name="password"]', 'test');

        const responsePromise = page.waitForResponse(response =>
            response.url().includes('/login') && response.request().method() === 'POST'
        );

        await page.click('button[type="submit"]');
        const response = await responsePromise;

        // Should not be rate limited on first attempt
        expect(response.status()).not.toBe(429);
    });
});

test.describe('UI Accessibility', () => {
    test('should have proper form labels', async ({ page }) => {
        await page.goto('/login');

        // Check form has proper labels
        const usernameLabel = page.locator('label[for="username"], label:has-text("Username"), label:has-text("Utente")');
        const passwordLabel = page.locator('label[for="password"], label:has-text("Password")');

        await expect(usernameLabel).toBeVisible();
        await expect(passwordLabel).toBeVisible();
    });

    test('should be keyboard navigable', async ({ page }) => {
        await page.goto('/login');

        // Tab through form elements
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');

        // Should be able to submit with Enter
        await page.fill('input[name="username"]', 'test');
        await page.keyboard.press('Tab');
        await page.fill('input[name="password"]', 'test');
        await page.keyboard.press('Enter');

        // Form should be submitted
        await page.waitForLoadState('networkidle');
    });
});

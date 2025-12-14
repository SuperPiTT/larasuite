import { test, expect } from '@playwright/test';

test.describe('Central Panel Login', () => {
    test('should login successfully with valid credentials', async ({ page }) => {
        // Log all requests and responses
        page.on('request', request => {
            if (request.url().includes('livewire') || request.url().includes('login')) {
                console.log('REQUEST:', request.method(), request.url());
            }
        });
        page.on('response', response => {
            if (response.url().includes('livewire') || response.url().includes('login')) {
                console.log('RESPONSE:', response.status(), response.url());
            }
        });
        page.on('console', msg => console.log('BROWSER:', msg.text()));

        // Go to login page
        await page.goto('http://larasuite.test/central/login');
        await page.waitForLoadState('networkidle');

        // Verify we're on the login page
        await expect(page).toHaveTitle(/Acceso.*Larasuite Central/);

        // Fill the form using standard fill() - simpler approach
        await page.fill('input[type="email"], input[wire\\:model="data.email"]', 'admin@larasuite.test');
        await page.fill('input[type="password"], input[wire\\:model="data.password"]', 'password');

        // Take screenshot before login
        await page.screenshot({ path: 'tests/Playwright/results/before-login.png' });

        // Click login button
        await page.click('button[type="submit"]');

        // Take screenshot during submission
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'tests/Playwright/results/after-click.png' });

        // Wait for either redirect or for the page to settle
        try {
            await page.waitForURL(/\/central(?!\/login)/, { timeout: 15000 });
            console.log('Redirected successfully!');
        } catch {
            // If no redirect, wait a bit more for page to settle
            await page.waitForTimeout(3000);
        }

        // Check current URL
        const currentUrl = page.url();
        console.log('Current URL after login attempt:', currentUrl);

        // Check if we redirected away from login
        if (currentUrl.includes('/central/login')) {
            // Check for error message
            const errorVisible = await page.locator('.fi-fo-field-wrp-error-message, [class*="alert"], [class*="error"]').isVisible().catch(() => false);
            console.log('Error message visible:', errorVisible);

            if (errorVisible) {
                const errorText = await page.locator('.fi-fo-field-wrp-error-message, [class*="alert"], [class*="error"]').first().textContent();
                console.log('Error text:', errorText);
            }

            throw new Error(`Login failed - still on login page. URL: ${currentUrl}`);
        }

        // Take screenshot after login
        await page.screenshot({ path: 'tests/Playwright/results/after-login.png' });

        // Verify dashboard is shown
        await expect(page).toHaveURL(/\/central(?!\/login)/);
        console.log('Login successful! URL:', page.url());
    });

    test('should show error with invalid credentials', async ({ page, context }) => {
        // Clear cookies to ensure clean state
        await context.clearCookies();

        await page.goto('http://larasuite.test/central/login');
        await page.waitForLoadState('networkidle');

        await page.fill('input[type="email"], input[wire\\:model="data.email"]', 'wrong@email.com');
        await page.fill('input[type="password"], input[wire\\:model="data.password"]', 'wrongpassword');
        await page.click('button[type="submit"]');

        // Wait for response
        await page.waitForTimeout(3000);
        await page.screenshot({ path: 'tests/Playwright/results/invalid-login.png' });

        // Should still be on login page
        await expect(page).toHaveURL(/\/central\/login/);
    });
});

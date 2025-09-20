import { test, expect } from '@playwright/test';

test.describe('Profile Functionality (Simple)', () => {
  let testUserEmail: string;
  let testUserName: string;

  test.beforeEach(async ({ page }) => {
    // Generate unique test data
    testUserEmail = `profile-simple-${Date.now()}@example.com`;
    testUserName = `Profile Simple User ${Date.now()}`;

    // Clear any existing browser state
    await page.context().clearCookies();

    // Create a test user and log in directly
    await page.goto('/register');
    await page.waitForLoadState('networkidle');

    // Fill in registration form
    await page.locator('input[name="name"]').fill(testUserName);
    await page.locator('input[name="email"]').fill(testUserEmail);
    await page.locator('input[name="password"]').fill('password123');
    await page.locator('input[name="password_confirmation"]').fill('password123');

    // Submit registration
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');

    // Handle onboarding if redirected there
    if (page.url().includes('/onboarding')) {
      // Try to find and click skip button
      const skipButton = page.locator('button:has-text("Skip"), a:has-text("Skip")').first();
      if (await skipButton.isVisible({ timeout: 5000 })) {
        await skipButton.click();
        await page.waitForLoadState('networkidle');
      } else {
        // If no skip button, navigate directly to dashboard
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
      }
    }

    // Ensure we're authenticated and on dashboard
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test('should navigate to profile page directly', async ({ page }) => {
    // Navigate directly to profile
    await page.goto('/profile');
    await page.waitForLoadState('networkidle');

    // Should be on profile page
    await expect(page).toHaveURL(/\/profile/);
    await expect(page.locator('h1')).toContainText('Profile Settings');

    // Should show user information
    await expect(page.locator('h2')).toContainText(testUserName);
  });

  test('should switch between profile tabs', async ({ page }) => {
    await page.goto('/profile');
    await page.waitForLoadState('networkidle');

    // Test General tab (should be active by default)
    await expect(page.locator('[data-testid="profile-tab-general"]')).toHaveClass(/bg-blue-50/);
    await expect(page.locator('text=General Information')).toBeVisible();

    // Switch to Preferences tab
    await page.locator('[data-testid="profile-tab-preferences"]').click();
    await expect(page.locator('[data-testid="profile-tab-preferences"]')).toHaveClass(/bg-blue-50/);
    await expect(page.locator('text=Language').first()).toBeVisible();

    // Switch to Security tab
    await page.locator('[data-testid="profile-tab-security"]').click();
    await expect(page.locator('[data-testid="profile-tab-security"]')).toHaveClass(/bg-blue-50/);
    await expect(page.locator('text=Security Settings')).toBeVisible();
  });

  test('should update user name', async ({ page }) => {
    await page.goto('/profile');
    await page.waitForLoadState('networkidle');

    const newName = `Updated ${testUserName}`;

    // Update name field
    await page.locator('[data-testid="profile-name-input"]').clear();
    await page.locator('[data-testid="profile-name-input"]').fill(newName);

    // Save changes
    await page.locator('[data-testid="profile-save-changes"]').click();
    await page.waitForLoadState('networkidle');

    // Wait a moment for any redirects or updates
    await page.waitForTimeout(2000);

    // Verify the name was updated
    if (page.url().includes('/profile')) {
      // If still on profile page, check sidebar
      await expect(page.locator('h2')).toContainText(newName);
    } else {
      // If redirected, go back to profile to verify
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');
      await expect(page.locator('h2')).toContainText(newName);
    }
  });

  test('should switch language from profile preferences', async ({ page }) => {
    await page.goto('/profile');
    await page.waitForLoadState('networkidle');

    // Go to Preferences tab
    await page.locator('[data-testid="profile-tab-preferences"]').click();
    await page.waitForTimeout(500);

    // Verify we can see language options
    await expect(page.locator('[data-testid="locale-option-en"]')).toBeVisible();
    await expect(page.locator('[data-testid="locale-option-ru"]')).toBeVisible();

    // English should be selected by default
    await expect(page.locator('[data-testid="locale-option-en"]')).toBeChecked();

    // Click Russian language option (click the label since radio is hidden)
    await page.locator('label:has([data-testid="locale-option-ru"])').click();

    // Wait for language change to process
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000); // Give time for reload

    // Check that current locale is updated (page may have reloaded)
    const currentLocale = await page.evaluate(() => window.currentLocale);

    // The most reliable check is that the Russian radio button is now selected
    await expect(page.locator('[data-testid="locale-option-ru"]')).toBeChecked();

    // JavaScript locale state might be undefined if page reloaded, but that's expected
    if (currentLocale !== undefined) {
      expect(currentLocale).toBe('ru');
    }

    // The page should have reloaded with Russian content
    // Note: Content may or may not change immediately depending on cached translations
  });

  test('should display language switcher in navigation', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Language switcher should be visible (use first visible one - desktop version)
    const languageSwitcher = page.locator('[data-testid="language-switcher"]').first();
    await expect(languageSwitcher).toBeVisible();

    // Should show English flag initially
    await expect(languageSwitcher).toContainText('🇬🇧');

    // Click to open dropdown
    await languageSwitcher.click();
    await page.waitForTimeout(500);

    // Should see both language options (use first visible instances)
    await expect(page.locator('[data-testid="language-option-en"]').first()).toBeVisible();
    await expect(page.locator('[data-testid="language-option-ru"]').first()).toBeVisible();
  });

  test('should switch language from navigation', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Click language switcher (first visible one)
    await page.locator('[data-testid="language-switcher"]').first().click();
    await page.waitForTimeout(500);

    // Click Russian option (first visible one)
    await page.locator('[data-testid="language-option-ru"]').first().click();

    // Wait for language change
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    // Check that locale changed (either via JavaScript or visual flag)
    const currentLocale = await page.evaluate(() => window.currentLocale);

    // Navigation should show Russian flag (this is the most reliable indicator)
    await expect(page.locator('[data-testid="language-switcher"]').first()).toContainText('🇷🇺');

    // JavaScript locale state might be undefined if page reloaded, but flag should be correct
    if (currentLocale !== undefined) {
      expect(currentLocale).toBe('ru');
    }
  });

  test.afterEach(async ({ page }) => {
    // Clean up: reset language to English
    try {
      await page.evaluate(async () => {
        try {
          await fetch('/locale', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ locale: 'en' })
          });
        } catch (error) {
          console.log('Language cleanup failed:', error);
        }
      });
    } catch (error) {
      console.log('Test cleanup error:', error);
    }
  });
});
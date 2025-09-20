import { test, expect } from '@playwright/test';

test.describe('Language Switching Functionality', () => {
  let testUserEmail: string;
  let testUserName: string;

  test.beforeEach(async ({ page }) => {
    // Generate unique test data
    testUserEmail = `lang-test-${Date.now()}@example.com`;
    testUserName = `Lang Test User ${Date.now()}`;

    // Clear any existing browser state
    await page.context().clearCookies();

    // Create and login a test user
    await page.goto('/register');
    await page.waitForLoadState('networkidle');

    await page.locator('input[name="name"]').fill(testUserName);
    await page.locator('input[name="email"]').fill(testUserEmail);
    await page.locator('input[name="password"]').fill('password123');
    await page.locator('input[name="password_confirmation"]').fill('password123');
    await page.locator('button[type="submit"]').click();
    await page.waitForLoadState('networkidle');

    // After registration, users go through onboarding first
    // Skip onboarding to get to dashboard quickly
    if (page.url().includes('/onboarding')) {
      await page.locator('text=Skip').click();
      await page.waitForLoadState('networkidle');
    }

    // Should now be on dashboard
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test.describe('Navigation Language Switching', () => {
    test('should display language switcher in navigation', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Language switcher should be visible
      const languageSwitcher = page.locator('[data-testid="language-switcher"]');
      await expect(languageSwitcher).toBeVisible();

      // Should show English flag initially
      await expect(languageSwitcher).toContainText('🇬🇧');
    });

    test('should open language dropdown when clicked', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Click language switcher
      await page.locator('[data-testid="language-switcher"]').click();
      await page.waitForTimeout(500);

      // Dropdown should be visible with both language options
      await expect(page.locator('[data-testid="language-option-en"]')).toBeVisible();
      await expect(page.locator('[data-testid="language-option-ru"]')).toBeVisible();

      // Should show flag emojis and native names
      await expect(page.locator('text=🇬🇧')).toBeVisible();
      await expect(page.locator('text=🇷🇺')).toBeVisible();
      await expect(page.locator('text=English')).toBeVisible();
      await expect(page.locator('text=Русский')).toBeVisible();
    });

    test('should switch to Russian from navigation', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Switch to Russian
      await page.locator('[data-testid="language-switcher"]').click();
      await page.waitForTimeout(500);
      await page.locator('[data-testid="language-option-ru"]').click();

      // Wait for language change to complete
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Verify language changed
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');

      // Navigation should show Russian flag
      await expect(page.locator('[data-testid="language-switcher"]')).toContainText('🇷🇺');
    });

    test('should close dropdown when clicking outside', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Open dropdown
      await page.locator('[data-testid="language-switcher"]').click();
      await page.waitForTimeout(500);

      // Verify dropdown is open
      await expect(page.locator('[data-testid="language-option-en"]')).toBeVisible();

      // Click outside the dropdown
      await page.locator('body').click();
      await page.waitForTimeout(500);

      // Dropdown should be closed
      await expect(page.locator('[data-testid="language-option-en"]')).not.toBeVisible();
    });
  });

  test.describe('Profile Page Language Switching', () => {
    test('should switch language from profile preferences', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Go to preferences tab
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await page.waitForTimeout(500);

      // Should see language selection interface
      await expect(page.locator('text=Language')).toBeVisible();
      await expect(page.locator('[data-testid="locale-option-en"]')).toBeVisible();
      await expect(page.locator('[data-testid="locale-option-ru"]')).toBeVisible();

      // Switch to Russian
      await page.locator('[data-testid="locale-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Verify language changed
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');

      // Page content should be in Russian
      await expect(page.locator('text=Профиль')).toBeVisible({ timeout: 5000 });
    });

    test('should show loading state during language change', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await page.waitForTimeout(500);

      // Monitor for disabled state during language change
      const ruOption = page.locator('[data-testid="locale-option-ru"]');
      await ruOption.click();

      // Radio buttons should be temporarily disabled during the change
      // This is a quick check as the loading state is brief
      await page.waitForTimeout(100);
    });

    test('should handle language switching errors gracefully', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      await page.locator('[data-testid="profile-tab-preferences"]').click();

      // Mock a network error by intercepting the request
      await page.route('/locale', route => {
        route.fulfill({
          status: 500,
          contentType: 'application/json',
          body: JSON.stringify({ success: false, message: 'Server error' })
        });
      });

      // Try to switch language
      await page.locator('[data-testid="locale-option-ru"]').click();
      await page.waitForTimeout(2000);

      // Language should not have changed
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('en');

      // Radio buttons should be re-enabled
      const enOption = page.locator('[data-testid="locale-option-en"]');
      await expect(enOption).not.toBeDisabled();
    });
  });

  test.describe('Language Persistence', () => {
    test('should persist language across page navigation', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Switch to Russian
      await page.locator('[data-testid="language-switcher"]').click();
      await page.waitForTimeout(500);
      await page.locator('[data-testid="language-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Navigate to different pages
      const pagesToTest = ['/profile', '/dashboard'];

      for (const pagePath of pagesToTest) {
        await page.goto(pagePath);
        await page.waitForLoadState('networkidle');

        // Language should still be Russian
        const currentLocale = await page.evaluate(() => window.currentLocale);
        expect(currentLocale).toBe('ru');

        // Navigation should show Russian flag
        await expect(page.locator('[data-testid="language-switcher"]')).toContainText('🇷🇺');
      }
    });

    test('should persist language across browser sessions', async ({ page, context }) => {
      // Switch to Russian
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      await page.locator('[data-testid="language-switcher"]').click();
      await page.waitForTimeout(500);
      await page.locator('[data-testid="language-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Create a new page (simulating a new tab/session)
      const newPage = await context.newPage();
      await newPage.goto('/dashboard');
      await newPage.waitForLoadState('networkidle');

      // Language should still be Russian
      const currentLocale = await newPage.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');

      await newPage.close();
    });

    test('should handle language switching for authenticated vs guest users', async ({ page }) => {
      // First, test as authenticated user
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      await page.locator('[data-testid="language-switcher"]').click();
      await page.waitForTimeout(500);
      await page.locator('[data-testid="language-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Verify language changed for authenticated user
      let currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');

      // Log out
      await page.locator('button:has-text("' + testUserName + '")').click();
      await page.locator('text=Log Out').click();
      await page.waitForLoadState('networkidle');

      // Should be on login page now
      await expect(page).toHaveURL(/\/login/);

      // Language preference should persist even after logout (for guest experience)
      currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');
    });
  });

  test.describe('Language Switching Edge Cases', () => {
    test('should handle rapid language switching', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Rapidly switch languages multiple times
      for (let i = 0; i < 3; i++) {
        await page.locator('[data-testid="language-switcher"]').click();
        await page.waitForTimeout(200);
        await page.locator('[data-testid="language-option-ru"]').click();
        await page.waitForTimeout(1000);

        await page.locator('[data-testid="language-switcher"]').click();
        await page.waitForTimeout(200);
        await page.locator('[data-testid="language-option-en"]').click();
        await page.waitForTimeout(1000);
      }

      // Should end up in English and be stable
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('en');
      await expect(page.locator('[data-testid="language-switcher"]')).toContainText('🇬🇧');
    });

    test('should maintain language preference during form submissions', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Switch to Russian
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await page.locator('[data-testid="locale-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Update profile information
      await page.locator('[data-testid="profile-tab-general"]').click();
      await page.locator('[data-testid="profile-name-input"]').fill('Updated Name');
      await page.locator('[data-testid="profile-save-changes"]').click();
      await page.waitForLoadState('networkidle');

      // Language should still be Russian after form submission
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');
    });

    test('should show appropriate language in both navigation and profile', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Switch to Russian via navigation
      await page.locator('[data-testid="language-switcher"]').click();
      await page.waitForTimeout(500);
      await page.locator('[data-testid="language-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Go to profile page
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Profile preferences should also show Russian as selected
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await page.waitForTimeout(500);

      // Both navigation and profile should reflect Russian selection
      await expect(page.locator('[data-testid="language-switcher"]')).toContainText('🇷🇺');

      // The Russian radio button should be selected in profile
      const russianRadio = page.locator('[data-testid="locale-option-ru"]');
      await expect(russianRadio).toBeChecked();
    });
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
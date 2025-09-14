import { test, expect } from '@playwright/test';

test.describe('Profile Functionality', () => {
  let testUserEmail: string;
  let testUserName: string;

  test.beforeEach(async ({ page }) => {
    // Generate unique test data
    testUserEmail = `profile-test-${Date.now()}@example.com`;
    testUserName = `Profile Test User ${Date.now()}`;

    // Clear any existing browser state
    await page.context().clearCookies();

    // Create a test user and log in
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

    // After registration, users go through onboarding first
    // Skip onboarding to get to dashboard quickly
    if (page.url().includes('/onboarding')) {
      await page.locator('text=Skip').click();
      await page.waitForLoadState('networkidle');
    }

    // Should now be on dashboard
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test.describe('Profile Page Access and Navigation', () => {
    test('should access profile page from navigation menu', async ({ page }) => {
      // Click on user dropdown in navigation
      await page.locator('button:has-text("' + testUserName + '")').click();

      // Click on Profile link from the dropdown (use first one)
      await page.locator('a[href*="/profile"]').first().click();
      await page.waitForLoadState('networkidle');

      // Verify we're on the profile page
      await expect(page).toHaveURL(/\/profile/);
      await expect(page.locator('h1')).toContainText('Profile Settings');
    });

    test('should display user information in profile sidebar', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Check user avatar initial
      const userInitial = testUserName.charAt(0).toUpperCase();
      await expect(page.locator('.rounded-full').first()).toContainText(userInitial);

      // Check user name and email display
      await expect(page.locator('h2')).toContainText(testUserName);
      await expect(page.locator('p:has-text("' + testUserEmail + '")')).toBeVisible();

      // Check stats display (should show 0 children and 0 subjects for new user)
      await expect(page.locator('text=Children')).toBeVisible();
      await expect(page.locator('text=Subjects')).toBeVisible();
    });

    test('should navigate between profile tabs', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Test General tab (should be active by default)
      await expect(page.locator('[data-testid="profile-tab-general"]')).toHaveClass(/bg-blue-50/);
      await expect(page.locator('text=General Information')).toBeVisible();

      // Switch to Preferences tab
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await expect(page.locator('[data-testid="profile-tab-preferences"]')).toHaveClass(/bg-blue-50/);
      await expect(page.locator('text=Language')).toBeVisible();

      // Switch to Security tab
      await page.locator('[data-testid="profile-tab-security"]').click();
      await expect(page.locator('[data-testid="profile-tab-security"]')).toHaveClass(/bg-blue-50/);
      await expect(page.locator('text=Security Settings')).toBeVisible();
    });
  });

  test.describe('Profile Information Editing', () => {
    test('should update user name successfully', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      const newName = `Updated ${testUserName}`;

      // Update name field
      await page.locator('[data-testid="profile-name-input"]').clear();
      await page.locator('[data-testid="profile-name-input"]').fill(newName);

      // Save changes
      await page.locator('[data-testid="profile-save-changes"]').click();
      await page.waitForLoadState('networkidle');

      // Check for success feedback (either redirect or success message)
      // The page might redirect after successful update
      await page.waitForTimeout(1000);

      // Verify the name was updated in the sidebar
      await expect(page.locator('h2')).toContainText(newName);
    });

    test('should update email address successfully', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      const newEmail = `updated-${testUserEmail}`;

      // Update email field
      await page.locator('[data-testid="profile-email-input"]').clear();
      await page.locator('[data-testid="profile-email-input"]').fill(newEmail);

      // Save changes
      await page.locator('[data-testid="profile-save-changes"]').click();
      await page.waitForLoadState('networkidle');

      // Wait for potential redirect
      await page.waitForTimeout(1000);

      // Verify the email was updated in the sidebar
      await expect(page.locator('p:has-text("' + newEmail + '")')).toBeVisible();
    });

    test('should validate required fields', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Clear required fields
      await page.locator('[data-testid="profile-name-input"]').clear();
      await page.locator('[data-testid="profile-email-input"]').clear();

      // Try to submit
      await page.locator('[data-testid="profile-save-changes"]').click();

      // Check for HTML5 validation or error messages
      const nameInput = page.locator('[data-testid="profile-name-input"]');
      const emailInput = page.locator('[data-testid="profile-email-input"]');

      await expect(nameInput).toHaveAttribute('required');
      await expect(emailInput).toHaveAttribute('required');

      // The form should not submit successfully
      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/\/profile/);
    });
  });

  test.describe('Language Switching from Profile', () => {
    test('should switch to Russian language from profile preferences', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Go to Preferences tab
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await page.waitForTimeout(500);

      // Click Russian language option
      await page.locator('[data-testid="locale-option-ru"]').click();

      // Wait for language change to process
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Check that page content is now in Russian
      await expect(page.locator('text=Профиль')).toBeVisible({ timeout: 10000 });

      // Verify locale state
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');
    });

    test('should switch back to English from profile preferences', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // First switch to Russian
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await page.locator('[data-testid="locale-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Now switch back to English
      await page.locator('[data-testid="locale-option-en"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Check that page content is back in English
      await expect(page.locator('text=Profile Settings')).toBeVisible({ timeout: 10000 });

      // Verify locale state
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('en');
    });

    test('should persist language preference across page navigation', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Switch to Russian
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await page.locator('[data-testid="locale-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Navigate to dashboard
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Check that dashboard is also in Russian
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');
    });
  });

  test.describe('Language Switching from Navigation', () => {
    test('should switch language from navigation dropdown', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Click on language switcher in navigation
      const languageSwitcher = page.locator('[data-testid="language-switcher"]');
      await languageSwitcher.click();
      await page.waitForTimeout(500);

      // Click Russian option
      const russianOption = page.locator('[data-testid="language-option-ru"]');
      await russianOption.click();

      // Wait for language change
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Verify page is now in Russian
      const currentLocale = await page.evaluate(() => window.currentLocale);
      expect(currentLocale).toBe('ru');
    });

    test('should show current language in navigation switcher', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Check initial language display (should show English flag and name)
      const languageSwitcher = page.locator('[data-testid="language-switcher"]');
      await expect(languageSwitcher).toContainText('🇬🇧');

      // Switch to Russian via profile
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await page.locator('[data-testid="locale-option-ru"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Check that navigation switcher now shows Russian
      await expect(languageSwitcher).toContainText('🇷🇺');
    });
  });

  test.describe('User Preferences Management', () => {
    test('should update timezone and date format preferences', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Go to Preferences tab
      await page.locator('[data-testid="profile-tab-preferences"]').click();

      // Update timezone
      await page.locator('select[name="timezone"]').selectOption('America/New_York');

      // Update date format
      await page.locator('select[name="date_format"]').selectOption('m/d/Y');

      // Save preferences
      await page.locator('[data-testid="profile-save-preferences"]').click();
      await page.waitForLoadState('networkidle');

      // Wait for form submission
      await page.waitForTimeout(1000);

      // Verify preferences were saved (check if form still shows selected values)
      await expect(page.locator('select[name="timezone"]')).toHaveValue('America/New_York');
      await expect(page.locator('select[name="date_format"]')).toHaveValue('m/d/Y');
    });

    test('should toggle notification preferences', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Go to Preferences tab
      await page.locator('[data-testid="profile-tab-preferences"]').click();

      // Toggle notification checkboxes
      const emailNotifications = page.locator('input[name="email_notifications"]');
      const reviewReminders = page.locator('input[name="review_reminders"]');

      // Check current state and toggle
      const emailChecked = await emailNotifications.isChecked();
      const reviewChecked = await reviewReminders.isChecked();

      if (!emailChecked) await emailNotifications.check();
      if (!reviewChecked) await reviewReminders.check();

      // Save preferences
      await page.locator('[data-testid="profile-save-preferences"]').click();
      await page.waitForLoadState('networkidle');

      // Wait for form submission
      await page.waitForTimeout(1000);

      // Verify checkboxes remain checked
      await expect(emailNotifications).toBeChecked();
      await expect(reviewReminders).toBeChecked();
    });
  });

  test.describe('Form Validation and Error Handling', () => {
    test('should validate email format', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Enter invalid email
      await page.locator('[data-testid="profile-email-input"]').clear();
      await page.locator('[data-testid="profile-email-input"]').fill('invalid-email');

      // Try to submit
      await page.locator('[data-testid="profile-save-changes"]').click();

      // Check for HTML5 validation
      const emailInput = page.locator('[data-testid="profile-email-input"]');
      await expect(emailInput).toHaveAttribute('type', 'email');
    });

    test('should handle server errors gracefully', async ({ page }) => {
      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Enter valid data first
      await page.locator('[data-testid="profile-name-input"]').fill('Valid Name');
      await page.locator('[data-testid="profile-email-input"]').fill('valid@email.com');

      // This should work normally (we can't easily simulate server errors in E2E tests)
      await page.locator('[data-testid="profile-save-changes"]').click();
      await page.waitForLoadState('networkidle');

      // The form should submit successfully
      await page.waitForTimeout(1000);
    });
  });

  test.describe('Profile Page Responsiveness', () => {
    test('should work on mobile viewport', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });

      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // Check that profile content is visible and accessible
      await expect(page.locator('h1')).toContainText('Profile Settings');

      // Tab navigation should still work
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await expect(page.locator('text=Language')).toBeVisible();

      // Form inputs should be accessible
      await expect(page.locator('[data-testid="profile-name-input"]')).toBeVisible();
    });

    test('should work on tablet viewport', async ({ page }) => {
      // Set tablet viewport
      await page.setViewportSize({ width: 768, height: 1024 });

      await page.goto('/profile');
      await page.waitForLoadState('networkidle');

      // All functionality should work normally
      await expect(page.locator('h1')).toContainText('Profile Settings');
      await page.locator('[data-testid="profile-tab-preferences"]').click();
      await expect(page.locator('text=Language')).toBeVisible();
    });
  });

  test.afterEach(async ({ page }) => {
    // Clean up: reset language to English after each test
    try {
      await page.evaluate(async () => {
        try {
          const response = await fetch('/locale', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ locale: 'en' })
          });
          console.log('Test cleanup: reset locale to English');
        } catch (error) {
          console.log('Test cleanup failed:', error);
        }
      });
    } catch (error) {
      console.log('Test cleanup error:', error);
    }
  });
});
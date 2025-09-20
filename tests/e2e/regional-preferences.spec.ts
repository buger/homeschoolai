import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';

test.describe('Regional Format Preferences', () => {
  let modalHelper: ModalHelper;
  let elementHelper: ElementHelper;

  test.beforeEach(async ({ page }) => {
    modalHelper = new ModalHelper(page);
    elementHelper = new ElementHelper(page);

    await page.goto('/');
    await elementHelper.waitForPageReady();
  });

  test('should apply US regional defaults when user registers with English', async ({ page }) => {
    // Go to registration
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    // Fill out registration form
    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    // Submit registration
    await page.click('button[type="submit"]');

    // Should be redirected to dashboard
    await expect(page.url()).toContain('/dashboard');

    // Go to profile settings
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    // Verify US format defaults are applied
    const regionFormatSelect = page.locator('select[name="region_format"]');
    await expect(regionFormatSelect).toHaveValue('us');

    // Check that format-specific fields show US defaults
    const timeFormat = page.locator('select[name="time_format"]');
    const weekStart = page.locator('select[name="week_start"]');
    const dateFormatType = page.locator('select[name="date_format_type"]');

    await expect(timeFormat).toHaveValue('12h');
    await expect(weekStart).toHaveValue('sunday');
    await expect(dateFormatType).toHaveValue('us');
  });

  test('should apply EU regional defaults when user registers with Russian', async ({ page }) => {
    // Go to registration
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    // Change language to Russian first
    const languageSwitcher = page.locator('[data-testid="language-switcher"]').or(page.locator('button:has-text("EN")'));
    if (await languageSwitcher.isVisible()) {
      await languageSwitcher.click();
      await page.click('a[href*="locale"][href*="ru"]');
      await elementHelper.waitForPageReady();
    }

    // Fill out registration form
    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    // Set locale to Russian if field is available
    const localeSelect = page.locator('select[name="locale"]');
    if (await localeSelect.isVisible()) {
      await localeSelect.selectOption('ru');
    }

    // Submit registration
    await page.click('button[type="submit"]');

    // Should be redirected to dashboard
    await expect(page.url()).toContain('/dashboard');

    // Go to profile settings
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    // Verify EU format defaults are applied
    const regionFormatSelect = page.locator('select[name="region_format"]');
    await expect(regionFormatSelect).toHaveValue('eu');

    // Check that format-specific fields show EU defaults
    const timeFormat = page.locator('select[name="time_format"]');
    const weekStart = page.locator('select[name="week_start"]');
    const dateFormatType = page.locator('select[name="date_format_type"]');

    await expect(timeFormat).toHaveValue('24h');
    await expect(weekStart).toHaveValue('monday');
    await expect(dateFormatType).toHaveValue('eu');
  });

  test('should change language and apply regional defaults for existing user', async ({ page }) => {
    // First register a user with English
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    await page.click('button[type="submit"]');
    await expect(page.url()).toContain('/dashboard');

    // Verify initial US format
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    let regionFormatSelect = page.locator('select[name="region_format"]');
    await expect(regionFormatSelect).toHaveValue('us');

    // Change language to Russian using language switcher
    const languageSwitcher = page.locator('[data-testid="language-switcher"]').or(page.locator('button:has-text("EN")'));
    if (await languageSwitcher.isVisible()) {
      await languageSwitcher.click();
      await page.click('a[href*="locale"][href*="ru"]');
      await elementHelper.waitForPageReady();
    }

    // Go back to profile and verify formats changed
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    regionFormatSelect = page.locator('select[name="region_format"]');
    await expect(regionFormatSelect).toHaveValue('eu');

    const timeFormat = page.locator('select[name="time_format"]');
    const weekStart = page.locator('select[name="week_start"]');

    await expect(timeFormat).toHaveValue('24h');
    await expect(weekStart).toHaveValue('monday');
  });

  test('should preserve custom format when changing language', async ({ page }) => {
    // Register and login user
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    await page.click('button[type="submit"]');
    await expect(page.url()).toContain('/dashboard');

    // Go to profile and set custom format
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    // Change to custom format
    await page.selectOption('select[name="region_format"]', 'custom');

    // Set custom preferences
    await page.selectOption('select[name="time_format"]', '24h');
    await page.selectOption('select[name="week_start"]', 'monday');
    await page.selectOption('select[name="date_format_type"]', 'iso');

    // Save preferences
    await page.click('button[type="submit"]:has-text("Save")');
    await elementHelper.waitForPageReady();

    // Verify custom format was saved
    const regionFormatSelect = page.locator('select[name="region_format"]');
    await expect(regionFormatSelect).toHaveValue('custom');

    // Change language to Russian
    const languageSwitcher = page.locator('[data-testid="language-switcher"]').or(page.locator('button:has-text("EN")'));
    if (await languageSwitcher.isVisible()) {
      await languageSwitcher.click();
      await page.click('a[href*="locale"][href*="ru"]');
      await elementHelper.waitForPageReady();
    }

    // Go back to profile and verify custom format is preserved
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    await expect(regionFormatSelect).toHaveValue('custom');

    const timeFormat = page.locator('select[name="time_format"]');
    const weekStart = page.locator('select[name="week_start"]');
    const dateFormatType = page.locator('select[name="date_format_type"]');

    await expect(timeFormat).toHaveValue('24h');
    await expect(weekStart).toHaveValue('monday');
    await expect(dateFormatType).toHaveValue('iso');
  });

  test('should update individual format preferences', async ({ page }) => {
    // Register and login user
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    await page.click('button[type="submit"]');
    await expect(page.url()).toContain('/dashboard');

    // Go to profile settings
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    // Change from US to EU format
    await page.selectOption('select[name="region_format"]', 'eu');

    // Save changes
    await page.click('button[type="submit"]:has-text("Save")');
    await elementHelper.waitForPageReady();

    // Verify EU format was applied
    const timeFormat = page.locator('select[name="time_format"]');
    const weekStart = page.locator('select[name="week_start"]');
    const dateFormatType = page.locator('select[name="date_format_type"]');

    await expect(timeFormat).toHaveValue('24h');
    await expect(weekStart).toHaveValue('monday');
    await expect(dateFormatType).toHaveValue('eu');

    // Change to custom format
    await page.selectOption('select[name="region_format"]', 'custom');
    await page.selectOption('select[name="time_format"]', '12h');
    await page.selectOption('select[name="week_start"]', 'sunday');
    await page.selectOption('select[name="date_format_type"]', 'iso');

    // Save custom changes
    await page.click('button[type="submit"]:has-text("Save")');
    await elementHelper.waitForPageReady();

    // Verify custom format was applied
    await expect(page.locator('select[name="region_format"]')).toHaveValue('custom');
    await expect(timeFormat).toHaveValue('12h');
    await expect(weekStart).toHaveValue('sunday');
    await expect(dateFormatType).toHaveValue('iso');
  });

  test('should display success message when preferences are updated', async ({ page }) => {
    // Register and login user
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    await page.click('button[type="submit"]');
    await expect(page.url()).toContain('/dashboard');

    // Go to profile settings and make a change
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    // Change region format
    await page.selectOption('select[name="region_format"]', 'eu');

    // Save changes
    await page.click('button[type="submit"]:has-text("Save")');

    // Should see success message
    const successMessage = page.locator('.alert-success').or(page.locator('[role="alert"]:has-text("success")')).or(page.locator('text=updated successfully'));
    await expect(successMessage).toBeVisible({ timeout: 10000 });
  });

  test('should validate format preference values', async ({ page }) => {
    // Register and login user
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    await page.click('button[type="submit"]');
    await expect(page.url()).toContain('/dashboard');

    // Go to profile settings
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    // Verify only valid options are available
    const regionFormatSelect = page.locator('select[name="region_format"]');
    const regionOptions = await regionFormatSelect.locator('option').allTextContents();
    expect(regionOptions).toContain('US Format');
    expect(regionOptions).toContain('European Format');
    expect(regionOptions).toContain('Custom');

    const timeFormatSelect = page.locator('select[name="time_format"]');
    const timeOptions = await timeFormatSelect.locator('option').allTextContents();
    expect(timeOptions.join(' ')).toMatch(/12.*hour|AM.*PM/i);
    expect(timeOptions.join(' ')).toMatch(/24.*hour/i);

    const weekStartSelect = page.locator('select[name="week_start"]');
    const weekOptions = await weekStartSelect.locator('option').allTextContents();
    expect(weekOptions).toContain('Sunday');
    expect(weekOptions).toContain('Monday');
  });

  test('should show format preview in profile settings', async ({ page }) => {
    // Register and login user
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    await page.click('button[type="submit"]');
    await expect(page.url()).toContain('/dashboard');

    // Go to profile settings
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    // Look for format examples or preview text
    const pageContent = await page.content();

    // Should show examples of date formats
    expect(pageContent).toMatch(/MM\/DD\/YYYY|DD\.MM\.YYYY|YYYY-MM-DD/);

    // Should show examples of time formats
    expect(pageContent).toMatch(/AM\/PM|24.*hour/i);
  });

  test('should maintain format preferences across sessions', async ({ page }) => {
    const testEmail = `test-${Date.now()}@example.com`;

    // Register user
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    await page.click('button[type="submit"]');
    await expect(page.url()).toContain('/dashboard');

    // Set custom preferences
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    await page.selectOption('select[name="region_format"]', 'eu');
    await page.click('button[type="submit"]:has-text("Save")');
    await elementHelper.waitForPageReady();

    // Logout
    await page.click('button:has-text("Log Out")').or(page.click('a:has-text("Log Out")')).or(page.click('[data-testid="logout-button"]'));
    await elementHelper.waitForPageReady();

    // Login again
    await page.goto('/login');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await expect(page.url()).toContain('/dashboard');

    // Check that preferences are still there
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    const regionFormatSelect = page.locator('select[name="region_format"]');
    await expect(regionFormatSelect).toHaveValue('eu');
  });

  test('should handle format switching between presets correctly', async ({ page }) => {
    // Register and login user
    await page.goto('/register');
    await elementHelper.waitForPageReady();

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', `test-${Date.now()}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');

    await page.click('button[type="submit"]');
    await expect(page.url()).toContain('/dashboard');

    // Go to profile settings
    await page.goto('/profile');
    await elementHelper.waitForPageReady();

    // Start with US format (default)
    let regionFormatSelect = page.locator('select[name="region_format"]');
    await expect(regionFormatSelect).toHaveValue('us');

    // Switch to EU format
    await page.selectOption('select[name="region_format"]', 'eu');
    await page.click('button[type="submit"]:has-text("Save")');
    await elementHelper.waitForPageReady();

    // Verify EU format applied
    await expect(regionFormatSelect).toHaveValue('eu');
    await expect(page.locator('select[name="time_format"]')).toHaveValue('24h');
    await expect(page.locator('select[name="week_start"]')).toHaveValue('monday');

    // Switch back to US format
    await page.selectOption('select[name="region_format"]', 'us');
    await page.click('button[type="submit"]:has-text("Save")');
    await elementHelper.waitForPageReady();

    // Verify US format applied
    await expect(regionFormatSelect).toHaveValue('us');
    await expect(page.locator('select[name="time_format"]')).toHaveValue('12h');
    await expect(page.locator('select[name="week_start"]')).toHaveValue('sunday');

    // Switch to custom format
    await page.selectOption('select[name="region_format"]', 'custom');
    await page.selectOption('select[name="time_format"]', '24h');
    await page.selectOption('select[name="week_start"]', 'sunday');
    await page.selectOption('select[name="date_format_type"]', 'iso');

    await page.click('button[type="submit"]:has-text("Save")');
    await elementHelper.waitForPageReady();

    // Verify custom format applied with mixed settings
    await expect(regionFormatSelect).toHaveValue('custom');
    await expect(page.locator('select[name="time_format"]')).toHaveValue('24h');
    await expect(page.locator('select[name="week_start"]')).toHaveValue('sunday');
    await expect(page.locator('select[name="date_format_type"]')).toHaveValue('iso');
  });
});
import { test, expect } from '@playwright/test';

test.describe('Locale API Test', () => {
  let testUserEmail: string;
  let testUserName: string;

  test.beforeEach(async ({ page }) => {
    testUserEmail = `api-test-${Date.now()}@example.com`;
    testUserName = `API Test User ${Date.now()}`;

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

    if (page.url().includes('/onboarding')) {
      const skipButton = page.locator('button:has-text("Skip"), a:has-text("Skip")').first();
      if (await skipButton.isVisible({ timeout: 5000 })) {
        await skipButton.click();
        await page.waitForLoadState('networkidle');
      } else {
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
      }
    }
  });

  test('should make successful locale API call directly', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Test the API call directly
    const response = await page.evaluate(async () => {
      try {
        const response = await fetch('/locale', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ locale: 'ru' })
        });

        const data = await response.json();
        return {
          status: response.status,
          success: data.success,
          locale: data.locale,
          message: data.message
        };
      } catch (error) {
        return {
          error: error.message
        };
      }
    });

    console.log('API Response:', response);

    // Check API response
    expect(response.status).toBe(200);
    expect(response.success).toBe(true);
    expect(response.locale).toBe('ru');
  });

  test('should verify translations endpoint works', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Test the translations endpoint
    const translationResponse = await page.evaluate(async () => {
      try {
        const response = await fetch('/translations/ru', {
          method: 'GET',
          headers: {
            'Accept': 'application/json'
          }
        });

        const data = await response.json();
        return {
          status: response.status,
          success: data.success,
          locale: data.locale,
          hasTranslations: data.translations && Object.keys(data.translations).length > 0
        };
      } catch (error) {
        return {
          error: error.message
        };
      }
    });

    console.log('Translations Response:', translationResponse);

    // Check translations response
    expect(translationResponse.status).toBe(200);
    expect(translationResponse.success).toBe(true);
    expect(translationResponse.locale).toBe('ru');
    expect(translationResponse.hasTranslations).toBe(true);
  });

  test('should check if Russian translation file exists', async ({ page }) => {
    await page.goto('/lang/ru.json');
    await page.waitForLoadState('networkidle');

    // Should not get a 404 error
    const content = await page.textContent('body');
    expect(content).not.toContain('Not Found');
    expect(content).not.toContain('404');

    // Should be valid JSON
    const jsonContent = await page.evaluate(() => {
      try {
        return JSON.parse(document.body.textContent || '{}');
      } catch (error) {
        return { error: error.message };
      }
    });

    expect(jsonContent.error).toBeUndefined();
    expect(typeof jsonContent).toBe('object');
  });
});
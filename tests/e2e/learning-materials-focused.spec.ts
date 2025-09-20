import { test, expect } from '@playwright/test';

test.describe('Learning Materials - Focused Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to login page first
    await page.goto('/login');

    // Check if we need to register or can login
    const hasLoginForm = await page.locator('#email').count() > 0;

    if (!hasLoginForm) {
      // Navigate to registration if no login form
      await page.goto('/register');

      // Fill in registration form
      await page.fill('#name', 'Test Parent');
      await page.fill('#email', `testparent${Date.now()}@example.com`);
      await page.fill('#password', 'password123');
      await page.fill('#password_confirmation', 'password123');
      await page.click('button[type="submit"]');

      // Wait for dashboard
      await expect(page).toHaveURL('/dashboard');
    } else {
      // Try logging in with a test account
      await page.fill('#email', 'test@example.com');
      await page.fill('#password', 'password');
      await page.click('button[type="submit"]');
    }

    // Navigate to subjects page to create test data
    await page.goto('/subjects');
  });

  test('should add and display video materials correctly', async ({ page }) => {
    // Navigate directly to a topic or create minimal test setup
    await page.goto('/subjects');

    // Look for existing subjects or create one
    const existingSubject = await page.locator('text=Math, text=Science, text=English').first();

    if (await existingSubject.count() > 0) {
      await existingSubject.click();
    } else {
      // Create a minimal subject for testing
      await page.click('text=Create Subject');
      await page.fill('#name', 'Test Subject');
      await page.selectOption('#color', '#10B981');
      await page.click('button[type="submit"]');
      await page.click('text=Test Subject');
    }

    // Look for existing units or create one
    const existingUnit = await page.locator('text=Unit, text=Chapter').first();

    if (await existingUnit.count() > 0) {
      await existingUnit.click();
    } else {
      await page.click('text=Add Unit');
      await page.fill('#title', 'Test Unit');
      await page.click('button[type="submit"]');
      await page.click('text=Test Unit');
    }

    // Look for existing topics or create one
    const existingTopic = await page.locator('text=Topic, text=Lesson').first();

    if (await existingTopic.count() > 0) {
      await existingTopic.click();
    } else {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Test Topic');
      await page.fill('#estimated_minutes', '30');
      await page.click('button[type="submit"]');
      await page.click('text=Test Topic');
    }

    // Now we should be on a topic page - test materials
    await page.click('text=Edit Topic');

    // Wait for modal to appear
    await expect(page.locator('#topic-modal')).toBeVisible();

    // Click on Materials tab
    await page.click('text=Learning Materials');

    // Add a YouTube video
    await page.click('text=Add Video');
    await page.fill('[data-testid="video-url"]', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
    await page.fill('[data-testid="video-title"]', 'Test YouTube Video');
    await page.fill('[data-testid="video-description"]', 'A test video for learning materials');
    await page.click('[data-testid="add-video-submit"]');

    // Verify video was added
    await expect(page.locator('text=Videos (1)')).toBeVisible();
    await expect(page.locator('text=Test YouTube Video')).toBeVisible();
    await expect(page.locator('text=YouTube')).toBeVisible();

    // Close modal
    await page.click('[data-testid="close-modal"]');

    // Verify materials are displayed on the topic page
    await expect(page.locator('text=Learning Materials')).toBeVisible();
    await expect(page.locator('text=Test YouTube Video')).toBeVisible();
  });

  test('should add and display link materials correctly', async ({ page }) => {
    // Assume we're on a topic page from previous setup
    await page.goto('/subjects');

    // Find any existing topic or use the one from previous test
    const topicLink = await page.locator('a[href*="/topics/"]').first();
    if (await topicLink.count() > 0) {
      await topicLink.click();
    } else {
      // Skip this test if no topics exist
      test.skip('No topics available for testing');
    }

    // Edit topic to add materials
    await page.click('text=Edit Topic');
    await expect(page.locator('#topic-modal')).toBeVisible();
    await page.click('text=Learning Materials');

    // Add a link
    await page.click('text=Add Link');
    await page.fill('[data-testid="link-url"]', 'https://khanacademy.org/math');
    await page.fill('[data-testid="link-title"]', 'Khan Academy Math');
    await page.fill('[data-testid="link-description"]', 'Educational math resources');
    await page.click('[data-testid="add-link-submit"]');

    // Verify link was added
    await expect(page.locator('text=Links (1)')).toBeVisible();
    await expect(page.locator('text=Khan Academy Math')).toBeVisible();
    await expect(page.locator('text=khanacademy.org')).toBeVisible();
  });

  test('should handle file upload correctly', async ({ page }) => {
    // Navigate to any existing topic
    await page.goto('/subjects');

    const topicLink = await page.locator('a[href*="/topics/"]').first();
    if (await topicLink.count() > 0) {
      await topicLink.click();
    } else {
      test.skip('No topics available for testing');
    }

    // Edit topic to add materials
    await page.click('text=Edit Topic');
    await expect(page.locator('#topic-modal')).toBeVisible();
    await page.click('text=Learning Materials');

    // Add a file (create a simple test file)
    await page.click('text=Upload File');

    // Create a simple test file content
    const testFileContent = 'Test PDF content\n%PDF-1.4\nTest content for learning materials';
    const buffer = Buffer.from(testFileContent);

    await page.setInputFiles('[data-testid="file-upload"]', {
      name: 'test-worksheet.pdf',
      mimeType: 'application/pdf',
      buffer: buffer,
    });

    await page.fill('[data-testid="file-title"]', 'Math Worksheet');
    await page.click('[data-testid="add-file-submit"]');

    // Verify file was added
    await expect(page.locator('text=Files (1)')).toBeVisible();
    await expect(page.locator('text=Math Worksheet')).toBeVisible();
    await expect(page.locator('text=PDF')).toBeVisible();
  });

  test('should display materials in kids-friendly format', async ({ page }) => {
    // Navigate to a topic with materials
    await page.goto('/subjects');

    const topicLink = await page.locator('a[href*="/topics/"]').first();
    if (await topicLink.count() > 0) {
      await topicLink.click();
    } else {
      test.skip('No topics available for testing');
    }

    // Check if materials are displayed
    const materialsSection = await page.locator('text=Learning Materials');
    if (await materialsSection.count() > 0) {
      // Verify the layout is kid-friendly
      await expect(page.locator('.material-card')).toBeVisible();

      // Check for large touch targets
      const videoCards = await page.locator('.video-card');
      if (await videoCards.count() > 0) {
        const firstCard = videoCards.first();
        const boundingBox = await firstCard.boundingBox();
        expect(boundingBox?.height).toBeGreaterThan(200); // Minimum height for kids
      }

      // Check for clear visual feedback
      await expect(page.locator('.material-card')).toHaveClass(/hover:scale-105/);
    }
  });

  test('should work on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });

    // Navigate to a topic with materials
    await page.goto('/subjects');

    const topicLink = await page.locator('a[href*="/topics/"]').first();
    if (await topicLink.count() > 0) {
      await topicLink.click();
    } else {
      test.skip('No topics available for testing');
    }

    // Check that materials are still accessible on mobile
    const materialsSection = await page.locator('text=Learning Materials');
    if (await materialsSection.count() > 0) {
      await expect(materialsSection).toBeVisible();

      // Check that buttons are touch-friendly
      const buttons = await page.locator('button, a[role="button"]');
      if (await buttons.count() > 0) {
        const firstButton = buttons.first();
        const boundingBox = await firstButton.boundingBox();
        expect(boundingBox?.height).toBeGreaterThan(40); // Minimum touch target
      }
    }
  });

  test('should handle video URL validation', async ({ page }) => {
    // Navigate to any existing topic
    await page.goto('/subjects');

    const topicLink = await page.locator('a[href*="/topics/"]').first();
    if (await topicLink.count() > 0) {
      await topicLink.click();
    } else {
      test.skip('No topics available for testing');
    }

    // Edit topic to test validation
    await page.click('text=Edit Topic');
    await expect(page.locator('#topic-modal')).toBeVisible();
    await page.click('text=Learning Materials');

    // Try to add an invalid video URL
    await page.click('text=Add Video');
    await page.fill('[data-testid="video-url"]', 'https://example.com/invalid-video');
    await page.fill('[data-testid="video-title"]', 'Invalid Video');
    await page.click('[data-testid="add-video-submit"]');

    // Should show error message
    await expect(page.locator('text=allowed domain')).toBeVisible();
  });

  test('should handle empty states gracefully', async ({ page }) => {
    // Navigate to a topic without materials
    await page.goto('/subjects');

    const topicLink = await page.locator('a[href*="/topics/"]').first();
    if (await topicLink.count() > 0) {
      await topicLink.click();
    } else {
      test.skip('No topics available for testing');
    }

    // Check for empty state message if no materials exist
    const noMaterialsMessage = await page.locator('text=No learning materials');
    if (await noMaterialsMessage.count() > 0) {
      await expect(noMaterialsMessage).toBeVisible();
      await expect(page.locator('text=Click "Edit Topic" to add')).toBeVisible();
    }
  });
});
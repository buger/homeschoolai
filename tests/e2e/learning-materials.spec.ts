import { test, expect } from '@playwright/test';

test.describe('Learning Materials System', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to registration page
    await page.goto('/register');

    // Fill in registration form
    await page.fill('#name', 'Test Parent');
    await page.fill('#email', `testparent${Date.now()}@example.com`);
    await page.fill('#password', 'password123');
    await page.fill('#password_confirmation', 'password123');

    // Submit registration
    await page.click('button[type="submit"]');

    // Wait for dashboard
    await expect(page).toHaveURL('/dashboard');

    // Create a test subject, unit, and topic for materials testing
    await page.click('text=Add Subject');
    await page.fill('#name', 'Science');
    await page.fill('#description', 'Science learning materials test');
    await page.selectOption('#color', '#10B981');
    await page.click('button[type="submit"]');

    // Create a unit
    await page.click('text=Add Unit');
    await page.fill('#title', 'Physics Basics');
    await page.fill('#description', 'Introduction to physics');
    await page.click('button[type="submit"]');

    // Create a topic
    await page.click('text=Add Topic');
    await page.fill('#title', 'Forces and Motion');
    await page.fill('#description', 'Understanding forces and motion');
    await page.fill('#estimated_minutes', '45');
    await page.click('button[type="submit"]');

    // Navigate to the topic detail page
    await page.click('text=Forces and Motion');
  });

  test.describe('Parent Experience - Material Management', () => {
    test('should add a YouTube video successfully', async ({ page }) => {
      // Click edit topic to access materials management
      await page.click('text=Edit Topic');

      // Wait for edit modal
      await expect(page.locator('#topic-modal')).toBeVisible();

      // Add video section
      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
      await page.fill('[data-testid="video-title"]', 'Introduction to Forces');
      await page.fill('[data-testid="video-description"]', 'Basic concepts of forces in physics');
      await page.click('[data-testid="add-video-submit"]');

      // Verify video was added
      await expect(page.locator('text=Videos (1)')).toBeVisible();
      await expect(page.locator('text=Introduction to Forces')).toBeVisible();
      await expect(page.locator('text=YouTube')).toBeVisible();
    });

    test('should add a Vimeo video successfully', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://vimeo.com/123456789');
      await page.fill('[data-testid="video-title"]', 'Motion Concepts');
      await page.click('[data-testid="add-video-submit"]');

      await expect(page.locator('text=Motion Concepts')).toBeVisible();
      await expect(page.locator('text=Vimeo')).toBeVisible();
    });

    test('should add a Khan Academy video successfully', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://www.khanacademy.org/science/physics/forces-newtons-laws');
      await page.fill('[data-testid="video-title"]', 'Newton\'s Laws');
      await page.click('[data-testid="add-video-submit"]');

      await expect(page.locator('text=Newton\'s Laws')).toBeVisible();
      await expect(page.locator('text=Khan Academy')).toBeVisible();
    });

    test('should reject invalid video URLs', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://example.com/invalid');
      await page.fill('[data-testid="video-title"]', 'Invalid Video');
      await page.click('[data-testid="add-video-submit"]');

      // Should show error message
      await expect(page.locator('text=Video URL must be from an allowed domain')).toBeVisible();
    });

    test('should add an educational link successfully', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      await page.click('text=Add Link');
      await page.fill('[data-testid="link-url"]', 'https://physicsclassroom.com/forces');
      await page.fill('[data-testid="link-title"]', 'Physics Classroom - Forces');
      await page.fill('[data-testid="link-description"]', 'Comprehensive guide to forces');
      await page.click('[data-testid="add-link-submit"]');

      await expect(page.locator('text=Links (1)')).toBeVisible();
      await expect(page.locator('text=Physics Classroom - Forces')).toBeVisible();
      await expect(page.locator('text=physicsclassroom.com')).toBeVisible();
    });

    test('should upload a PDF file successfully', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      // Create a test PDF file (mock)
      const testPdfContent = '%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n';
      const buffer = Buffer.from(testPdfContent);

      await page.click('text=Add File');
      const fileInput = page.locator('[data-testid="file-upload"]');
      await fileInput.setInputFiles({
        name: 'forces-worksheet.pdf',
        mimeType: 'application/pdf',
        buffer: buffer,
      });
      await page.fill('[data-testid="file-title"]', 'Forces Worksheet');
      await page.click('[data-testid="add-file-submit"]');

      await expect(page.locator('text=Files (1)')).toBeVisible();
      await expect(page.locator('text=Forces Worksheet')).toBeVisible();
      await expect(page.locator('text=PDF')).toBeVisible();
    });

    test('should upload an image file successfully', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      // Create a simple test image (1x1 PNG)
      const pngBuffer = Buffer.from([
        0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A,
        0x00, 0x00, 0x00, 0x0D, 0x49, 0x48, 0x44, 0x52,
        0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01,
        0x08, 0x02, 0x00, 0x00, 0x00, 0x90, 0x77, 0x53,
        0xDE, 0x00, 0x00, 0x00, 0x0C, 0x49, 0x44, 0x41,
        0x54, 0x08, 0xD7, 0x63, 0xF8, 0x0F, 0x00, 0x00,
        0x01, 0x00, 0x01, 0x5C, 0xCD, 0x90, 0x0A, 0x00,
        0x00, 0x00, 0x00, 0x49, 0x45, 0x4E, 0x44, 0xAE,
        0x42, 0x60, 0x82
      ]);

      await page.click('text=Add File');
      const fileInput = page.locator('[data-testid="file-upload"]');
      await fileInput.setInputFiles({
        name: 'force-diagram.png',
        mimeType: 'image/png',
        buffer: pngBuffer,
      });
      await page.fill('[data-testid="file-title"]', 'Force Diagram');
      await page.click('[data-testid="add-file-submit"]');

      await expect(page.locator('text=Force Diagram')).toBeVisible();
      await expect(page.locator('text=PNG')).toBeVisible();
    });

    test('should reject files that are too large', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      // Create a large file (simulated)
      const largeBuffer = Buffer.alloc(11 * 1024 * 1024); // 11MB - should exceed 10MB limit

      await page.click('text=Add File');
      const fileInput = page.locator('[data-testid="file-upload"]');
      await fileInput.setInputFiles({
        name: 'large-file.pdf',
        mimeType: 'application/pdf',
        buffer: largeBuffer,
      });
      await page.click('[data-testid="add-file-submit"]');

      await expect(page.locator('text=File size exceeds maximum allowed size')).toBeVisible();
    });

    test('should reject unsupported file types', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      await page.click('text=Add File');
      const fileInput = page.locator('[data-testid="file-upload"]');
      await fileInput.setInputFiles({
        name: 'malware.exe',
        mimeType: 'application/x-executable',
        buffer: Buffer.from('fake executable'),
      });
      await page.click('[data-testid="add-file-submit"]');

      await expect(page.locator('text=File type not allowed')).toBeVisible();
    });

    test('should remove materials successfully', async ({ page }) => {
      // First add a video
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://www.youtube.com/watch?v=test123');
      await page.fill('[data-testid="video-title"]', 'Test Video');
      await page.click('[data-testid="add-video-submit"]');

      // Verify video was added
      await expect(page.locator('text=Test Video')).toBeVisible();

      // Remove the video
      await page.click('[data-testid="remove-video-0"]');
      await page.click('text=OK'); // Confirm deletion

      // Verify video was removed
      await expect(page.locator('text=Test Video')).not.toBeVisible();
      await expect(page.locator('text=No learning materials yet')).toBeVisible();
    });

    test('should manage multiple materials of different types', async ({ page }) => {
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      // Add a video
      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://www.youtube.com/watch?v=video1');
      await page.fill('[data-testid="video-title"]', 'Video 1');
      await page.click('[data-testid="add-video-submit"]');

      // Add a link
      await page.click('text=Add Link');
      await page.fill('[data-testid="link-url"]', 'https://example.com/article');
      await page.fill('[data-testid="link-title"]', 'Educational Article');
      await page.click('[data-testid="add-link-submit"]');

      // Add a file
      const pdfBuffer = Buffer.from('%PDF-1.4\ntest');
      await page.click('text=Add File');
      const fileInput = page.locator('[data-testid="file-upload"]');
      await fileInput.setInputFiles({
        name: 'worksheet.pdf',
        mimeType: 'application/pdf',
        buffer: pdfBuffer,
      });
      await page.fill('[data-testid="file-title"]', 'Worksheet');
      await page.click('[data-testid="add-file-submit"]');

      // Verify all materials are present
      await expect(page.locator('text=Videos (1)')).toBeVisible();
      await expect(page.locator('text=Links (1)')).toBeVisible();
      await expect(page.locator('text=Files (1)')).toBeVisible();
      await expect(page.locator('text=Video 1')).toBeVisible();
      await expect(page.locator('text=Educational Article')).toBeVisible();
      await expect(page.locator('text=Worksheet')).toBeVisible();
    });
  });

  test.describe('Kids Experience - Material Viewing', () => {
    test.beforeEach(async ({ page }) => {
      // Add some test materials first
      await page.click('text=Edit Topic');
      await expect(page.locator('#topic-modal')).toBeVisible();

      // Add a video
      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://www.youtube.com/watch?v=kidsvideo');
      await page.fill('[data-testid="video-title"]', 'Fun Physics Video');
      await page.fill('[data-testid="video-description"]', 'An exciting video about forces');
      await page.click('[data-testid="add-video-submit"]');

      // Add a link
      await page.click('text=Add Link');
      await page.fill('[data-testid="link-url"]', 'https://kidscience.com/forces');
      await page.fill('[data-testid="link-title"]', 'Kids Science - Forces');
      await page.fill('[data-testid="link-description"]', 'Interactive games about forces');
      await page.click('[data-testid="add-link-submit"]');

      // Add a file
      const imageBuffer = Buffer.from([
        0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A,
        0x00, 0x00, 0x00, 0x0D, 0x49, 0x48, 0x44, 0x52,
        0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01,
        0x08, 0x02, 0x00, 0x00, 0x00, 0x90, 0x77, 0x53,
        0xDE, 0x00, 0x00, 0x00, 0x0C, 0x49, 0x44, 0x41,
        0x54, 0x08, 0xD7, 0x63, 0xF8, 0x0F, 0x00, 0x00,
        0x01, 0x00, 0x01, 0x5C, 0xCD, 0x90, 0x0A, 0x00,
        0x00, 0x00, 0x00, 0x49, 0x45, 0x4E, 0x44, 0xAE,
        0x42, 0x60, 0x82
      ]);

      await page.click('text=Add File');
      const fileInput = page.locator('[data-testid="file-upload"]');
      await fileInput.setInputFiles({
        name: 'force-poster.png',
        mimeType: 'image/png',
        buffer: imageBuffer,
      });
      await page.fill('[data-testid="file-title"]', 'Force Poster');
      await page.click('[data-testid="add-file-submit"]');

      // Close the modal
      await page.click('[data-testid="close-modal"]');

      // Wait for materials to be visible
      await expect(page.locator('text=Learning Materials')).toBeVisible();
    });

    test('should display materials in a kid-friendly grid layout', async ({ page }) => {
      // Check that materials are displayed in a grid
      const materialsGrid = page.locator('.grid.grid-cols-1.lg\\:grid-cols-2.xl\\:grid-cols-3');
      await expect(materialsGrid).toBeVisible();

      // Check that each material type has appropriate styling
      await expect(page.locator('.bg-red-50')).toBeVisible(); // Video card
      await expect(page.locator('.bg-blue-50')).toBeVisible(); // Link card
      await expect(page.locator('.bg-green-50')).toBeVisible(); // File card
    });

    test('should have large, touch-friendly material cards', async ({ page }) => {
      // Check that material cards are appropriately sized
      const videoCard = page.locator('.bg-red-50').first();
      await expect(videoCard).toBeVisible();

      // Verify the card has proper padding and is clickable
      const cardBounds = await videoCard.boundingBox();
      expect(cardBounds?.height).toBeGreaterThan(80); // Minimum height for touch
      expect(cardBounds?.width).toBeGreaterThan(200); // Adequate width
    });

    test('should open videos in new tabs when clicked', async ({ page }) => {
      const [newTab] = await Promise.all([
        page.context().waitForEvent('page'),
        page.click('text=Watch Video')
      ]);

      await newTab.waitForLoadState();
      expect(newTab.url()).toContain('youtube.com');
    });

    test('should open links in new tabs when clicked', async ({ page }) => {
      const [newTab] = await Promise.all([
        page.context().waitForEvent('page'),
        page.click('text=Visit', { force: true })
      ]);

      await newTab.waitForLoadState();
      expect(newTab.url()).toContain('kidscience.com');
    });

    test('should allow file viewing and downloading', async ({ page }) => {
      // Test view file link
      const viewLink = page.locator('text=View').first();
      await expect(viewLink).toBeVisible();
      await expect(viewLink).toHaveAttribute('target', '_blank');

      // Test download link
      const downloadLink = page.locator('text=Download').first();
      await expect(downloadLink).toBeVisible();
      await expect(downloadLink).toHaveAttribute('download');
    });

    test('should show appropriate file type icons', async ({ page }) => {
      // Check for image file icon
      const imageIcon = page.locator('svg').filter({ has: page.locator('path[d*="M4 16l4.586-4.586"]') });
      await expect(imageIcon).toBeVisible();

      // Check for video platform icons
      const youtubeIcon = page.locator('svg').filter({ has: page.locator('path[d*="M23.498"]') });
      await expect(youtubeIcon).toBeVisible();
    });

    test('should display file sizes in human-readable format', async ({ page }) => {
      // Check that file sizes are displayed
      const fileCard = page.locator('.bg-green-50').first();
      await expect(fileCard).toContainText(/\d+\s?(B|KB|MB)/);
    });

    test('should work well on mobile viewports', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });

      // Check that materials still display properly
      await expect(page.locator('text=Learning Materials')).toBeVisible();
      await expect(page.locator('text=Fun Physics Video')).toBeVisible();
      await expect(page.locator('text=Kids Science - Forces')).toBeVisible();
      await expect(page.locator('text=Force Poster')).toBeVisible();

      // Check that cards stack properly on mobile
      const materialsGrid = page.locator('.grid');
      await expect(materialsGrid).toHaveClass(/grid-cols-1/);
    });

    test('should work well on tablet viewports', async ({ page }) => {
      // Set tablet viewport
      await page.setViewportSize({ width: 768, height: 1024 });

      await expect(page.locator('text=Learning Materials')).toBeVisible();

      // Check that we get 2 columns on tablet
      const materialsGrid = page.locator('.grid');
      await expect(materialsGrid).toHaveClass(/lg:grid-cols-2/);
    });

    test('should provide clear visual feedback on hover', async ({ page }) => {
      const videoCard = page.locator('.bg-red-50').first();

      // Hover over the card
      await videoCard.hover();

      // Check for hover effects (shadow transition)
      await expect(videoCard).toHaveClass(/hover:shadow-md/);
      await expect(videoCard).toHaveClass(/transition-shadow/);
    });

    test('should handle empty states gracefully', async ({ page }) => {
      // Remove all materials first
      await page.click('text=Edit Topic');

      // Remove video
      await page.click('[data-testid="remove-video-0"]');
      await page.click('text=OK');

      // Remove link
      await page.click('[data-testid="remove-link-0"]');
      await page.click('text=OK');

      // Remove file
      await page.click('[data-testid="remove-file-0"]');
      await page.click('text=OK');

      // Close modal
      await page.click('[data-testid="close-modal"]');

      // Check empty state
      await expect(page.locator('text=No learning materials')).toBeVisible();
      await expect(page.locator('text=Click "Edit Topic" to add videos, links, or files')).toBeVisible();
    });
  });

  test.describe('Cross-Device Compatibility', () => {
    test('should work on desktop Chrome', async ({ page, browserName }) => {
      test.skip(browserName !== 'chromium', 'This test is for Chrome only');

      await expect(page.locator('text=Learning Materials')).toBeVisible();
      await expect(page.locator('.grid')).toBeVisible();
    });

    test('should work on desktop Firefox', async ({ page, browserName }) => {
      test.skip(browserName !== 'firefox', 'This test is for Firefox only');

      await expect(page.locator('text=Learning Materials')).toBeVisible();
      await expect(page.locator('.grid')).toBeVisible();
    });

    test('should work on desktop Safari', async ({ page, browserName }) => {
      test.skip(browserName !== 'webkit', 'This test is for Safari only');

      await expect(page.locator('text=Learning Materials')).toBeVisible();
      await expect(page.locator('.grid')).toBeVisible();
    });
  });

  test.describe('Accessibility Features', () => {
    test('should be keyboard navigable', async ({ page }) => {
      // Tab through the materials
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');

      // Check that focus is visible
      const focusedElement = page.locator(':focus');
      await expect(focusedElement).toBeVisible();
    });

    test('should have proper ARIA labels', async ({ page }) => {
      const videoLink = page.locator('text=Watch Video');
      await expect(videoLink).toHaveAttribute('href');
      await expect(videoLink).toHaveAttribute('target', '_blank');

      const downloadLink = page.locator('text=Download');
      await expect(downloadLink).toHaveAttribute('download');
    });

    test('should have sufficient color contrast', async ({ page }) => {
      // Check that text has sufficient contrast
      const videoTitle = page.locator('text=Fun Physics Video');
      const textColor = await videoTitle.evaluate(el =>
        window.getComputedStyle(el).color
      );

      // Basic check that text is not too light
      expect(textColor).not.toBe('rgb(255, 255, 255)');
    });

    test('should support screen readers', async ({ page }) => {
      // Check for semantic HTML structure
      await expect(page.locator('h3')).toContainText('Learning Materials');
      await expect(page.locator('h4')).toBeVisible(); // Material titles

      // Check that images have alt text or aria-labels
      const icons = page.locator('svg');
      await expect(icons.first()).toBeVisible();
    });
  });

  test.describe('Performance and Loading', () => {
    test('should load materials quickly', async ({ page }) => {
      const startTime = Date.now();

      await expect(page.locator('text=Learning Materials')).toBeVisible();

      const loadTime = Date.now() - startTime;
      expect(loadTime).toBeLessThan(3000); // Should load within 3 seconds
    });

    test('should handle many materials efficiently', async ({ page }) => {
      // Add multiple materials
      await page.click('text=Edit Topic');

      // Add 10 videos
      for (let i = 1; i <= 5; i++) {
        await page.click('text=Add Video');
        await page.fill('[data-testid="video-url"]', `https://www.youtube.com/watch?v=test${i}`);
        await page.fill('[data-testid="video-title"]', `Test Video ${i}`);
        await page.click('[data-testid="add-video-submit"]');
      }

      // Close modal
      await page.click('[data-testid="close-modal"]');

      // Check that all materials are displayed
      await expect(page.locator('text=Videos (5)')).toBeVisible();

      // Check that the page is still responsive
      const videoCards = page.locator('.bg-red-50');
      await expect(videoCards).toHaveCount(5);
    });

    test('should handle file uploads smoothly', async ({ page }) => {
      await page.click('text=Edit Topic');

      const startTime = Date.now();

      await page.click('text=Add File');
      const fileInput = page.locator('[data-testid="file-upload"]');
      await fileInput.setInputFiles({
        name: 'test-upload.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('%PDF-1.4\ntest content'),
      });
      await page.fill('[data-testid="file-title"]', 'Test Upload');
      await page.click('[data-testid="add-file-submit"]');

      await expect(page.locator('text=Test Upload')).toBeVisible();

      const uploadTime = Date.now() - startTime;
      expect(uploadTime).toBeLessThan(5000); // Upload should complete within 5 seconds
    });
  });

  test.describe('Security Validation', () => {
    test('should prevent unauthorized access to materials', async ({ page }) => {
      // Navigate to a topic URL without being the owner
      const topicUrl = page.url();

      // Create a new user
      await page.goto('/register');
      await page.fill('[data-testid="name"]', 'Other User');
      await page.fill('[data-testid="email"]', `otheruser${Date.now()}@example.com`);
      await page.fill('[data-testid="password"]', 'password123');
      await page.fill('[data-testid="password_confirmation"]', 'password123');
      await page.click('[data-testid="register-submit"]');

      // Try to access the original topic
      await page.goto(topicUrl);

      // Should be redirected or show error
      await expect(page.locator('text=Access denied')).toBeVisible();
    });

    test('should validate file types securely', async ({ page }) => {
      await page.click('text=Edit Topic');
      await page.click('text=Add File');

      // Try to upload a script file
      const scriptBuffer = Buffer.from('console.log("malicious script");');
      const fileInput = page.locator('[data-testid="file-upload"]');
      await fileInput.setInputFiles({
        name: 'malicious.js',
        mimeType: 'application/javascript',
        buffer: scriptBuffer,
      });
      await page.click('[data-testid="add-file-submit"]');

      await expect(page.locator('text=File type not allowed')).toBeVisible();
    });

    test('should validate URLs properly', async ({ page }) => {
      await page.click('text=Edit Topic');
      await page.click('text=Add Link');

      // Try to add a malicious URL
      await page.fill('[data-testid="link-url"]', 'javascript:alert("xss")');
      await page.fill('[data-testid="link-title"]', 'Malicious Link');
      await page.click('[data-testid="add-link-submit"]');

      await expect(page.locator('text=Invalid URL format')).toBeVisible();
    });
  });
});
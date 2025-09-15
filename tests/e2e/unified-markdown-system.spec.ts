import { test, expect } from '@playwright/test';

/**
 * Comprehensive E2E Test Suite for Unified Markdown Learning Materials System
 *
 * This test suite covers all phases of the unified markdown system:
 * - Phase 1: Database schema and migration
 * - Phase 2: GitHub-style markdown editor with drag-drop
 * - Phase 3: Enhanced markdown rendering with video/file embedding
 * - Phase 4: Unified editor interface with live preview
 * - Phase 5: Smart clipboard and drag-drop file handling
 * - Phase 6: File management and security features
 * - Phase 7: Beautiful kids view rendering
 * - Phase 8: Comprehensive testing and optimization (this file)
 */

test.describe('Unified Markdown Learning Materials System', () => {
  let testUser: {
    name: string;
    email: string;
    subjectId?: string;
    unitId?: string;
    topicId?: string;
  };

  test.beforeEach(async ({ page }) => {
    // Create unique test user for each test
    testUser = {
      name: 'Test Parent',
      email: `testparent${Date.now()}@example.com`
    };

    // Navigate to registration page
    await page.goto('/register');

    // Fill in registration form
    await page.fill('#name', testUser.name);
    await page.fill('#email', testUser.email);
    await page.fill('#password', 'password123');
    await page.fill('#password_confirmation', 'password123');

    // Submit registration
    await page.click('button[type="submit"]');

    // Wait for dashboard
    await expect(page).toHaveURL('/dashboard');

    // Create test subject and unit structure
    await setupTestSubjectAndUnit(page);
  });

  async function setupTestSubjectAndUnit(page: any) {
    // Create test subject
    await page.click('text=Add Subject');
    await page.fill('#name', 'Unified Content Testing');
    await page.fill('#description', 'Testing unified markdown content system');
    await page.selectOption('#color', '#10B981');
    await page.click('button[type="submit"]');

    // Create test unit
    await page.click('text=Add Unit');
    await page.fill('#title', 'Unified Content Unit');
    await page.fill('#description', 'Unit for testing unified content features');
    await page.click('button[type="submit"]');
  }

  test.describe('Phase 1 & 2: Database Schema & GitHub-Style Editor', () => {
    test('should create topic with unified content structure', async ({ page }) => {
      // Create topic with unified content
      await page.click('text=Add Topic');
      await page.fill('#title', 'Unified Content Topic');
      await page.fill('#description', 'Initial description content');
      await page.fill('#estimated_minutes', '30');
      await page.click('button[type="submit"]');

      // Navigate to topic detail
      await page.click('text=Unified Content Topic');

      // Verify unified content structure exists
      await expect(page.locator('[data-testid="topic-content-container"]')).toBeVisible();
      await expect(page.locator('text=Initial description content')).toBeVisible();
    });

    test('should migrate legacy content to unified format', async ({ page }) => {
      // Create topic with legacy materials first
      await page.click('text=Add Topic');
      await page.fill('#title', 'Legacy Topic');
      await page.fill('#description', 'Legacy description');
      await page.click('button[type="submit"]');

      // Add legacy materials
      await page.click('text=Legacy Topic');
      await page.click('text=Edit Topic');

      // Add video material
      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
      await page.fill('[data-testid="video-title"]', 'Legacy Video');
      await page.click('[data-testid="add-video-submit"]');

      // Close modal and trigger migration
      await page.click('[data-testid="close-modal"]');

      // Trigger migration to unified format
      await page.click('[data-testid="migrate-to-unified"]');
      await page.click('text=Confirm Migration');

      // Verify unified content includes migrated materials
      await expect(page.locator('text=## Video Resources')).toBeVisible();
      await expect(page.locator('text=Legacy Video')).toBeVisible();
      await expect(page.locator('[data-testid="unified-content-editor"]')).toBeVisible();
    });

    test('should provide GitHub-style markdown editor interface', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Markdown Editor Test');
      await page.fill('#description', 'Test markdown editor');
      await page.click('button[type="submit"]');

      await page.click('text=Markdown Editor Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Verify GitHub-style editor components
      await expect(page.locator('[data-testid="markdown-editor-toolbar"]')).toBeVisible();
      await expect(page.locator('[data-testid="markdown-textarea"]')).toBeVisible();
      await expect(page.locator('[data-testid="markdown-preview"]')).toBeVisible();
      await expect(page.locator('[data-testid="markdown-split-view"]')).toBeVisible();

      // Test toolbar buttons
      await expect(page.locator('[data-testid="bold-button"]')).toBeVisible();
      await expect(page.locator('[data-testid="italic-button"]')).toBeVisible();
      await expect(page.locator('[data-testid="heading-button"]')).toBeVisible();
      await expect(page.locator('[data-testid="link-button"]')).toBeVisible();
      await expect(page.locator('[data-testid="image-button"]')).toBeVisible();
      await expect(page.locator('[data-testid="list-button"]')).toBeVisible();
    });

    test('should support drag-and-drop file uploads in editor', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Drag Drop Test');
      await page.fill('#description', 'Test drag and drop');
      await page.click('button[type="submit"]');

      await page.click('text=Drag Drop Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Test file drop zone
      const dropZone = page.locator('[data-testid="markdown-drop-zone"]');
      await expect(dropZone).toBeVisible();
      await expect(dropZone).toContainText('Drop files here to upload');

      // Simulate file drop
      const testImage = Buffer.from([
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

      // Use file input fallback for testing
      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles({
        name: 'test-image.png',
        mimeType: 'image/png',
        buffer: testImage,
      });

      // Verify upload progress and completion
      await expect(page.locator('[data-testid="upload-progress"]')).toBeVisible();
      await expect(page.locator('[data-testid="upload-complete"]')).toBeVisible({ timeout: 10000 });

      // Verify markdown is inserted
      const markdownTextarea = page.locator('[data-testid="markdown-textarea"]');
      await expect(markdownTextarea).toContainText('![test-image.png]');
    });
  });

  test.describe('Phase 3 & 4: Enhanced Rendering & Unified Interface', () => {
    test('should render enhanced markdown with video embeds', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Video Embed Test');
      await page.fill('#description', 'Test video embedding');
      await page.click('button[type="submit"]');

      await page.click('text=Video Embed Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Add markdown content with videos
      const markdownContent = `# Video Learning Content

Here's an educational video:

[Introduction to Physics](https://www.youtube.com/watch?v=dQw4w9WgXcQ)

And another from Vimeo:

[Physics Concepts](https://vimeo.com/123456789)

Khan Academy resource:

[Newton's Laws](https://www.khanacademy.org/science/physics/forces-newtons-laws)`;

      await page.fill('[data-testid="markdown-textarea"]', markdownContent);

      // Verify enhanced rendering in preview
      const preview = page.locator('[data-testid="markdown-preview"]');
      await expect(preview.locator('.video-embed-container')).toHaveCount(3);
      await expect(preview.locator('[data-video-type="youtube"]')).toBeVisible();
      await expect(preview.locator('[data-video-type="vimeo"]')).toBeVisible();
      await expect(preview.locator('[data-video-type="khan_academy"]')).toBeVisible();

      // Save and verify live rendering
      await page.click('[data-testid="save-unified-content"]');
      await expect(page.locator('.video-embed-container')).toHaveCount(3);
    });

    test('should render file embeds with preview and download', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'File Embed Test');
      await page.fill('#description', 'Test file embedding');
      await page.click('button[type="submit"]');

      await page.click('text=File Embed Test');
      await page.click('[data-testid="edit-unified-content"]');

      // First upload files, then reference them
      const pdfBuffer = Buffer.from('%PDF-1.4\nTest PDF content');
      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles({
        name: 'worksheet.pdf',
        mimeType: 'application/pdf',
        buffer: pdfBuffer,
      });

      await expect(page.locator('[data-testid="upload-complete"]')).toBeVisible({ timeout: 10000 });

      // Add markdown content referencing the file
      const markdownContent = `# Study Materials

Download the worksheet:

[Physics Worksheet](./uploads/worksheet.pdf)

View it online or download for offline use.`;

      await page.fill('[data-testid="markdown-textarea"]', markdownContent);

      // Verify file embed rendering
      const preview = page.locator('[data-testid="markdown-preview"]');
      await expect(preview.locator('.file-embed')).toBeVisible();
      await expect(preview.locator('[data-file-type="pdf"]')).toBeVisible();
      await expect(preview.locator('[data-testid="file-preview-btn"]')).toBeVisible();
      await expect(preview.locator('[data-testid="file-download-btn"]')).toBeVisible();

      // Save and verify live rendering
      await page.click('[data-testid="save-unified-content"]');
      await expect(page.locator('.file-embed')).toBeVisible();
      await expect(page.locator('[data-testid="file-preview-btn"]')).toBeVisible();
      await expect(page.locator('[data-testid="file-download-btn"]')).toBeVisible();
    });

    test('should provide real-time live preview', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Live Preview Test');
      await page.fill('#description', 'Test live preview');
      await page.click('button[type="submit"]');

      await page.click('text=Live Preview Test');
      await page.click('[data-testid="edit-unified-content"]');

      const markdownTextarea = page.locator('[data-testid="markdown-textarea"]');
      const preview = page.locator('[data-testid="markdown-preview"]');

      // Test typing updates preview in real-time
      await markdownTextarea.fill('# Heading 1');
      await expect(preview.locator('h1')).toContainText('Heading 1');

      await markdownTextarea.fill('# Heading 1\n\n**Bold text**');
      await expect(preview.locator('strong')).toContainText('Bold text');

      await markdownTextarea.fill('# Heading 1\n\n**Bold text**\n\n- List item 1\n- List item 2');
      await expect(preview.locator('ul li')).toHaveCount(2);

      // Test interactive elements
      await markdownTextarea.fill('# Heading 1\n\n> **Note**\n>\n> This is a callout box');
      await expect(preview.locator('blockquote')).toBeVisible();
    });

    test('should handle split view and full preview modes', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'View Mode Test');
      await page.fill('#description', 'Test view modes');
      await page.click('button[type="submit"]');

      await page.click('text=View Mode Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Test split view (default)
      await expect(page.locator('[data-testid="markdown-textarea"]')).toBeVisible();
      await expect(page.locator('[data-testid="markdown-preview"]')).toBeVisible();

      // Switch to preview-only mode
      await page.click('[data-testid="preview-only-mode"]');
      await expect(page.locator('[data-testid="markdown-textarea"]')).not.toBeVisible();
      await expect(page.locator('[data-testid="markdown-preview"]')).toBeVisible();

      // Switch to editor-only mode
      await page.click('[data-testid="editor-only-mode"]');
      await expect(page.locator('[data-testid="markdown-textarea"]')).toBeVisible();
      await expect(page.locator('[data-testid="markdown-preview"]')).not.toBeVisible();

      // Switch back to split view
      await page.click('[data-testid="split-view-mode"]');
      await expect(page.locator('[data-testid="markdown-textarea"]')).toBeVisible();
      await expect(page.locator('[data-testid="markdown-preview"]')).toBeVisible();
    });
  });

  test.describe('Phase 5: Smart Clipboard & Advanced File Handling', () => {
    test('should handle smart clipboard paste operations', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Clipboard Test');
      await page.fill('#description', 'Test clipboard features');
      await page.click('button[type="submit"]');

      await page.click('text=Clipboard Test');
      await page.click('[data-testid="edit-unified-content"]');

      const markdownTextarea = page.locator('[data-testid="markdown-textarea"]');

      // Test pasting URL - should auto-detect and format as link
      await markdownTextarea.focus();
      await page.keyboard.type('Check out this video: ');

      // Simulate pasting a YouTube URL
      await page.evaluate(() => {
        navigator.clipboard.writeText('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
      });
      await page.keyboard.press('Control+v');

      // Should auto-format as video embed
      await expect(markdownTextarea).toContainText('[Video](https://www.youtube.com/watch?v=dQw4w9WgXcQ)');

      // Test pasting image data
      await markdownTextarea.focus();
      await page.keyboard.press('Enter');
      await page.keyboard.press('Enter');

      // Simulate image paste (would be handled by paste event listener)
      await page.evaluate(() => {
        const event = new ClipboardEvent('paste', {
          clipboardData: new DataTransfer()
        });

        // Mock image file
        const file = new File(['fake-image-data'], 'pasted-image.png', { type: 'image/png' });
        event.clipboardData?.items.add(file);

        document.querySelector('[data-testid="markdown-textarea"]')?.dispatchEvent(event);
      });

      // Should trigger image upload and insert markdown
      await expect(page.locator('[data-testid="upload-progress"]')).toBeVisible();
    });

    test('should handle multiple file uploads simultaneously', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Multi Upload Test');
      await page.fill('#description', 'Test multiple uploads');
      await page.click('button[type="submit"]');

      await page.click('text=Multi Upload Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Prepare multiple test files
      const files = [
        {
          name: 'document1.pdf',
          mimeType: 'application/pdf',
          buffer: Buffer.from('%PDF-1.4\nDocument 1')
        },
        {
          name: 'image1.png',
          mimeType: 'image/png',
          buffer: Buffer.from([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A])
        },
        {
          name: 'document2.pdf',
          mimeType: 'application/pdf',
          buffer: Buffer.from('%PDF-1.4\nDocument 2')
        }
      ];

      // Upload multiple files
      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles(files);

      // Verify multiple upload progress indicators
      await expect(page.locator('[data-testid="upload-progress"]')).toHaveCount(3);

      // Wait for all uploads to complete
      await expect(page.locator('[data-testid="upload-complete"]')).toHaveCount(3, { timeout: 15000 });

      // Verify markdown is inserted for all files
      const markdownTextarea = page.locator('[data-testid="markdown-textarea"]');
      await expect(markdownTextarea).toContainText('![image1.png]');
      await expect(markdownTextarea).toContainText('[document1.pdf]');
      await expect(markdownTextarea).toContainText('[document2.pdf]');
    });

    test('should provide file management interface', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'File Management Test');
      await page.fill('#description', 'Test file management');
      await page.click('button[type="submit"]');

      await page.click('text=File Management Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Upload a test file first
      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles({
        name: 'test-file.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('%PDF-1.4\nTest content')
      });

      await expect(page.locator('[data-testid="upload-complete"]')).toBeVisible({ timeout: 10000 });

      // Open file management panel
      await page.click('[data-testid="manage-files-btn"]');
      await expect(page.locator('[data-testid="file-management-panel"]')).toBeVisible();

      // Verify file appears in management panel
      await expect(page.locator('[data-testid="file-item-test-file.pdf"]')).toBeVisible();
      await expect(page.locator('[data-testid="file-size-test-file.pdf"]')).toBeVisible();
      await expect(page.locator('[data-testid="file-type-test-file.pdf"]')).toContainText('PDF');

      // Test file actions
      await expect(page.locator('[data-testid="rename-file-test-file.pdf"]')).toBeVisible();
      await expect(page.locator('[data-testid="delete-file-test-file.pdf"]')).toBeVisible();
      await expect(page.locator('[data-testid="insert-file-test-file.pdf"]')).toBeVisible();

      // Test rename functionality
      await page.click('[data-testid="rename-file-test-file.pdf"]');
      await page.fill('[data-testid="new-filename"]', 'renamed-file.pdf');
      await page.click('[data-testid="confirm-rename"]');
      await expect(page.locator('[data-testid="file-item-renamed-file.pdf"]')).toBeVisible();
    });
  });

  test.describe('Phase 6: Security Features & File Management', () => {
    test('should enforce file type restrictions', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Security Test');
      await page.fill('#description', 'Test security features');
      await page.click('button[type="submit"]');

      await page.click('text=Security Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Try to upload disallowed file types
      const maliciousFiles = [
        {
          name: 'malware.exe',
          mimeType: 'application/x-executable',
          buffer: Buffer.from('fake executable')
        },
        {
          name: 'script.js',
          mimeType: 'application/javascript',
          buffer: Buffer.from('console.log("malicious");')
        },
        {
          name: 'batch.bat',
          mimeType: 'application/x-bat',
          buffer: Buffer.from('@echo off')
        }
      ];

      for (const file of maliciousFiles) {
        const fileInput = page.locator('[data-testid="file-input-fallback"]');
        await fileInput.setInputFiles([file]);

        // Should show security error
        await expect(page.locator('[data-testid="security-error"]')).toBeVisible();
        await expect(page.locator('text=File type not allowed for security reasons')).toBeVisible();

        // Dismiss error
        await page.click('[data-testid="dismiss-error"]');
      }
    });

    test('should scan for malicious content in uploads', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Content Scan Test');
      await page.fill('#description', 'Test content scanning');
      await page.click('button[type="submit"]');

      await page.click('text=Content Scan Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Upload file with suspicious content
      const suspiciousFile = {
        name: 'suspicious.txt',
        mimeType: 'text/plain',
        buffer: Buffer.from('<script>alert("xss")</script>')
      };

      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles([suspiciousFile]);

      // Should trigger content scanning
      await expect(page.locator('[data-testid="content-scanning"]')).toBeVisible();
      await expect(page.locator('[data-testid="security-warning"]')).toBeVisible();
      await expect(page.locator('text=Potentially unsafe content detected')).toBeVisible();
    });

    test('should enforce file size limits', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Size Limit Test');
      await page.fill('#description', 'Test file size limits');
      await page.click('button[type="submit"]');

      await page.click('text=Size Limit Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Try to upload oversized file
      const oversizedFile = {
        name: 'large-file.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.alloc(50 * 1024 * 1024) // 50MB - should exceed limit
      };

      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles([oversizedFile]);

      // Should show size error
      await expect(page.locator('[data-testid="size-error"]')).toBeVisible();
      await expect(page.locator('text=File size exceeds maximum allowed')).toBeVisible();
    });

    test('should provide secure file access controls', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Access Control Test');
      await page.fill('#description', 'Test access controls');
      await page.click('button[type="submit"]');

      await page.click('text=Access Control Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Upload a file
      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles({
        name: 'private-file.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('%PDF-1.4\nPrivate content')
      });

      await expect(page.locator('[data-testid="upload-complete"]')).toBeVisible({ timeout: 10000 });

      // Verify file URLs are secured
      const markdownTextarea = page.locator('[data-testid="markdown-textarea"]');
      const content = await markdownTextarea.inputValue();

      // File URLs should include security tokens or use secure paths
      expect(content).toMatch(/\[private-file\.pdf\]\([^)]*(?:token|secure|auth)[^)]*\)/);
    });
  });

  test.describe('Phase 7: Kids View Rendering', () => {
    test.beforeEach(async ({ page }) => {
      // Add a child for testing kids view
      await page.goto('/profile');
      await page.click('[data-testid="add-child"]');
      await page.fill('[data-testid="child-name"]', 'Test Child');
      await page.fill('[data-testid="child-age"]', '10');
      await page.selectOption('[data-testid="child-grade"]', '5th');
      await page.selectOption('[data-testid="independence-level"]', '2');
      await page.click('[data-testid="save-child"]');
    });

    test('should render content with kids-friendly enhancements', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Kids Content Test');
      await page.fill('#description', 'Test kids rendering');
      await page.click('button[type="submit"]');

      await page.click('text=Kids Content Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Add content with various elements
      const kidsContent = `# Fun Science Adventure! 🌟

Welcome to our exciting science journey!

## What You'll Learn Today

- Forces that move things around us
- How gravity works
- Fun experiments to try

## Watch This Cool Video

[Amazing Forces Video](https://www.youtube.com/watch?v=dQw4w9WgXcQ)

## Try This Activity

> **Fun Fact!** 💡
>
> Did you know that when you walk, you're using forces?

Let's explore together!`;

      await page.fill('[data-testid="markdown-textarea"]', kidsContent);
      await page.click('[data-testid="save-unified-content"]');

      // Switch to kids view
      await page.click('[data-testid="switch-to-kids-view"]');
      await page.selectOption('[data-testid="select-child"]', 'Test Child');
      await page.click('[data-testid="view-as-child"]');

      // Verify kids-specific enhancements
      await expect(page.locator('.kids-content-container')).toBeVisible();
      await expect(page.locator('[data-age-group="elementary"]')).toBeVisible();
      await expect(page.locator('[data-independence="2"]')).toBeVisible();

      // Check for age-appropriate styling
      await expect(page.locator('.kids-heading')).toBeVisible();
      await expect(page.locator('.kids-text')).toBeVisible();
      await expect(page.locator('.kids-list')).toBeVisible();

      // Check for interactive elements based on independence level
      await expect(page.locator('.kids-checkbox')).toBeVisible();
      await expect(page.locator('[data-testid="kids-progress-tracking"]')).toBeVisible();
    });

    test('should provide age-appropriate interactions', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Interactive Content Test');
      await page.fill('#description', 'Test interactive features');
      await page.click('button[type="submit"]');

      await page.click('text=Interactive Content Test');
      await page.click('[data-testid="edit-unified-content"]');

      const interactiveContent = `# Interactive Learning

Click on the items as you learn:

- [ ] First step
- [ ] Second step
- [ ] Final step

Watch this video:

[Learning Video](https://www.youtube.com/watch?v=test)

Download this worksheet:

[Fun Worksheet](./worksheet.pdf)`;

      await page.fill('[data-testid="markdown-textarea"]', interactiveContent);
      await page.click('[data-testid="save-unified-content"]');

      // Switch to kids view
      await page.click('[data-testid="switch-to-kids-view"]');
      await page.selectOption('[data-testid="select-child"]', 'Test Child');
      await page.click('[data-testid="view-as-child"]');

      // Test interactive checkboxes
      const checkbox = page.locator('.kids-checkbox').first();
      await checkbox.check();
      await expect(checkbox).toBeChecked();

      // Test read-aloud feature
      await page.click('[data-testid="kids-read-aloud-btn"]');
      await expect(page.locator('[data-testid="reading-indicator"]')).toBeVisible();

      // Test progress tracking
      await expect(page.locator('.kids-progress-bar')).toBeVisible();
      await expect(page.locator('.kids-progress-percentage')).toContainText('0%');

      // Simulate progress
      await page.click('.kids-checkbox');
      await page.waitForTimeout(1000);
      await expect(page.locator('.kids-progress-percentage')).toContainText(/[1-9]/); // Some progress
    });

    test('should provide safe video and file interactions', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Safe Media Test');
      await page.fill('#description', 'Test safe media interactions');
      await page.click('button[type="submit"]');

      await page.click('text=Safe Media Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Upload file and add content
      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles({
        name: 'safe-worksheet.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('%PDF-1.4\nSafe content')
      });

      await expect(page.locator('[data-testid="upload-complete"]')).toBeVisible({ timeout: 10000 });

      const safeContent = `# Safe Learning Materials

Watch this educational video:

[Safe Video](https://www.youtube.com/watch?v=educational)

Download your worksheet:

[Safe Worksheet](./safe-worksheet.pdf)`;

      await page.fill('[data-testid="markdown-textarea"]', safeContent);
      await page.click('[data-testid="save-unified-content"]');

      // Switch to kids view
      await page.click('[data-testid="switch-to-kids-view"]');
      await page.selectOption('[data-testid="select-child"]', 'Test Child');
      await page.click('[data-testid="view-as-child"]');

      // Verify safe video controls
      const videoEmbed = page.locator('.kids-video-enhancements');
      await expect(videoEmbed).toBeVisible();
      await expect(page.locator('.kids-video-safety-notice')).toBeVisible();
      await expect(page.locator('text=Ask a grown-up to watch with you!')).toBeVisible();

      // Test safe file download
      const fileEmbed = page.locator('.kids-file-enhancements');
      await expect(fileEmbed).toBeVisible();
      await expect(page.locator('.kids-file-safety-reminder')).toBeVisible();
      await expect(page.locator('text=Downloaded files are safe for you to use!')).toBeVisible();
    });

    test('should adapt to different age groups and independence levels', async ({ page }) => {
      // Test with different child configurations
      const childConfigs = [
        { name: 'Preschool Child', grade: 'PreK', independence: '1', ageGroup: 'preschool' },
        { name: 'Elementary Child', grade: '3rd', independence: '2', ageGroup: 'elementary' },
        { name: 'Middle School Child', grade: '7th', independence: '3', ageGroup: 'middle' },
        { name: 'High School Child', grade: '10th', independence: '4', ageGroup: 'high' }
      ];

      for (const config of childConfigs) {
        // Add child
        await page.goto('/profile');
        await page.click('[data-testid="add-child"]');
        await page.fill('[data-testid="child-name"]', config.name);
        await page.selectOption('[data-testid="child-grade"]', config.grade);
        await page.selectOption('[data-testid="independence-level"]', config.independence);
        await page.click('[data-testid="save-child"]');

        // Create test content
        await page.goto('/dashboard');
        await page.click('text=Add Topic');
        await page.fill('#title', `${config.ageGroup} Content Test`);
        await page.fill('#description', 'Age-appropriate test content');
        await page.click('button[type="submit"]');

        await page.click(`text=${config.ageGroup} Content Test`);

        // Switch to kids view for this child
        await page.click('[data-testid="switch-to-kids-view"]');
        await page.selectOption('[data-testid="select-child"]', config.name);
        await page.click('[data-testid="view-as-child"]');

        // Verify age-appropriate styling and features
        await expect(page.locator(`[data-age-group="${config.ageGroup}"]`)).toBeVisible();
        await expect(page.locator(`[data-independence="${config.independence}"]`)).toBeVisible();

        // Check for age-appropriate features
        if (config.independence >= 3) {
          await expect(page.locator('.kids-add-note-btn')).toBeVisible();
          await expect(page.locator('.kids-bookmark-btn')).toBeVisible();
        }

        if (config.independence >= 4) {
          await expect(page.locator('[data-feature="share"]')).toBeVisible();
        }
      }
    });
  });

  test.describe('Phase 8: Performance & Optimization', () => {
    test('should load large content efficiently', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Performance Test');
      await page.fill('#description', 'Test performance with large content');
      await page.click('button[type="submit"]');

      await page.click('text=Performance Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Create large content
      const largeContent = `# Large Content Performance Test

${Array(100).fill(0).map((_, i) => `
## Section ${i + 1}

This is section ${i + 1} with substantial content to test performance.

### Subsection A

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.

### Subsection B

Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

- Item 1
- Item 2
- Item 3

1. First point
2. Second point
3. Third point

> **Note**: This is a callout box for section ${i + 1}

`).join('')}

## Performance Summary

This content contains 100 sections to test rendering performance.`;

      const startTime = Date.now();
      await page.fill('[data-testid="markdown-textarea"]', largeContent);

      // Wait for preview to update
      await expect(page.locator('[data-testid="markdown-preview"] h2')).toHaveCount(100, { timeout: 10000 });

      const renderTime = Date.now() - startTime;
      expect(renderTime).toBeLessThan(5000); // Should render within 5 seconds

      // Test saving performance
      const saveStartTime = Date.now();
      await page.click('[data-testid="save-unified-content"]');
      await expect(page.locator('[data-testid="save-success"]')).toBeVisible();

      const saveTime = Date.now() - saveStartTime;
      expect(saveTime).toBeLessThan(3000); // Should save within 3 seconds
    });

    test('should handle concurrent file uploads efficiently', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Concurrent Upload Test');
      await page.fill('#description', 'Test concurrent uploads');
      await page.click('button[type="submit"]');

      await page.click('text=Concurrent Upload Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Prepare multiple files for concurrent upload
      const concurrentFiles = Array(5).fill(0).map((_, i) => ({
        name: `file-${i + 1}.pdf`,
        mimeType: 'application/pdf',
        buffer: Buffer.from(`%PDF-1.4\nConcurrent file ${i + 1}`)
      }));

      const startTime = Date.now();

      // Upload all files simultaneously
      const fileInput = page.locator('[data-testid="file-input-fallback"]');
      await fileInput.setInputFiles(concurrentFiles);

      // Wait for all uploads to complete
      await expect(page.locator('[data-testid="upload-complete"]')).toHaveCount(5, { timeout: 15000 });

      const uploadTime = Date.now() - startTime;
      expect(uploadTime).toBeLessThan(10000); // Should complete within 10 seconds

      // Verify all files are properly referenced
      const markdownTextarea = page.locator('[data-testid="markdown-textarea"]');
      for (let i = 1; i <= 5; i++) {
        await expect(markdownTextarea).toContainText(`file-${i}.pdf`);
      }
    });

    test('should provide optimal caching and loading', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Caching Test');
      await page.fill('#description', 'Test caching optimization');
      await page.click('button[type="submit"]');

      await page.click('text=Caching Test');

      // Measure initial load time
      const firstLoadStart = Date.now();
      await expect(page.locator('[data-testid="topic-content-container"]')).toBeVisible();
      const firstLoadTime = Date.now() - firstLoadStart;

      // Navigate away and back
      await page.goto('/dashboard');
      await page.click('text=Caching Test');

      // Measure cached load time
      const cachedLoadStart = Date.now();
      await expect(page.locator('[data-testid="topic-content-container"]')).toBeVisible();
      const cachedLoadTime = Date.now() - cachedLoadStart;

      // Cached load should be significantly faster
      expect(cachedLoadTime).toBeLessThan(firstLoadTime * 0.8);
    });

    test('should optimize memory usage with large datasets', async ({ page }) => {
      // Create multiple topics with content to test memory usage
      const topicCount = 10;

      for (let i = 1; i <= topicCount; i++) {
        await page.click('text=Add Topic');
        await page.fill('#title', `Memory Test Topic ${i}`);
        await page.fill('#description', `Memory test content ${i}`);
        await page.click('button[type="submit"]');
      }

      // Navigate between topics rapidly to test memory management
      for (let i = 1; i <= topicCount; i++) {
        await page.click(`text=Memory Test Topic ${i}`);
        await expect(page.locator('[data-testid="topic-content-container"]')).toBeVisible();

        // Check that page is still responsive
        const responseStart = Date.now();
        await page.click('[data-testid="edit-unified-content"]');
        await page.click('[data-testid="cancel-edit"]');
        const responseTime = Date.now() - responseStart;

        expect(responseTime).toBeLessThan(2000); // Should remain responsive
      }
    });
  });

  test.describe('Security & Error Handling', () => {
    test('should handle network failures gracefully', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Network Failure Test');
      await page.fill('#description', 'Test network failure handling');
      await page.click('button[type="submit"]');

      await page.click('text=Network Failure Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Simulate network failure during save
      await page.route('**/api/topics/**', route => route.abort());

      await page.fill('[data-testid="markdown-textarea"]', '# Test content');
      await page.click('[data-testid="save-unified-content"]');

      // Should show error message and offer retry
      await expect(page.locator('[data-testid="network-error"]')).toBeVisible();
      await expect(page.locator('text=Network error occurred')).toBeVisible();
      await expect(page.locator('[data-testid="retry-save"]')).toBeVisible();

      // Restore network and retry
      await page.unroute('**/api/topics/**');
      await page.click('[data-testid="retry-save"]');
      await expect(page.locator('[data-testid="save-success"]')).toBeVisible();
    });

    test('should validate input sanitization', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'XSS Test');
      await page.fill('#description', 'Test XSS prevention');
      await page.click('button[type="submit"]');

      await page.click('text=XSS Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Try to inject malicious content
      const maliciousContent = `# XSS Test

<script>alert('xss')</script>

<img src="x" onerror="alert('xss')">

<iframe src="javascript:alert('xss')"></iframe>

[Click me](javascript:alert('xss'))`;

      await page.fill('[data-testid="markdown-textarea"]', maliciousContent);

      // Preview should sanitize content
      const preview = page.locator('[data-testid="markdown-preview"]');
      await expect(preview.locator('script')).toHaveCount(0);
      await expect(preview.locator('iframe')).toHaveCount(0);
      await expect(preview.locator('a[href^="javascript:"]')).toHaveCount(0);

      // Save and verify sanitization persists
      await page.click('[data-testid="save-unified-content"]');
      await expect(page.locator('script')).toHaveCount(0);
    });

    test('should enforce user authorization', async ({ page }) => {
      // Create topic as first user
      await page.click('text=Add Topic');
      await page.fill('#title', 'Authorization Test');
      await page.fill('#description', 'Test authorization');
      await page.click('button[type="submit"]');

      const topicUrl = page.url();

      // Logout and create second user
      await page.click('[data-testid="logout"]');
      await page.goto('/register');
      await page.fill('#name', 'Unauthorized User');
      await page.fill('#email', `unauthorized${Date.now()}@example.com`);
      await page.fill('#password', 'password123');
      await page.fill('#password_confirmation', 'password123');
      await page.click('button[type="submit"]');

      // Try to access first user's topic
      await page.goto(topicUrl);

      // Should be denied access
      await expect(page.locator('text=Access denied')).toBeVisible();
      await expect(page.locator('[data-testid="unauthorized-error"]')).toBeVisible();
    });
  });

  test.describe('Accessibility & Usability', () => {
    test('should be fully keyboard navigable', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Keyboard Test');
      await page.fill('#description', 'Test keyboard navigation');
      await page.click('button[type="submit"]');

      await page.click('text=Keyboard Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Test keyboard navigation through editor
      await page.keyboard.press('Tab'); // Focus markdown textarea
      await expect(page.locator('[data-testid="markdown-textarea"]:focus')).toBeVisible();

      await page.keyboard.press('Tab'); // Move to toolbar
      await expect(page.locator('[data-testid="bold-button"]:focus')).toBeVisible();

      // Test keyboard shortcuts
      await page.keyboard.press('Control+b'); // Bold
      await expect(page.locator('[data-testid="markdown-textarea"]')).toContainText('****');

      await page.keyboard.press('Control+i'); // Italic
      await expect(page.locator('[data-testid="markdown-textarea"]')).toContainText('**');

      await page.keyboard.press('Control+s'); // Save
      await expect(page.locator('[data-testid="save-success"]')).toBeVisible();
    });

    test('should support screen readers', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Screen Reader Test');
      await page.fill('#description', 'Test screen reader support');
      await page.click('button[type="submit"]');

      await page.click('text=Screen Reader Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Check for proper ARIA labels and roles
      await expect(page.locator('[data-testid="markdown-textarea"]')).toHaveAttribute('aria-label');
      await expect(page.locator('[data-testid="markdown-preview"]')).toHaveAttribute('role', 'region');
      await expect(page.locator('[data-testid="markdown-toolbar"]')).toHaveAttribute('role', 'toolbar');

      // Check for semantic structure
      await expect(page.locator('main')).toBeVisible();
      await expect(page.locator('[role="main"]')).toBeVisible();
      await expect(page.locator('h1, h2, h3')).toHaveCount.greaterThan(0);
    });

    test('should work well on mobile devices', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });

      await page.click('text=Add Topic');
      await page.fill('#title', 'Mobile Test');
      await page.fill('#description', 'Test mobile experience');
      await page.click('button[type="submit"]');

      await page.click('text=Mobile Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Verify mobile-responsive editor
      await expect(page.locator('[data-testid="markdown-editor-mobile"]')).toBeVisible();
      await expect(page.locator('[data-testid="mobile-toolbar"]')).toBeVisible();

      // Test touch interactions
      const markdownTextarea = page.locator('[data-testid="markdown-textarea"]');
      await markdownTextarea.tap();
      await expect(markdownTextarea).toBeFocused();

      // Test swipe between edit and preview
      await page.touchscreen.tap(200, 100);
      await page.touchscreen.tap(300, 100);
      await expect(page.locator('[data-testid="mobile-preview-mode"]')).toBeVisible();
    });
  });

  test.describe('Integration & Cross-Feature Testing', () => {
    test('should work seamlessly with existing planning system', async ({ page }) => {
      // This test verifies the unified content system integrates with the existing homeschool planning

      // Navigate to planning board
      await page.goto('/planning-board');
      await expect(page.locator('[data-testid="planning-board"]')).toBeVisible();

      // Find our test topic in the planning system
      await expect(page.locator('text=Unified Content Topic')).toBeVisible();

      // Verify unified content is displayed in planning cards
      const topicCard = page.locator('[data-testid="topic-card"]').filter({ hasText: 'Unified Content Topic' });
      await expect(topicCard).toBeVisible();
      await expect(topicCard.locator('[data-testid="unified-content-indicator"]')).toBeVisible();

      // Open topic from planning board
      await topicCard.click();
      await expect(page.locator('[data-testid="unified-content-display"]')).toBeVisible();
    });

    test('should maintain data consistency across migrations', async ({ page }) => {
      // Create topic with legacy format
      await page.click('text=Add Topic');
      await page.fill('#title', 'Migration Consistency Test');
      await page.fill('#description', 'Original legacy description');
      await page.click('button[type="submit"]');

      // Add legacy materials
      await page.click('text=Migration Consistency Test');
      await page.click('text=Edit Topic');

      await page.click('text=Add Video');
      await page.fill('[data-testid="video-url"]', 'https://www.youtube.com/watch?v=original');
      await page.fill('[data-testid="video-title"]', 'Original Video');
      await page.click('[data-testid="add-video-submit"]');

      await page.click('text=Add Link');
      await page.fill('[data-testid="link-url"]', 'https://example.com/original');
      await page.fill('[data-testid="link-title"]', 'Original Link');
      await page.click('[data-testid="add-link-submit"]');

      await page.click('[data-testid="close-modal"]');

      // Migrate to unified format
      await page.click('[data-testid="migrate-to-unified"]');
      await page.click('text=Confirm Migration');

      // Verify all original content is preserved
      await expect(page.locator('text=Original legacy description')).toBeVisible();
      await expect(page.locator('text=## Video Resources')).toBeVisible();
      await expect(page.locator('text=Original Video')).toBeVisible();
      await expect(page.locator('text=## Additional Resources')).toBeVisible();
      await expect(page.locator('text=Original Link')).toBeVisible();

      // Verify can still edit and add new content
      await page.click('[data-testid="edit-unified-content"]');
      const existing = await page.locator('[data-testid="markdown-textarea"]').inputValue();
      const updated = existing + '\n\n## New Unified Content\n\nThis is new content added after migration.';
      await page.fill('[data-testid="markdown-textarea"]', updated);
      await page.click('[data-testid="save-unified-content"]');

      await expect(page.locator('text=New Unified Content')).toBeVisible();
      await expect(page.locator('text=This is new content added after migration')).toBeVisible();
    });

    test('should work with all supported file types and platforms', async ({ page }) => {
      await page.click('text=Add Topic');
      await page.fill('#title', 'Comprehensive Media Test');
      await page.fill('#description', 'Test all supported media types');
      await page.click('button[type="submit"]');

      await page.click('text=Comprehensive Media Test');
      await page.click('[data-testid="edit-unified-content"]');

      // Test all supported video platforms
      const comprehensiveContent = `# Comprehensive Media Testing

## Video Platforms

### YouTube
[YouTube Video](https://www.youtube.com/watch?v=dQw4w9WgXcQ)

### Vimeo
[Vimeo Video](https://vimeo.com/123456789)

### Khan Academy
[Khan Academy Lesson](https://www.khanacademy.org/science/physics/forces-newtons-laws)

### Coursera
[Coursera Course](https://www.coursera.org/learn/physics-introduction)

### edX
[edX Course](https://www.edx.org/course/introduction-to-physics)

## File Types

We'll upload various file types to test support.`;

      await page.fill('[data-testid="markdown-textarea"]', comprehensiveContent);

      // Upload different file types
      const supportedFiles = [
        { name: 'document.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4\nPDF content') },
        { name: 'presentation.pptx', mimeType: 'application/vnd.openxmlformats-officedocument.presentationml.presentation', buffer: Buffer.from('PPT content') },
        { name: 'spreadsheet.xlsx', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', buffer: Buffer.from('Excel content') },
        { name: 'document.docx', mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', buffer: Buffer.from('Word content') },
        { name: 'image.png', mimeType: 'image/png', buffer: Buffer.from([0x89, 0x50, 0x4E, 0x47]) },
        { name: 'image.jpg', mimeType: 'image/jpeg', buffer: Buffer.from([0xFF, 0xD8, 0xFF, 0xE0]) },
        { name: 'audio.mp3', mimeType: 'audio/mpeg', buffer: Buffer.from('MP3 content') },
        { name: 'video.mp4', mimeType: 'video/mp4', buffer: Buffer.from('MP4 content') }
      ];

      for (const file of supportedFiles) {
        const fileInput = page.locator('[data-testid="file-input-fallback"]');
        await fileInput.setInputFiles([file]);
        await expect(page.locator('[data-testid="upload-complete"]')).toBeVisible({ timeout: 10000 });
      }

      // Save and verify all content renders correctly
      await page.click('[data-testid="save-unified-content"]');

      // Verify video embeds
      await expect(page.locator('.video-embed-container')).toHaveCount(5);
      await expect(page.locator('[data-video-type="youtube"]')).toBeVisible();
      await expect(page.locator('[data-video-type="vimeo"]')).toBeVisible();
      await expect(page.locator('[data-video-type="khan_academy"]')).toBeVisible();
      await expect(page.locator('[data-video-type="coursera"]')).toBeVisible();
      await expect(page.locator('[data-video-type="edx"]')).toBeVisible();

      // Verify file embeds
      await expect(page.locator('.file-embed')).toHaveCount(supportedFiles.length);
      for (const file of supportedFiles) {
        await expect(page.locator(`[data-file-name="${file.name}"]`)).toBeVisible();
      }
    });
  });
});
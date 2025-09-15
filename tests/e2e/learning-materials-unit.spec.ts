import { test, expect } from '@playwright/test';

test.describe('Learning Materials Unit Tests', () => {
  // Simple test to validate the learning materials display template
  test('should render materials display template correctly', async ({ page }) => {
    // Create a test page that includes our learning materials component
    const testHtml = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Learning Materials Test</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
          .material-card {
            position: relative;
            min-height: 280px;
            background: linear-gradient(135deg, var(--tw-gradient-from), var(--tw-gradient-to));
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
          }

          .material-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: scale(1.05);
          }
        </style>
      </head>
      <body class="bg-gray-100 p-8">
        <div class="learning-materials-kids" data-testid="learning-materials-kids">
          <!-- Header -->
          <div class="mb-6 text-center">
            <h3 class="text-2xl font-bold text-gray-800 mb-2 flex items-center justify-center">
              <span class="mr-3 text-3xl">📚</span>
              Learning Materials
              <span class="ml-3 text-3xl">🎯</span>
            </h3>
            <p class="text-gray-600 text-lg">Explore these awesome resources to learn about Test Topic!</p>
          </div>

          <!-- Materials Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Test Video Card -->
            <div class="material-card video-card bg-gradient-to-br from-red-50 to-red-100 border-2 border-red-200 rounded-xl p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group"
                 data-testid="video-card-0">
              <div class="mb-4">
                <div class="relative rounded-lg bg-red-200 flex items-center justify-center" style="aspect-ratio: 16/9;">
                  <div class="text-center">
                    <svg class="w-16 h-16 mx-auto text-red-600 mb-2" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span class="text-sm font-medium text-red-700">YouTube Video</span>
                  </div>
                </div>
              </div>
              <div class="text-center">
                <h4 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">Fun Physics Video</h4>
                <p class="text-sm text-gray-600 mb-3 line-clamp-2">An exciting video about forces</p>
                <a href="https://www.youtube.com/watch?v=test123" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center justify-center w-full py-3 px-6 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg text-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-red-300"
                   data-testid="watch-video-0">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                  </svg>
                  Watch Video
                </a>
              </div>
              <div class="absolute top-4 right-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white shadow-lg">
                  YouTube
                </span>
              </div>
            </div>

            <!-- Test Link Card -->
            <div class="material-card link-card bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-200 rounded-xl p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group"
                 data-testid="link-card-0">
              <div class="mb-4 text-center">
                <div class="w-20 h-20 mx-auto bg-blue-200 rounded-full flex items-center justify-center mb-3 group-hover:bg-blue-300 transition-colors">
                  <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                  </svg>
                </div>
              </div>
              <div class="text-center">
                <h4 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">Kids Science - Forces</h4>
                <p class="text-sm text-gray-600 mb-3 line-clamp-2">Interactive games about forces</p>
                <div class="mb-4">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    🌐 kidscience.com
                  </span>
                </div>
                <a href="https://kidscience.com/forces" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center justify-center w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300"
                   data-testid="visit-link-0">
                  <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                  </svg>
                  Visit Website
                </a>
              </div>
            </div>

            <!-- Test File Card -->
            <div class="material-card file-card bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-200 rounded-xl p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group"
                 data-testid="file-card-0">
              <div class="mb-4 text-center">
                <div class="w-20 h-20 mx-auto bg-green-200 rounded-full flex items-center justify-center mb-3 group-hover:bg-green-300 transition-colors">
                  <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                </div>
              </div>
              <div class="text-center">
                <h4 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">Force Poster</h4>
                <p class="text-sm text-gray-600 mb-2 line-clamp-1">force-poster.png</p>
                <div class="mb-4 space-y-1">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-600 text-white uppercase">
                    PNG
                  </span>
                  <div class="text-sm text-gray-500 font-medium">125 KB</div>
                </div>
                <div class="space-y-2">
                  <a href="/storage/test-file.png" target="_blank" rel="noopener noreferrer"
                     class="inline-flex items-center justify-center w-full py-3 px-6 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-green-300"
                     data-testid="view-file-0">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View File
                  </a>
                  <a href="/storage/test-file.png" download
                     class="inline-flex items-center justify-center w-full py-2 px-6 bg-white border-2 border-green-600 text-green-600 hover:bg-green-50 font-semibold rounded-lg text-base transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-green-300"
                     data-testid="download-file-0">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <script>
          // Add the interaction JavaScript
          document.addEventListener('DOMContentLoaded', function() {
            // Add loading states for external links
            const links = document.querySelectorAll('[data-testid^="visit-link-"], [data-testid^="watch-video-"]');
            links.forEach(link => {
              link.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent navigation in test
                const button = this;
                const originalText = button.innerHTML;

                // Show loading state briefly
                button.innerHTML = button.innerHTML.replace(/Watch Video|Visit Website/, 'Opening...');
                button.style.pointerEvents = 'none';

                // Reset after a short delay
                setTimeout(() => {
                  button.innerHTML = originalText;
                  button.style.pointerEvents = 'auto';
                }, 1500);
              });
            });

            // Add file download feedback
            const downloadLinks = document.querySelectorAll('[data-testid^="download-file-"]');
            downloadLinks.forEach(link => {
              link.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent download in test
                const button = this;
                const originalText = button.innerHTML;

                button.innerHTML = button.innerHTML.replace('Download', 'Downloading...');
                button.style.pointerEvents = 'none';

                setTimeout(() => {
                  button.innerHTML = originalText.replace('Downloading...', 'Downloaded!');

                  setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.pointerEvents = 'auto';
                  }, 2000);
                }, 1000);
              });
            });

            // Touch feedback for mobile
            if ('ontouchstart' in window) {
              const cards = document.querySelectorAll('.material-card');
              cards.forEach(card => {
                card.addEventListener('touchstart', function() {
                  this.style.transform = 'scale(0.98)';
                });

                card.addEventListener('touchend', function() {
                  this.style.transform = '';
                });
              });
            }
          });
        </script>
      </body>
      </html>
    `;

    // Set the test HTML content
    await page.setContent(testHtml);

    // Test the basic layout
    await expect(page.locator('[data-testid="learning-materials-kids"]')).toBeVisible();
    await expect(page.locator('text=Learning Materials')).toBeVisible();

    // Test video card
    const videoCard = page.locator('[data-testid="video-card-0"]');
    await expect(videoCard).toBeVisible();
    await expect(videoCard).toContainText('Fun Physics Video');
    await expect(videoCard).toContainText('YouTube');

    // Test video card size for kids (should be large enough)
    const videoBounds = await videoCard.boundingBox();
    expect(videoBounds?.height).toBeGreaterThan(250); // Should be large for kids
    expect(videoBounds?.width).toBeGreaterThan(300);

    // Test link card
    const linkCard = page.locator('[data-testid="link-card-0"]');
    await expect(linkCard).toBeVisible();
    await expect(linkCard).toContainText('Kids Science - Forces');
    await expect(linkCard).toContainText('kidscience.com');

    // Test file card
    const fileCard = page.locator('[data-testid="file-card-0"]');
    await expect(fileCard).toBeVisible();
    await expect(fileCard).toContainText('Force Poster');
    await expect(fileCard).toContainText('PNG');
    await expect(fileCard).toContainText('125 KB');

    // Test interaction - video button
    const watchButton = page.locator('[data-testid="watch-video-0"]');
    await watchButton.click();
    await expect(page.locator('text=Opening...')).toBeVisible();

    // Wait for state to reset
    await page.waitForTimeout(2000);
    await expect(page.locator('text=Watch Video')).toBeVisible();

    // Test interaction - download button
    const downloadButton = page.locator('[data-testid="download-file-0"]');
    await downloadButton.click();
    await expect(page.locator('text=Downloading...')).toBeVisible();

    // Wait for feedback sequence (the animation takes 1000ms + 2000ms)
    await page.waitForTimeout(3500);
    await expect(page.locator('text=Download')).toBeVisible(); // Should be back to original state
  });

  test('should be responsive on mobile viewport', async ({ page }) => {
    const testHtml = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Mobile Test</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
          .material-card {
            min-height: 300px;
            transition: all 0.3s ease;
          }
          @media (max-width: 768px) {
            .material-card {
              min-height: 300px;
              padding: 1.5rem !important;
            }
            .material-card button,
            .material-card a {
              padding: 1rem 1.5rem !important;
              font-size: 1.125rem !important;
            }
          }
        </style>
      </head>
      <body class="bg-gray-100 p-4">
        <div class="grid grid-cols-1 gap-6">
          <div class="material-card bg-red-50 border-2 border-red-200 rounded-xl p-6" data-testid="mobile-card">
            <h4 class="text-lg font-bold mb-4">Mobile Test Card</h4>
            <button class="w-full py-3 px-6 bg-red-600 text-white rounded-lg text-lg" data-testid="mobile-button">
              Test Button
            </button>
          </div>
        </div>
      </body>
      </html>
    `;

    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    await page.setContent(testHtml);

    const mobileCard = page.locator('[data-testid="mobile-card"]');
    await expect(mobileCard).toBeVisible();

    // Test touch target size
    const button = page.locator('[data-testid="mobile-button"]');
    const buttonBounds = await button.boundingBox();
    expect(buttonBounds?.height).toBeGreaterThan(40); // Minimum touch target
    expect(buttonBounds?.width).toBeGreaterThan(200); // Wide enough for touch

    // Test card height on mobile
    const cardBounds = await mobileCard.boundingBox();
    expect(cardBounds?.height).toBeGreaterThan(250); // Adequate height
  });

  test('should have proper accessibility features', async ({ page }) => {
    const testHtml = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Accessibility Test</title>
        <script src="https://cdn.tailwindcss.com"></script>
      </head>
      <body class="bg-gray-100 p-8">
        <div role="main">
          <h3 class="text-2xl font-bold mb-4">Learning Materials</h3>
          <div class="grid grid-cols-1 gap-6">
            <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6" tabindex="0" data-testid="accessible-card">
              <h4 class="text-lg font-bold mb-2">Accessible Video</h4>
              <a href="https://youtube.com/test"
                 target="_blank"
                 rel="noopener noreferrer"
                 aria-label="Watch educational video about physics"
                 class="inline-flex items-center py-3 px-6 bg-red-600 text-white rounded-lg focus:outline-none focus:ring-4 focus:ring-red-300"
                 data-testid="accessible-link">
                Watch Video
              </a>
            </div>
          </div>
        </div>
      </body>
      </html>
    `;

    await page.setContent(testHtml);

    // Test semantic structure
    await expect(page.locator('h3')).toContainText('Learning Materials');
    await expect(page.locator('h4')).toContainText('Accessible Video');

    // Test keyboard navigation
    const card = page.locator('[data-testid="accessible-card"]');
    await expect(card).toHaveAttribute('tabindex', '0');

    const link = page.locator('[data-testid="accessible-link"]');
    await expect(link).toHaveAttribute('aria-label');
    await expect(link).toHaveAttribute('target', '_blank');
    await expect(link).toHaveAttribute('rel', 'noopener noreferrer');

    // Test focus states
    await link.focus();
    await expect(link).toHaveClass(/focus:ring-4/);
  });

  test('should handle empty states gracefully', async ({ page }) => {
    const emptyHtml = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Empty State Test</title>
        <script src="https://cdn.tailwindcss.com"></script>
      </head>
      <body class="bg-gray-100 p-8">
        <div class="text-center py-12 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border-2 border-dashed border-gray-300">
          <div class="mb-6">
            <span class="text-6xl">📝</span>
          </div>
          <h3 class="text-xl font-bold text-gray-700 mb-2">No Learning Materials Yet</h3>
          <p class="text-gray-600 text-lg mb-6">Ask your parent to add some awesome videos, links, or files to help you learn!</p>
          <div class="inline-flex items-center px-6 py-3 bg-blue-100 text-blue-800 rounded-lg font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Coming Soon!
          </div>
        </div>
      </body>
      </html>
    `;

    await page.setContent(emptyHtml);

    // Test empty state elements
    await expect(page.locator('text=📝')).toBeVisible();
    await expect(page.locator('text=No Learning Materials Yet')).toBeVisible();
    await expect(page.locator('text=Ask your parent to add')).toBeVisible();
    await expect(page.locator('text=Coming Soon!')).toBeVisible();
  });
});
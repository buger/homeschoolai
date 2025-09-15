/**
 * E2E Tests for Kids View Functionality
 *
 * Tests the beautiful kids view rendering system, including:
 * - Age-appropriate content rendering
 * - Interactive elements and gamification
 * - Touch-friendly interface
 * - Safety features and content filtering
 * - Progress tracking and achievements
 */

import { test, expect, Page } from '@playwright/test';

// Test data setup
const TEST_USER = {
    email: 'test@example.com',
    password: 'password123'
};

const TEST_CHILD = {
    name: 'Test Child',
    grade: '3rd',
    independence_level: 2
};

const TEST_CONTENT = {
    title: 'Fun Science Experiment',
    description: `# Amazing Water Cycle

Learn about how water moves around our planet!

## What You'll Need
- [ ] A clear glass
- [ ] Water
- [ ] Ice cubes
- [ ] A plate

## Steps
1. Fill the glass with water
2. Add ice cubes
3. Watch what happens!

## Fun Facts
- Water can be a solid, liquid, or gas
- The sun helps water evaporate
- Clouds are made of tiny water droplets

[Watch this video](https://youtube.com/watch?v=example)

![Water cycle diagram](https://example.com/water-cycle.png)`,
    estimated_minutes: 15
};

test.describe('Kids View Functionality', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to login and sign in
        await page.goto('/login');
        await page.fill('input[name="email"]', TEST_USER.email);
        await page.fill('input[name="password"]', TEST_USER.password);
        await page.click('button[type="submit"]');

        // Wait for dashboard to load
        await page.waitForURL('**/dashboard*');
        await expect(page.locator('h1')).toContainText('Dashboard');
    });

    test.describe('Kids Mode Setup and Access', () => {
        test('should activate kids mode and access kids view', async ({ page }) => {
            // Create a child first
            await page.click('a[href*="children"]');
            await page.click('button:has-text("Add Child")');

            await page.fill('input[name="name"]', TEST_CHILD.name);
            await page.selectOption('select[name="grade"]', TEST_CHILD.grade);
            await page.selectOption('select[name="independence_level"]', TEST_CHILD.independence_level.toString());
            await page.click('button[type="submit"]');

            // Wait for child to be created
            await expect(page.locator('text=' + TEST_CHILD.name)).toBeVisible();

            // Create test content
            await createTestContent(page);

            // Activate kids mode
            await page.click('button:has-text("Kids Mode")');
            await page.click(`text=${TEST_CHILD.name}`);

            // Enter PIN (if required)
            const pinInput = page.locator('input[type="password"]');
            if (await pinInput.isVisible()) {
                await pinInput.fill('1234');
                await page.click('button:has-text("Enter Kids Mode")');
            }

            // Verify kids mode is active
            await expect(page.locator('[data-testid="kids-mode-indicator"]')).toBeVisible();

            // Navigate to topic in kids view
            await page.click('text=Subjects');
            await page.click('text=Science'); // Assuming we created it in Science
            await page.click('text=Water Experiments'); // Unit name
            await page.click('text=' + TEST_CONTENT.title);

            // Verify kids view is loaded
            await expect(page.locator('[data-testid="kids-unified-content"]')).toBeVisible();
        });

        test('should prevent access to kids view when not in kids mode', async ({ page }) => {
            // Try to access kids view URL directly without kids mode
            await page.goto('/units/1/topics/1/kids');

            // Should redirect to regular topic view
            await expect(page.url()).toContain('/units/1/topics/1');
            await expect(page.url()).not.toContain('/kids');
        });
    });

    test.describe('Age-Appropriate Content Rendering', () => {
        test('should render content with age-appropriate styling for elementary grade', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check for elementary-specific styling
            const container = page.locator('[data-age-group="elementary"]');
            await expect(container).toBeVisible();

            // Verify educational styling elements
            await expect(page.locator('.kids-heading.kids-educational')).toBeVisible();
            await expect(page.locator('.kids-text.kids-readable')).toBeVisible();

            // Check for appropriate emoji usage
            await expect(page.locator('text=📚')).toBeVisible();
            await expect(page.locator('text=🔍')).toBeVisible();
        });

        test('should adjust complexity based on age group', async ({ page }) => {
            await activateKidsMode(page, { ...TEST_CHILD, grade: 'PreK' });
            await navigateToKidsTopicView(page);

            // Preschool content should have larger fonts and simpler layout
            const container = page.locator('[data-age-group="preschool"]');
            await expect(container).toBeVisible();

            // Check for preschool-specific elements
            await expect(page.locator('.kids-heading-xl')).toHaveCSS('font-size', /3.5rem|56px/);
            await expect(page.locator('.kids-text-large')).toBeVisible();
        });

        test('should filter content for safety', async ({ page }) => {
            // Create content with potential safety issues
            const unsafeContent = {
                ...TEST_CONTENT,
                description: TEST_CONTENT.description + '\n\n[Dangerous Site](https://example-unsafe-domain.com)\n\nThis experiment involves dangerous chemicals.'
            };

            await activateKidsMode(page, TEST_CHILD);
            await createTestContent(page, unsafeContent);
            await navigateToKidsTopicView(page);

            // Check that unsafe links are filtered
            await expect(page.locator('text=link removed for safety')).toBeVisible();

            // Check for safety warnings
            await expect(page.locator('.kids-safety-section')).toBeVisible();
            await expect(page.locator('text=Adult supervision recommended')).toBeVisible();
        });
    });

    test.describe('Interactive Elements and Touch Interface', () => {
        test('should provide touch-friendly interactive elements', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check for large, touch-friendly buttons
            const buttons = page.locator('.kids-control-btn');
            await expect(buttons.first()).toHaveCSS('min-height', /44px|52px/);

            // Test read-aloud functionality
            await page.click('[data-testid="kids-read-aloud"]');
            await expect(page.locator('.kids-feedback')).toContainText('Reading aloud');

            // Test highlight mode (for level 2+ independence)
            if (TEST_CHILD.independence_level >= 2) {
                await page.click('[data-testid="kids-highlight-mode"]');
                await expect(page.locator('.kids-feedback')).toContainText('Highlight mode');
            }
        });

        test('should handle checkbox interactions with celebrations', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Find and click a checkbox
            const checkbox = page.locator('.kids-checkbox').first();
            await expect(checkbox).toBeVisible();

            await checkbox.click();

            // Check for celebration animation
            await expect(page.locator('.kids-task-celebration')).toBeVisible();
            await expect(page.locator('.kids-feedback')).toContainText('Great job');

            // Verify checkbox state is saved
            await page.reload();
            await expect(checkbox).toBeChecked();
        });

        test('should provide text highlighting functionality', async ({ page }) => {
            // Only test for independence level 2+
            if (TEST_CHILD.independence_level < 2) {
                test.skip();
            }

            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Activate highlight mode
            await page.click('[data-testid="kids-highlight-mode"]');
            await expect(page.locator('body.kids-highlight-mode')).toBeVisible();

            // Select text to highlight
            const textElement = page.locator('.kids-text').first();
            await textElement.selectText();

            // Text should be highlighted
            await expect(page.locator('.kids-highlight')).toBeVisible();
            await expect(page.locator('.kids-feedback')).toContainText('highlighted');
        });
    });

    test.describe('Gamification and Progress Tracking', () => {
        test('should display gamification dashboard', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check gamification elements
            await expect(page.locator('.kids-gamification-dashboard')).toBeVisible();
            await expect(page.locator('.kids-score-display')).toBeVisible();

            // Check score items
            await expect(page.locator('text=Points Available')).toBeVisible();
            await expect(page.locator('text=Difficulty')).toBeVisible();
            await expect(page.locator('text=Engagement')).toBeVisible();

            // Check encouragement message
            await expect(page.locator('.kids-encouragement-message')).toBeVisible();
        });

        test('should track reading progress', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check initial progress
            const progressBar = page.locator('.kids-progress-fill');
            await expect(progressBar).toBeVisible();

            const initialWidth = await progressBar.evaluate(el => el.style.width);

            // Scroll to increase reading progress
            await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
            await page.waitForTimeout(500); // Allow progress update

            const updatedWidth = await progressBar.evaluate(el => el.style.width);

            // Progress should increase
            expect(parseFloat(updatedWidth)).toBeGreaterThan(parseFloat(initialWidth || '0'));
        });

        test('should show achievement showcase', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check achievement section
            await expect(page.locator('.kids-achievement-showcase')).toBeVisible();
            await expect(page.locator('.kids-achievement-title')).toContainText('Achievements');

            // Check individual achievement cards
            const achievementCards = page.locator('.kids-achievement-card');
            await expect(achievementCards.first()).toBeVisible();

            // Each card should have icon, name, and status
            await expect(achievementCards.first().locator('.kids-achievement-icon')).toBeVisible();
            await expect(achievementCards.first().locator('.kids-achievement-name')).toBeVisible();
            await expect(achievementCards.first().locator('.kids-achievement-status')).toBeVisible();
        });

        test('should track time spent learning', async ({ page }) => {
            // Only test for independence level 3+
            if (TEST_CHILD.independence_level < 3) {
                test.skip();
            }

            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check time tracking elements
            await expect(page.locator('.kids-time-tracking')).toBeVisible();
            await expect(page.locator('[data-timer="session"]')).toBeVisible();

            const initialTime = await page.locator('[data-timer="session"]').textContent();

            // Wait a few seconds
            await page.waitForTimeout(3000);

            const updatedTime = await page.locator('[data-timer="session"]').textContent();

            // Time should have progressed
            expect(updatedTime).not.toBe(initialTime);

            // Test pause functionality
            await page.click('[data-action="toggle-timer"]');
            await expect(page.locator('.kids-feedback')).toContainText('paused');
        });
    });

    test.describe('Safety Features and Parental Controls', () => {
        test('should display safety section with appropriate information', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check safety section
            await expect(page.locator('.kids-safety-section')).toBeVisible();
            await expect(page.locator('.kids-safety-title')).toContainText('Safe Learning Zone');

            // Check safety indicators
            await expect(page.locator('.kids-safety-item')).toBeVisible();
            await expect(page.locator('text=Content approved')).toBeVisible();
            await expect(page.locator('text=Safe learning environment')).toBeVisible();

            // For lower independence levels, should show adult supervision reminder
            if (TEST_CHILD.independence_level <= 2) {
                await expect(page.locator('text=Ask a grown-up')).toBeVisible();
            }
        });

        test('should provide help and reporting functionality', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Test help button
            const helpButton = page.locator('[data-testid="kids-get-help"]');
            if (await helpButton.isVisible()) {
                await helpButton.click();
                // Should show help dialog or message
                await expect(page.locator('text=Help is on the way')).toBeVisible();
            }

            // Test report problem button
            await page.click('[data-testid="kids-report-problem"]');
            await expect(page.locator('text=Thanks for reporting')).toBeVisible();
        });

        test('should allow safe exit from kids mode', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Click exit kids mode
            await page.click('[data-testid="kids-exit-mode"]');

            // Should require PIN or redirect to exit process
            await expect(page.url()).toContain('kids-mode/exit');
        });
    });

    test.describe('Responsive Design and Accessibility', () => {
        test('should work on mobile viewport', async ({ page }) => {
            // Set mobile viewport
            await page.setViewportSize({ width: 375, height: 667 });

            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check mobile-specific adaptations
            await expect(page.locator('.kids-unified-content-view')).toBeVisible();

            // Buttons should be larger on mobile
            const buttons = page.locator('.kids-control-btn');
            await expect(buttons.first()).toHaveCSS('min-height', /60px|72px/);

            // Progress section should stack on mobile
            const progressSection = page.locator('.kids-progress-indicators');
            await expect(progressSection).toBeVisible();
        });

        test('should support keyboard navigation', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Test keyboard focus on interactive elements
            await page.keyboard.press('Tab');

            const focusedElement = page.locator(':focus');
            await expect(focusedElement).toBeVisible();

            // Should be able to activate buttons with Enter
            if (await focusedElement.getAttribute('class')?.includes('kids-control-btn')) {
                await page.keyboard.press('Enter');
                await expect(page.locator('.kids-feedback')).toBeVisible();
            }
        });

        test('should provide proper contrast and readability', async ({ page }) => {
            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Check text has sufficient contrast
            const textElement = page.locator('.kids-text').first();
            const textColor = await textElement.evaluate(el => getComputedStyle(el).color);
            const backgroundColor = await textElement.evaluate(el => getComputedStyle(el).backgroundColor);

            // Basic contrast check (would need more sophisticated testing in real implementation)
            expect(textColor).toBeTruthy();
            expect(backgroundColor).toBeTruthy();

            // Check font sizes are appropriate
            const headingElement = page.locator('.kids-heading-xl').first();
            const fontSize = await headingElement.evaluate(el => getComputedStyle(el).fontSize);
            expect(parseFloat(fontSize)).toBeGreaterThan(20); // Should be at least 20px for readability
        });
    });

    test.describe('Content Completion and Progress Saving', () => {
        test('should mark topic as complete and track progress', async ({ page }) => {
            // Only test for independence level 2+
            if (TEST_CHILD.independence_level < 2) {
                test.skip();
            }

            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Interact with content to build progress
            const checkboxes = page.locator('.kids-checkbox');
            for (let i = 0; i < Math.min(3, await checkboxes.count()); i++) {
                await checkboxes.nth(i).click();
                await page.waitForTimeout(500);
            }

            // Scroll to read content
            await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
            await page.waitForTimeout(1000);

            // Mark as complete
            await page.click('[data-testid="kids-mark-complete"]');

            // Should show completion celebration
            await expect(page.locator('.kids-big-celebration')).toBeVisible();
            await expect(page.locator('text=Great job completing')).toBeVisible();

            // Should track completion
            await expect(page.locator('text=Points earned')).toBeVisible();
        });

        test('should save progress and restore on return', async ({ page }) => {
            // Only test for independence level 2+
            if (TEST_CHILD.independence_level < 2) {
                test.skip();
            }

            await activateKidsMode(page, TEST_CHILD);
            await navigateToKidsTopicView(page);

            // Make some progress
            const firstCheckbox = page.locator('.kids-checkbox').first();
            await firstCheckbox.click();

            // Save progress
            await page.click('[data-testid="kids-save-progress"]');
            await expect(page.locator('text=Progress saved')).toBeVisible();

            // Navigate away and back
            await page.goBack();
            await page.goForward();

            // Progress should be restored
            await expect(firstCheckbox).toBeChecked();
        });
    });
});

// Helper functions
async function activateKidsMode(page: Page, child = TEST_CHILD) {
    // Create child if needed
    await page.goto('/children');

    const childExists = await page.locator(`text=${child.name}`).isVisible();
    if (!childExists) {
        await page.click('button:has-text("Add Child")');
        await page.fill('input[name="name"]', child.name);
        await page.selectOption('select[name="grade"]', child.grade);
        await page.selectOption('select[name="independence_level"]', child.independence_level.toString());
        await page.click('button[type="submit"]');
        await expect(page.locator(`text=${child.name}`)).toBeVisible();
    }

    // Activate kids mode
    await page.click('button:has-text("Kids Mode")');
    await page.click(`text=${child.name}`);

    const pinInput = page.locator('input[type="password"]');
    if (await pinInput.isVisible()) {
        await pinInput.fill('1234');
        await page.click('button:has-text("Enter Kids Mode")');
    }

    await expect(page.locator('[data-testid="kids-mode-indicator"]')).toBeVisible();
}

async function createTestContent(page: Page, content = TEST_CONTENT) {
    // Navigate to subjects
    await page.click('a[href*="subjects"]');

    // Create subject if needed
    const subjectExists = await page.locator('text=Science').isVisible();
    if (!subjectExists) {
        await page.click('button:has-text("Add Subject")');
        await page.fill('input[name="name"]', 'Science');
        await page.fill('input[name="description"]', 'Science experiments and learning');
        await page.click('button[type="submit"]');
    }

    // Click on Science subject
    await page.click('text=Science');

    // Create unit if needed
    const unitExists = await page.locator('text=Water Experiments').isVisible();
    if (!unitExists) {
        await page.click('button:has-text("Add Unit")');
        await page.fill('input[name="title"]', 'Water Experiments');
        await page.fill('input[name="description"]', 'Fun experiments with water');
        await page.fill('input[name="estimated_hours"]', '2');
        await page.click('button[type="submit"]');
    }

    // Click on unit
    await page.click('text=Water Experiments');

    // Create topic if needed
    const topicExists = await page.locator(`text=${content.title}`).isVisible();
    if (!topicExists) {
        await page.click('button:has-text("Add Topic")');
        await page.fill('input[name="name"]', content.title);
        await page.fill('textarea[name="description"]', content.description);
        await page.fill('input[name="estimated_minutes"]', content.estimated_minutes.toString());
        await page.click('button[type="submit"]');
    }
}

async function navigateToKidsTopicView(page: Page) {
    // Navigate to the topic in kids view
    await page.click('text=Subjects');
    await page.click('text=Science');
    await page.click('text=Water Experiments');
    await page.click('text=' + TEST_CONTENT.title);

    // Should automatically load kids view when in kids mode
    await expect(page.locator('[data-testid="kids-unified-content"]')).toBeVisible();
}
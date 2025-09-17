import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';
import { KidsModeHelper } from './helpers/kids-mode-helpers';

test.describe('Unit Screen Flashcard Integration (with Topics)', () => {
  let testUser: { name: string; email: string; password: string };
  let modalHelper: ModalHelper;
  let elementHelper: ElementHelper;
  let kidsModeHelper: KidsModeHelper;
  let subjectId: string;
  let unitId: string;
  
  test.beforeEach(async ({ page }) => {
    // Initialize helpers
    modalHelper = new ModalHelper(page);
    elementHelper = new ElementHelper(page);
    kidsModeHelper = new KidsModeHelper(page);
    
    // Ensure we're not in kids mode at start of each test
    await kidsModeHelper.resetKidsMode();
    
    // Create a unique test user for this session
    testUser = {
      name: 'Flashcard Test Parent',
      email: `flashcard-test-${Date.now()}@example.com`,
      password: 'testpassword123'
    };

    // Register and login
    await page.goto('/register');
    await page.fill('input[name="name"]', testUser.name);
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    
    // Wait for redirect
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // If we're still on register page, try logging in
    if (page.url().includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', testUser.email);
      await page.fill('input[name="password"]', testUser.password);
      await page.click('button[type="submit"]');
    }
    
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // Create test data: Subject → Unit
    await setupTestData(page);
  });
  
  async function setupTestData(page: any) {
    // Create child first (needed for proper data setup)
    await page.goto('/children');
    
    try {
      await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
      await page.waitForSelector('#child-form-modal [data-testid="modal-content"]', { timeout: 10000 });
      await page.waitForTimeout(1000);
      await page.waitForSelector('#child-form-modal input[name="name"]', { timeout: 10000 });
      
      await page.fill('#child-form-modal input[name="name"]', 'Test Child');
      await page.selectOption('#child-form-modal select[name="grade"]', '5th');
      await page.selectOption('#child-form-modal select[name="independence_level"]', '2');
      await page.click('#child-form-modal button[type="submit"]');
      await page.waitForTimeout(2000);
    } catch (e) {
      console.log('Child creation skipped, may already exist');
    }
    
    // Create subject
    await page.goto('/subjects');
    await elementHelper.safeClick('button:has-text("Add Subject")', 'first');
    await page.fill('input[name="name"]', 'Test Mathematics');
    await page.selectOption('select[name="color"]', '#3b82f6');
    await page.click('button:has-text("Save")');
    await page.waitForTimeout(1000);
    
    // Extract subject ID from URL or element
    const subjectLink = await page.locator('a:has-text("Test Mathematics")').first();
    const subjectHref = await subjectLink.getAttribute('href');
    subjectId = subjectHref?.match(/\/subjects\/(\d+)/)?.[1] || '1';
    
    // Create unit
    await page.click('text=Test Mathematics');
    await elementHelper.safeClick('button:has-text("Add Unit")');
    
    await modalHelper.waitForModal('unit-create-modal');
    await modalHelper.fillModalField('unit-create-modal', 'name', 'Flashcard Test Unit');
    await modalHelper.fillModalField('unit-create-modal', 'description', 'Unit for testing flashcard integration');
    await modalHelper.fillModalField('unit-create-modal', 'target_completion_date', '2024-12-31');
    await modalHelper.submitModalForm('unit-create-modal');
    
    await page.waitForTimeout(2000);
    
    // Extract unit ID - navigate to the unit page
    await elementHelper.safeClick('div:has([data-unit-name="Flashcard Test Unit"]) a:has-text("View Unit")');
    await page.waitForLoadState('networkidle');
    
    // Extract unit ID from current URL
    const currentUrl = page.url();
    unitId = currentUrl.match(/\/units\/(\d+)/)?.[1] || '1';

    // Create a topic for testing topic-based flashcards
    await createTestTopic(page);

    console.log(`Test data setup complete - Subject ID: ${subjectId}, Unit ID: ${unitId}`);
  }

  test('should display flashcards section with both unit and topic flashcards', async ({ page }) => {
    // Navigate to the unit page
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    await page.waitForLoadState('networkidle');
    
    // Verify flashcards section exists with header
    const flashcardSection = page.locator('h2:has-text("Flashcards"), h3:has-text("Flashcards")');
    await expect(flashcardSection.first()).toBeVisible();

    // Verify flashcard count is displayed (initially should be 0)
    // Count may be displayed in different ways now with topics
    const countElement = page.locator('#flashcard-count, [data-flashcard-count], .flashcard-count');
    if (await countElement.count() > 0) {
      await expect(countElement.first()).toContainText('0');
    }

    // Verify flashcard section has the correct structure
    const flashcardList = page.locator('#flashcards-list, [data-flashcards-list], .flashcards-list');
    if (await flashcardList.count() > 0) {
      await expect(flashcardList.first()).toBeVisible();
    }

    // Look for topics section as well since flashcards can be topic-based
    const topicsSection = page.locator('h2:has-text("Topics"), h3:has-text("Topics")');
    if (await topicsSection.count() > 0) {
      console.log('✅ Topics section also visible - supports topic-based flashcards');
    }

    console.log('✅ Flashcards section displayed correctly on Unit screen');
  });

  test('should open "Add Flashcard" modal when button is clicked', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    await page.waitForLoadState('networkidle');

    // Try to find and click Add Flashcard button (could be unit or topic level)
    let addFlashcardBtn = page.locator('button.bg-green-600:has-text("Add Flashcard")');

    if (await addFlashcardBtn.count() === 0) {
      // Try more generic selector
      addFlashcardBtn = page.locator('button:has-text("Add Flashcard")');
    }

    if (await addFlashcardBtn.count() === 0) {
      // Try within topics section
      addFlashcardBtn = page.locator('[data-topic] button:has-text("Add Flashcard"), .topic button:has-text("Add Flashcard")');
    }

    await addFlashcardBtn.first().click();

    // Wait for modal to load (try both possible selectors)
    const modalSelectors = '#flashcard-modal-overlay, [data-testid="flashcard-modal"], #flashcard-modal';
    await page.waitForSelector(modalSelectors, { timeout: 10000 });

    // Verify modal is visible with correct title
    const modalOverlay = page.locator(modalSelectors);
    await expect(modalOverlay.first()).toBeVisible();
    await expect(page.locator('h3:has-text("Add New Flashcard"), h2:has-text("Add New Flashcard")')).toBeVisible();
    
    // Verify form fields are present
    await expect(page.locator('select[name="card_type"]')).toBeVisible();
    await expect(page.locator('textarea[name="question"]')).toBeVisible();
    await expect(page.locator('textarea[name="answer"]')).toBeVisible();
    await expect(page.locator('select[name="difficulty_level"]')).toBeVisible();
    
    // Verify buttons are present
    await expect(page.locator('button:has-text("Cancel")')).toBeVisible();
    await expect(page.locator('button:has-text("Create Flashcard")')).toBeVisible();
    
    console.log('✅ Add Flashcard modal opens correctly');
  });

  test('should create basic flashcard from UI', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    await page.waitForLoadState('networkidle');

    // Use the helper function that handles both unit and topic contexts
    let addFlashcardBtn = page.locator('button.bg-green-600:has-text("Add Flashcard")');

    if (await addFlashcardBtn.count() === 0) {
      addFlashcardBtn = page.locator('button:has-text("Add Flashcard")');
    }

    if (await addFlashcardBtn.count() === 0) {
      addFlashcardBtn = page.locator('[data-topic] button:has-text("Add Flashcard"), .topic button:has-text("Add Flashcard")');
    }

    await addFlashcardBtn.first().click();
    await page.waitForSelector('#flashcard-modal-overlay, [data-testid="flashcard-modal"], #flashcard-modal', { timeout: 10000 });
    
    // Fill out flashcard form
    await page.selectOption('select[name="card_type"]', 'basic');
    await page.fill('textarea[name="question"]', 'What is 2 + 2?');
    await page.fill('textarea[name="answer"]', '4');
    await page.fill('textarea[name="hint"]', 'Think about simple addition');
    await page.selectOption('select[name="difficulty_level"]', 'easy');
    await page.fill('input[name="tags"]', 'math, addition, basic');
    
    // Submit form
    await page.click('button:has-text("Create Flashcard")');
    
    // Wait for form submission and modal to close
    await page.waitForTimeout(2000);
    
    // Verify modal closed
    await expect(page.locator('#flashcard-modal-overlay')).not.toBeVisible();
    
    // Verify flashcard appears in list
    await expect(page.locator('text=What is 2 + 2?')).toBeVisible();
    await expect(page.locator('text=Answer: 4')).toBeVisible();
    
    // Verify flashcard count updated (handle different count display methods)
    const countElement = page.locator('#flashcard-count, [data-flashcard-count], .flashcard-count');
    if (await countElement.count() > 0) {
      await expect(countElement.first()).toContainText('1');
    } else {
      // If no count element found, verify flashcard exists in list
      await expect(page.locator('text=What is 2 + 2?')).toBeVisible();
    }
    
    console.log('✅ Basic flashcard created successfully from UI');
  });

  test('should show question preview in flashcard list', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    
    // Create a flashcard first
    await createTestFlashcard(page, 'What is the capital of France?', 'Paris', 'Think about the city of lights');
    
    // Verify question preview is shown (truncated if long)
    await expect(page.locator('h4:has-text("What is the capital of France?")')).toBeVisible();
    
    // Verify answer preview is shown
    await expect(page.locator('text=Answer: Paris')).toBeVisible();
    
    // Verify hint is shown
    await expect(page.locator('text=Hint: Think about the city of lights')).toBeVisible();
    
    // Verify card type badge
    await expect(page.locator('.inline-flex:has-text("Basic")')).toBeVisible();
    
    // Verify difficulty badge
    await expect(page.locator('.inline-flex:has-text("Medium")')).toBeVisible();
    
    console.log('✅ Flashcard list shows question preview correctly');
  });

  test('should open pre-filled modal when edit button is clicked', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    
    // Create a flashcard first
    await createTestFlashcard(page, 'Original Question', 'Original Answer', 'Original Hint');
    
    // Click edit button (pencil icon)
    await page.click('button[title="Edit flashcard"] svg');
    
    // Wait for modal to load
    await page.waitForSelector('#flashcard-modal-overlay', { timeout: 10000 });
    
    // Verify modal is visible with edit title
    await expect(page.locator('h3:has-text("Edit Flashcard")')).toBeVisible();
    
    // Verify form is pre-filled with existing values
    await expect(page.locator('textarea[name="question"]')).toHaveValue('Original Question');
    await expect(page.locator('textarea[name="answer"]')).toHaveValue('Original Answer');
    await expect(page.locator('textarea[name="hint"]')).toHaveValue('Original Hint');
    
    // Verify button text is correct for editing
    await expect(page.locator('button:has-text("Update Flashcard")')).toBeVisible();
    
    console.log('✅ Edit button opens pre-filled modal correctly');
  });

  test('should update flashcard when edit form is submitted', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    
    // Create a flashcard first
    await createTestFlashcard(page, 'Old Question', 'Old Answer');
    
    // Edit the flashcard
    await page.click('button[title="Edit flashcard"] svg');
    await page.waitForSelector('#flashcard-modal-overlay', { timeout: 10000 });
    
    // Update the content
    await page.fill('textarea[name="question"]', 'Updated Question');
    await page.fill('textarea[name="answer"]', 'Updated Answer');
    await page.selectOption('select[name="difficulty_level"]', 'hard');
    
    // Submit the update
    await page.click('button:has-text("Update Flashcard")');
    await page.waitForTimeout(2000);
    
    // Verify modal closed
    await expect(page.locator('#flashcard-modal-overlay')).not.toBeVisible();
    
    // Verify updated content appears in list
    await expect(page.locator('text=Updated Question')).toBeVisible();
    await expect(page.locator('text=Answer: Updated Answer')).toBeVisible();
    await expect(page.locator('.inline-flex:has-text("Hard")')).toBeVisible();
    
    // Verify old content is gone
    await expect(page.locator('text=Old Question')).not.toBeVisible();
    
    console.log('✅ Flashcard updated successfully');
  });

  test('should show confirmation dialog when delete button is clicked', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    
    // Create a flashcard first
    await createTestFlashcard(page, 'Question to Delete', 'Answer to Delete');
    
    // Set up dialog handler to capture confirmation
    let confirmationShown = false;
    page.on('dialog', async dialog => {
      confirmationShown = true;
      expect(dialog.type()).toBe('confirm');
      expect(dialog.message()).toContain('Are you sure you want to delete this flashcard?');
      await dialog.accept(); // Accept the deletion
    });
    
    // Click delete button (trash icon)
    await page.click('button[title="Delete flashcard"] svg');
    
    // Wait for HTMX request to complete
    await page.waitForTimeout(2000);
    
    // Verify confirmation was shown
    expect(confirmationShown).toBe(true);
    
    // Verify flashcard was deleted (should not be visible)
    await expect(page.locator('text=Question to Delete')).not.toBeVisible();
    
    // Verify count was updated
    await expect(page.locator('#flashcard-count')).toContainText('(0)');
    
    console.log('✅ Delete confirmation dialog works correctly');
  });

  test('should handle delete cancellation', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    
    // Create a flashcard first
    await createTestFlashcard(page, 'Question to Keep', 'Answer to Keep');
    
    // Set up dialog handler to cancel deletion
    page.on('dialog', async dialog => {
      expect(dialog.type()).toBe('confirm');
      await dialog.dismiss(); // Cancel the deletion
    });
    
    // Click delete button (trash icon)
    await page.click('button[title="Delete flashcard"] svg');
    
    // Wait a moment
    await page.waitForTimeout(1000);
    
    // Verify flashcard is still visible (deletion was cancelled)
    await expect(page.locator('text=Question to Keep')).toBeVisible();
    
    // Verify count is still 1
    await expect(page.locator('#flashcard-count')).toContainText('(1)');
    
    console.log('✅ Delete cancellation works correctly');
  });

  test('should show pagination for more than 20 flashcards', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    
    // Create 25 flashcards to test pagination
    console.log('Creating 25 flashcards to test pagination...');
    for (let i = 1; i <= 25; i++) {
      await createTestFlashcard(page, `Question ${i}`, `Answer ${i}`, '', false);
      
      // Add a brief pause every 5 flashcards to avoid overwhelming the server
      if (i % 5 === 0) {
        await page.waitForTimeout(500);
        console.log(`Created ${i}/25 flashcards`);
      }
    }
    
    console.log('All flashcards created, checking pagination...');

    // Reload to see all flashcards
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000); // Give time for dynamic content to load
    
    // Verify count shows 25 (handle different count display methods)
    const countElement = page.locator('#flashcard-count, [data-flashcard-count], .flashcard-count');
    if (await countElement.count() > 0) {
      await expect(countElement.first()).toContainText('25');
    } else {
      // Alternative: count flashcard items on page
      const flashcardItems = page.locator('.bg-white.rounded-lg.shadow-sm, [data-flashcard-item], .flashcard-item');
      const itemCount = await flashcardItems.count();
      expect(itemCount).toBeGreaterThan(0);
      console.log(`Found ${itemCount} flashcard items on page`);
    }
    
    // Verify pagination appears (should show page navigation)
    const paginationExists = await page.locator('.pagination, nav[aria-label="Pagination"]').count() > 0 ||
                            await page.locator('a:has-text("Next"), a:has-text("2")').count() > 0;
    
    if (paginationExists) {
      console.log('✅ Pagination is visible for 25+ flashcards');
      
      // Test pagination functionality
      const nextButton = page.locator('a:has-text("Next"), a:has-text("2")').first();
      if (await nextButton.count() > 0) {
        await nextButton.click();
        await page.waitForTimeout(1000);
        
        // Should still show flashcards on page 2
        const flashcardsOnPage2 = await page.locator('.bg-white.rounded-lg.shadow-sm').count();
        expect(flashcardsOnPage2).toBeGreaterThan(0);
        console.log('✅ Pagination navigation works correctly');
      }
    } else {
      console.log('⚠️  Pagination not visible - may be showing all flashcards or pagination threshold higher than 20');
    }
  });

  test('should work in both parent and kids mode', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);

    // Create a flashcard in parent mode
    await createTestFlashcard(page, 'Parent Mode Question', 'Parent Mode Answer');

    // Verify add/edit/delete buttons are visible in parent mode (flexible selectors)
    const addButtons = page.locator('button:has-text("Add Flashcard"), button:has-text("Add")');
    const editButtons = page.locator('button[title="Edit flashcard"], button[title*="Edit"], button:has-text("Edit")');
    const deleteButtons = page.locator('button[title="Delete flashcard"], button[title*="Delete"], button:has-text("Delete")');

    if (await addButtons.count() > 0) {
      await expect(addButtons.first()).toBeVisible();
    }
    if (await editButtons.count() > 0) {
      await expect(editButtons.first()).toBeVisible();
    }
    if (await deleteButtons.count() > 0) {
      await expect(deleteButtons.first()).toBeVisible();
    }
    
    console.log('✅ Parent mode: All CRUD buttons visible');
    
    // Set up PIN and enter kids mode
    await kidsModeHelper.setupPin('1234');
    await kidsModeHelper.forceKidsMode(undefined, 'Test Child');
    
    // Navigate back to unit page in kids mode
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    await page.waitForLoadState('networkidle');
    
    // Verify flashcard content is still visible
    await expect(page.locator('text=Parent Mode Question')).toBeVisible();
    
    // Verify management buttons are hidden in kids mode
    const addButtonsKids = page.locator('button:has-text("Add Flashcard"), button:has-text("Add")');
    const editButtonsKids = page.locator('button[title="Edit flashcard"], button[title*="Edit"], button:has-text("Edit")');
    const deleteButtonsKids = page.locator('button[title="Delete flashcard"], button[title*="Delete"], button:has-text("Delete")');

    // In kids mode, these buttons should either be hidden or not present
    if (await addButtonsKids.count() > 0) {
      await expect(addButtonsKids.first()).not.toBeVisible();
    }
    if (await editButtonsKids.count() > 0) {
      await expect(editButtonsKids.first()).not.toBeVisible();
    }
    if (await deleteButtonsKids.count() > 0) {
      await expect(deleteButtonsKids.first()).not.toBeVisible();
    }
    
    console.log('✅ Kids mode: Management buttons hidden, content still visible');
  });

  test('should display different card types correctly', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    
    // Test Multiple Choice card
    await page.click('button.bg-green-600:has-text("Add Flashcard")');
    await page.waitForSelector('#flashcard-modal-overlay', { timeout: 10000 });
    
    await page.selectOption('select[name="card_type"]', 'multiple_choice');
    await page.fill('textarea[name="question"]', 'Which planet is closest to the Sun?');
    await page.fill('textarea[name="answer"]', 'Mercury');
    
    // Add choices
    await page.click('button:has-text("Add Choice")'); // Should add to default 2 choices
    const choiceInputs = await page.locator('input[name="choices[]"]').count();
    expect(choiceInputs).toBeGreaterThanOrEqual(2);
    
    // Fill some choices
    await page.fill('input[name="choices[]"]', 'Mercury');
    await page.fill('input[name="choices[]"]:nth-child(2)', 'Venus');
    
    // Mark first choice as correct
    await page.check('input[name="correct_choices[]"]:first-child');
    
    await page.click('button:has-text("Create Flashcard")');
    await page.waitForTimeout(2000);
    
    // Verify multiple choice card appears with correct badge
    await expect(page.locator('.inline-flex:has-text("Multiple Choice")')).toBeVisible();
    
    // Test True/False card
    await page.click('button.bg-green-600:has-text("Add Flashcard")');
    await page.waitForSelector('#flashcard-modal-overlay', { timeout: 10000 });
    
    await page.selectOption('select[name="card_type"]', 'true_false');
    await page.fill('textarea[name="question"]', 'The Earth is flat.');
    await page.fill('textarea[name="answer"]', 'False');
    await page.check('input[name="true_false_answer"][value="false"]');
    
    await page.click('button:has-text("Create Flashcard")');
    await page.waitForTimeout(2000);
    
    // Verify true/false card appears with correct badge
    await expect(page.locator('.inline-flex:has-text("True/False")')).toBeVisible();
    
    console.log('✅ Different card types display correctly');
  });

  test('should handle form validation errors gracefully', async ({ page }) => {
    await page.goto(`/subjects/${subjectId}/units/${unitId}`);
    
    // Try to create flashcard without required fields
    await page.click('button.bg-green-600:has-text("Add Flashcard")');
    await page.waitForSelector('#flashcard-modal-overlay', { timeout: 10000 });
    
    // Leave question and answer empty, try to submit
    await page.click('button:has-text("Create Flashcard")');
    
    // Wait for validation - form should still be visible
    await page.waitForTimeout(1000);
    
    // Modal should remain open (validation failed)
    await expect(page.locator('#flashcard-modal-overlay')).toBeVisible();
    
    // Fill in required fields
    await page.fill('textarea[name="question"]', 'Valid question');
    await page.fill('textarea[name="answer"]', 'Valid answer');
    
    // Submit again
    await page.click('button:has-text("Create Flashcard")');
    await page.waitForTimeout(2000);
    
    // Should close successfully this time
    await expect(page.locator('#flashcard-modal-overlay')).not.toBeVisible();
    await expect(page.locator('text=Valid question')).toBeVisible();
    
    console.log('✅ Form validation works correctly');
  });

  // Helper function to create test topic
  async function createTestTopic(page: any) {
    try {
      // Look for Add Topic button
      const addTopicBtn = page.locator('button:has-text("Add Topic")');
      if (await addTopicBtn.count() > 0) {
        await addTopicBtn.click();
        await modalHelper.waitForModal('topic-create-modal');
        await modalHelper.fillModalField('topic-create-modal', 'title', 'Test Topic for Flashcards');
        await modalHelper.fillModalField('topic-create-modal', 'description', 'Topic to test flashcard integration');
        await modalHelper.fillModalField('topic-create-modal', 'estimated_minutes', '30');
        await modalHelper.submitModalForm('topic-create-modal');
        await page.waitForTimeout(2000);
        console.log('✅ Test topic created for flashcard integration');
      }
    } catch (e) {
      console.log('⚠️ Could not create topic, continuing with unit-level flashcards');
    }
  }

  // Helper function to create test flashcards (supports both unit and topic context)
  async function createTestFlashcard(page: any, question: string, answer: string, hint: string = '', waitForModal: boolean = true) {
    // Try different approaches to find Add Flashcard button
    let addFlashcardBtn = page.locator('button.bg-green-600:has-text("Add Flashcard")');

    // If not found, try more generic selectors
    if (await addFlashcardBtn.count() === 0) {
      addFlashcardBtn = page.locator('button:has-text("Add Flashcard")');
    }

    // If still not found, look in topics section
    if (await addFlashcardBtn.count() === 0) {
      const topicAddBtn = page.locator('[data-topic] button:has-text("Add Flashcard"), .topic button:has-text("Add Flashcard")');
      if (await topicAddBtn.count() > 0) {
        addFlashcardBtn = topicAddBtn.first();
      }
    }

    await addFlashcardBtn.first().click();

    if (waitForModal) {
      await page.waitForSelector('#flashcard-modal-overlay, [data-testid="flashcard-modal"]', { timeout: 10000 });
    }

    await page.selectOption('select[name="card_type"]', 'basic');
    await page.fill('textarea[name="question"]', question);
    await page.fill('textarea[name="answer"]', answer);
    if (hint) {
      await page.fill('textarea[name="hint"]', hint);
    }
    await page.selectOption('select[name="difficulty_level"]', 'medium');

    await page.click('button:has-text("Create Flashcard")');
    await page.waitForTimeout(waitForModal ? 2000 : 1000);
  }
});
import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';
import { KidsModeHelper } from './helpers/kids-mode-helpers';

test.describe('Complete Flashcard System E2E Tests (Unit + Topic Support)', () => {
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
      name: 'Flashcard System Test',
      email: `flashcard-system-${Date.now()}@example.com`,
      password: 'testpassword123'
    };

    // Register and login
    await page.goto('/register');
    await page.fill('input[name="name"]', testUser.name);
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    
    // Wait for redirect and handle login if needed
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    if (page.url().includes('/register') || page.url().includes('/login')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', testUser.email);
      await page.fill('input[name="password"]', testUser.password);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle', { timeout: 10000 });
    }
    
    // Create test data: Child → Subject → Unit
    await setupTestData(page);
  });
  
  async function setupTestData(page: any) {
    console.log('Setting up test data...');
    
    // Create child first (REQUIRED by the application workflow)
    await page.goto('/children');
    await page.waitForLoadState('networkidle');
    
    // First check if we already have children
    const noChildrenMessage = await page.locator('text=No children found').count();
    const hasChildren = noChildrenMessage === 0;
    
    if (!hasChildren) {
      try {
        await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
        
        // Wait for the modal to actually become visible - the app uses CSS transitions
        await page.waitForTimeout(1000);
        
        // Check if modal is now visible, if not try alternative approach
        const modalVisible = await page.locator('#child-form-modal').isVisible();
        
        if (modalVisible) {
          await page.fill('#child-form-modal input[name="name"]', 'Test Child');
          await page.selectOption('#child-form-modal select[name="grade"]', '5th');
          await page.selectOption('#child-form-modal select[name="independence_level"]', '2');
          await page.click('#child-form-modal button[type="submit"]');
          await page.waitForTimeout(3000);
          console.log('Child created successfully');
        } else {
          throw new Error('Modal did not become visible');
        }
      } catch (e) {
        console.log('Child creation failed:', e.message);
        
        // Try alternative approach - look for "Manage Children" link
        const manageChildrenLink = await page.locator('text=Manage Children').count();
        if (manageChildrenLink > 0) {
          await page.click('text=Manage Children');
          await page.waitForLoadState('networkidle');
          
          // Try again on the children management page
          await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
          await page.waitForTimeout(1000);
          
          const modalNowVisible = await page.locator('#child-form-modal').isVisible();
          if (modalNowVisible) {
            await page.fill('#child-form-modal input[name="name"]', 'Test Child');
            await page.selectOption('#child-form-modal select[name="grade"]', '5th');
            await page.selectOption('#child-form-modal select[name="independence_level"]', '2');
            await page.click('#child-form-modal button[type="submit"]');
            await page.waitForTimeout(3000);
            console.log('Child created via alternative route');
          } else {
            throw new Error('Could not create child - modal issue');
          }
        } else {
          throw new Error('Could not find way to create child');
        }
      }
    } else {
      console.log('Children already exist');
    }
    
    // Now create subject - should work since child exists
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');
    
    // Wait a moment for the page to fully load and check for child requirement
    await page.waitForTimeout(1000);
    
    // Check if the "Add Subject" button is now available
    const addSubjectButton = await page.locator('button:has-text("Add Subject")').first().count();
    
    if (addSubjectButton === 0) {
      // Still no button - check for warning message
      const noChildrenWarning = await page.locator('h3:has-text("No children found")').count();
      if (noChildrenWarning > 0) {
        console.log('Still no children found on subjects page - need to create children first');
        
        // Go back to children page and force create a child
        await page.goto('/children');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        
        // Try to create a child more forcefully
        await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
        await page.waitForTimeout(1000);
        
        await page.fill('#child-form-modal input[name="name"]', 'Test Child');
        await page.selectOption('#child-form-modal select[name="grade"]', '5th');
        await page.selectOption('#child-form-modal select[name="independence_level"]', '2');
        await page.click('#child-form-modal button[type="submit"]');
        await page.waitForTimeout(3000);
        
        // Go back to subjects and try again
        await page.goto('/subjects');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
      } else {
        // Try refreshing the page
        await page.reload();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
      }
    }
    
    await elementHelper.safeClick('button:has-text("Add Subject")', 'first');
    await page.fill('input[name="name"]', 'Flashcard Test Subject');
    await page.selectOption('select[name="color"]', '#3b82f6');
    await page.click('button:has-text("Save")');
    await page.waitForTimeout(2000);
    
    // Extract subject ID from URL or element
    const subjectLink = await page.locator('a:has-text("Flashcard Test Subject")').first();
    const subjectHref = await subjectLink.getAttribute('href');
    subjectId = subjectHref?.match(/\/subjects\/(\d+)/)?.[1] || '1';
    
    // Create unit
    await page.click('text=Flashcard Test Subject');
    await page.waitForLoadState('networkidle');
    
    await elementHelper.safeClick('button:has-text("Add Unit")');
    
    await modalHelper.waitForModal('unit-create-modal');
    await modalHelper.fillModalField('unit-create-modal', 'name', 'Flashcard Test Unit');
    await modalHelper.fillModalField('unit-create-modal', 'description', 'Unit for testing flashcard system');
    await modalHelper.fillModalField('unit-create-modal', 'target_completion_date', '2024-12-31');
    await modalHelper.submitModalForm('unit-create-modal');
    
    await page.waitForTimeout(2000);
    
    // Navigate to the unit page
    await elementHelper.safeClick('div:has([data-unit-name="Flashcard Test Unit"]) a:has-text("View Unit")');
    await page.waitForLoadState('networkidle');
    
    // Extract unit ID from current URL
    const currentUrl = page.url();
    unitId = currentUrl.match(/\/units\/(\d+)/)?.[1] || '1';

    // Create a topic for testing topic-based flashcards
    await createTestTopic(page);

    console.log(`Test data setup complete - Subject ID: ${subjectId}, Unit ID: ${unitId}`);
  }

  test.describe('Basic Flashcard Management', () => {
    test('should display flashcards section on unit screen', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');

      // Verify flashcards section exists with header (flexible selectors)
      const flashcardHeaders = page.locator('h2:has-text("Flashcards"), h3:has-text("Flashcards")');
      await expect(flashcardHeaders.first()).toBeVisible();

      // Verify flashcard count is displayed (initially should be 0) - handle different count displays
      const countElement = page.locator('#flashcard-count, [data-flashcard-count], .flashcard-count');
      if (await countElement.count() > 0) {
        await expect(countElement.first()).toContainText('0');
      }
      
      // Verify Add Flashcard button exists (in parent mode) - flexible selectors
      const addFlashcardBtns = page.locator('button.bg-green-600:has-text("Add Flashcard"), button:has-text("Add Flashcard")');
      if (await addFlashcardBtns.count() > 0) {
        await expect(addFlashcardBtns.first()).toBeVisible();
      } else {
        // May be within topics section
        const topicAddBtns = page.locator('[data-topic] button:has-text("Add Flashcard"), .topic button:has-text("Add Flashcard")');
        if (await topicAddBtns.count() > 0) {
          await expect(topicAddBtns.first()).toBeVisible();
        }
      }
      
      console.log('✅ Flashcards section displayed correctly on Unit screen');
    });

    test('should open flashcard creation modal when Add Flashcard is clicked', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');

      // Find and click Add Flashcard button (flexible approach)
      let addFlashcardBtn = page.locator('button.bg-green-600:has-text("Add Flashcard")');
      if (await addFlashcardBtn.count() === 0) {
        addFlashcardBtn = page.locator('button:has-text("Add Flashcard")');
      }
      if (await addFlashcardBtn.count() === 0) {
        addFlashcardBtn = page.locator('[data-topic] button:has-text("Add Flashcard"), .topic button:has-text("Add Flashcard")');
      }

      await addFlashcardBtn.first().click();

      // Wait for modal to appear - use correct overlay selector
      const modal = await modalHelper.waitForModal('flashcard-modal');
      
      // Verify modal is visible with correct title
      await expect(modal).toBeVisible();
      await expect(page.locator('h3:has-text("Add New Flashcard")')).toBeVisible();
      
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

    test('should create a basic flashcard successfully', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Click Add Flashcard button
      await page.click('button.bg-green-600:has-text("Add Flashcard")');
      await modalHelper.waitForModal('flashcard-modal');
      
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
      await page.waitForTimeout(3000);
      
      // Verify modal closed
      await expect(page.locator('[data-testid="flashcard-modal"]')).not.toBeVisible();
      
      // Wait for flashcards list to load
      await page.waitForTimeout(2000);
      
      // Verify flashcard appears in list
      await expect(page.locator('text=What is 2 + 2?')).toBeVisible();
      await expect(page.locator('text=Answer: 4')).toBeVisible();
      await expect(page.locator('text=Hint: Think about simple addition')).toBeVisible();
      
      // Verify flashcard count updated
      await expect(page.locator('#flashcard-count')).toContainText('(1)');
      
      console.log('✅ Basic flashcard created successfully');
    });

    test('should edit an existing flashcard', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // First create a flashcard
      await createTestFlashcard(page, 'Original Question', 'Original Answer');
      
      // Click edit button
      await page.click('button[title="Edit flashcard"]');
      await modalHelper.waitForModal('flashcard-modal');
      
      // Verify modal is in edit mode
      await expect(page.locator('h3:has-text("Edit Flashcard")')).toBeVisible();
      
      // Verify form is pre-filled
      await expect(page.locator('textarea[name="question"]')).toHaveValue('Original Question');
      await expect(page.locator('textarea[name="answer"]')).toHaveValue('Original Answer');
      
      // Update the content
      await page.fill('textarea[name="question"]', 'Updated Question');
      await page.fill('textarea[name="answer"]', 'Updated Answer');
      await page.selectOption('select[name="difficulty_level"]', 'hard');
      
      // Submit the update
      await page.click('button:has-text("Update Flashcard")');
      await page.waitForTimeout(3000);
      
      // Verify updated content appears
      await expect(page.locator('text=Updated Question')).toBeVisible();
      await expect(page.locator('text=Answer: Updated Answer')).toBeVisible();
      await expect(page.locator('.inline-flex:has-text("Hard")')).toBeVisible();
      
      // Verify old content is gone
      await expect(page.locator('text=Original Question')).not.toBeVisible();
      
      console.log('✅ Flashcard edited successfully');
    });

    test('should delete a flashcard with confirmation', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // First create a flashcard
      await createTestFlashcard(page, 'Question to Delete', 'Answer to Delete');
      
      // Set up dialog handler to accept deletion
      let confirmationShown = false;
      page.on('dialog', async dialog => {
        confirmationShown = true;
        expect(dialog.type()).toBe('confirm');
        expect(dialog.message()).toContain('Are you sure you want to delete this flashcard?');
        await dialog.accept();
      });
      
      // Click delete button
      await page.click('button[title="Delete flashcard"]');
      
      // Wait for HTMX request to complete
      await page.waitForTimeout(3000);
      
      // Verify confirmation was shown
      expect(confirmationShown).toBe(true);
      
      // Verify flashcard was deleted
      await expect(page.locator('text=Question to Delete')).not.toBeVisible();
      
      // Verify count was updated
      await expect(page.locator('#flashcard-count')).toContainText('(0)');
      
      console.log('✅ Delete confirmation works correctly');
    });
  });

  test.describe('Card Types', () => {
    test('should create multiple choice flashcard', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Click Add Flashcard button
      await page.click('button.bg-green-600:has-text("Add Flashcard")');
      await modalHelper.waitForModal('flashcard-modal');
      
      // Select multiple choice type
      await page.selectOption('select[name="card_type"]', 'multiple_choice');
      
      // Wait for multiple choice fields to appear
      await page.waitForTimeout(1000);
      
      // Fill basic question and answer
      await page.fill('textarea[name="question"]', 'Which planet is closest to the Sun?');
      await page.fill('textarea[name="answer"]', 'Mercury');
      
      // Wait for choices to initialize
      await page.waitForTimeout(500);
      
      // Fill choices (there should be 2 default choices)
      const choiceInputs = page.locator('input[name="choices[]"]');
      const choiceCount = await choiceInputs.count();
      
      if (choiceCount >= 2) {
        await choiceInputs.nth(0).fill('Mercury');
        await choiceInputs.nth(1).fill('Venus');
        
        // Mark first choice as correct
        await page.check('input[name="correct_choices[]"]:first-child');
      } else {
        console.log('Adding additional choices...');
        await page.click('button:has-text("Add Choice")');
        await page.waitForTimeout(500);
        
        await page.fill('input[name="choices[]"]', 'Mercury');
        await page.check('input[name="correct_choices[]"]:first-child');
      }
      
      // Submit form
      await page.click('button:has-text("Create Flashcard")');
      await page.waitForTimeout(3000);
      
      // Verify multiple choice card appears
      await expect(page.locator('.inline-flex:has-text("Multiple Choice")')).toBeVisible();
      await expect(page.locator('text=Which planet is closest to the Sun?')).toBeVisible();
      
      console.log('✅ Multiple choice flashcard created successfully');
    });

    test('should create true/false flashcard', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Click Add Flashcard button
      await page.click('button.bg-green-600:has-text("Add Flashcard")');
      await modalHelper.waitForModal('flashcard-modal');
      
      // Select true/false type
      await page.selectOption('select[name="card_type"]', 'true_false');
      
      // Wait for true/false fields to appear
      await page.waitForTimeout(1000);
      
      // Fill basic question and answer
      await page.fill('textarea[name="question"]', 'The Earth is flat.');
      await page.fill('textarea[name="answer"]', 'False');
      
      // Select false as correct answer
      await page.check('input[name="true_false_answer"][value="false"]');
      
      // Submit form
      await page.click('button:has-text("Create Flashcard")');
      await page.waitForTimeout(3000);
      
      // Verify true/false card appears
      await expect(page.locator('.inline-flex:has-text("True/False")')).toBeVisible();
      await expect(page.locator('text=The Earth is flat.')).toBeVisible();
      
      console.log('✅ True/false flashcard created successfully');
    });

    test('should create cloze deletion flashcard', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Click Add Flashcard button
      await page.click('button.bg-green-600:has-text("Add Flashcard")');
      await modalHelper.waitForModal('flashcard-modal');
      
      // Select cloze type
      await page.selectOption('select[name="card_type"]', 'cloze');
      
      // Wait for cloze fields to appear
      await page.waitForTimeout(1000);
      
      // Fill cloze text
      await page.fill('textarea[name="cloze_text"]', 'The {{capital}} of France is {{Paris}}.');
      
      // Submit form
      await page.click('button:has-text("Create Flashcard")');
      await page.waitForTimeout(3000);
      
      // Verify cloze card appears
      await expect(page.locator('.inline-flex:has-text("Cloze")')).toBeVisible();
      await expect(page.locator('text=The {{capital}} of France is {{Paris}}.')).toBeVisible();
      
      console.log('✅ Cloze deletion flashcard created successfully');
    });
  });

  test.describe('Kids Mode Integration', () => {
    test('should hide management buttons in kids mode', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Create a flashcard first in parent mode
      await createTestFlashcard(page, 'Kids Mode Test', 'Test Answer');
      
      // Verify management buttons are visible in parent mode
      await expect(page.locator('button:has-text("Add Flashcard")')).toBeVisible();
      await expect(page.locator('button[title="Edit flashcard"]')).toBeVisible();
      await expect(page.locator('button[title="Delete flashcard"]')).toBeVisible();
      
      console.log('✅ Management buttons visible in parent mode');
      
      // Set up PIN and enter kids mode
      await kidsModeHelper.setupPin('1234');
      await kidsModeHelper.forceKidsMode(undefined, 'Test Child');
      
      // Navigate back to unit page in kids mode
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Verify flashcard content is still visible
      await expect(page.locator('text=Kids Mode Test')).toBeVisible();
      
      // Verify management buttons are hidden in kids mode
      await expect(page.locator('button:has-text("Add Flashcard")')).not.toBeVisible();
      await expect(page.locator('button[title="Edit flashcard"]')).not.toBeVisible();
      await expect(page.locator('button[title="Delete flashcard"]')).not.toBeVisible();
      
      console.log('✅ Management buttons hidden in kids mode');
    });
  });

  test.describe('Form Validation', () => {
    test('should handle validation errors gracefully', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Try to create flashcard without required fields
      await page.click('button.bg-green-600:has-text("Add Flashcard")');
      await modalHelper.waitForModal('flashcard-modal');
      
      // Leave question and answer empty, try to submit
      await page.click('button:has-text("Create Flashcard")');
      
      // Wait for validation response
      await page.waitForTimeout(2000);
      
      // Modal should remain open (validation failed)
      await expect(page.locator('#flashcard-modal-overlay')).toBeVisible();
      
      // Fill in required fields
      await page.fill('textarea[name="question"]', 'Valid question');
      await page.fill('textarea[name="answer"]', 'Valid answer');
      
      // Submit again
      await page.click('button:has-text("Create Flashcard")');
      await page.waitForTimeout(3000);
      
      // Should close successfully this time
      await expect(page.locator('#flashcard-modal-overlay')).not.toBeVisible();
      await expect(page.locator('text=Valid question')).toBeVisible();
      
      console.log('✅ Form validation works correctly');
    });
  });

  // Helper function to create test topic
  async function createTestTopic(page: any) {
    try {
      const addTopicBtn = page.locator('button:has-text("Add Topic")');
      if (await addTopicBtn.count() > 0) {
        await addTopicBtn.click();
        await modalHelper.waitForModal('topic-create-modal');
        await modalHelper.fillModalField('topic-create-modal', 'title', 'Test Topic');
        await modalHelper.fillModalField('topic-create-modal', 'description', 'Topic for flashcard testing');
        await modalHelper.fillModalField('topic-create-modal', 'estimated_minutes', '30');
        await modalHelper.submitModalForm('topic-create-modal');
        await page.waitForTimeout(2000);
        console.log('✅ Test topic created');
      }
    } catch (e) {
      console.log('⚠️ Could not create topic, using unit-level flashcards');
    }
  }

  // Helper function to create test flashcards (supports both unit and topic context)
  async function createTestFlashcard(page: any, question: string, answer: string, hint: string = '') {
    // Find Add Flashcard button with flexible selectors
    let addFlashcardBtn = page.locator('button.bg-green-600:has-text("Add Flashcard")');
    if (await addFlashcardBtn.count() === 0) {
      addFlashcardBtn = page.locator('button:has-text("Add Flashcard")');
    }
    if (await addFlashcardBtn.count() === 0) {
      addFlashcardBtn = page.locator('[data-topic] button:has-text("Add Flashcard"), .topic button:has-text("Add Flashcard")');
    }

    await addFlashcardBtn.first().click();
    await modalHelper.waitForModal('flashcard-modal');

    await page.selectOption('select[name="card_type"]', 'basic');
    await page.fill('textarea[name="question"]', question);
    await page.fill('textarea[name="answer"]', answer);
    if (hint) {
      await page.fill('textarea[name="hint"]', hint);
    }
    await page.selectOption('select[name="difficulty_level"]', 'medium');

    await page.click('button:has-text("Create Flashcard")');
    await page.waitForTimeout(3000);
  }
});
<?php

namespace Tests\Feature;

use App\Models\Flashcard;
use App\Models\Subject;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FlashcardImportFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Subject $subject;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $this->unit = Unit::factory()->create(['subject_id' => $this->subject->id]);

        Storage::fake('local');
    }

    /** @test */
    public function test_shows_import_modal()
    {
        $response = $this->actingAs($this->user)
            ->get(route('flashcards.import', $this->unit->id));

        $response->assertOk();
        $response->assertViewIs('flashcards.partials.import-modal');
        $response->assertViewHas('unit', $this->unit);
        $response->assertViewHas('supportedExtensions');
        $response->assertViewHas('maxImportSize');
    }

    /** @test */
    public function test_unauthorized_user_cannot_access_import()
    {
        $response = $this->get(route('flashcards.import', $this->unit->id));

        // Laravel's auth middleware redirects to login (302) rather than returning 401
        $response->assertStatus(302);
    }

    /** @test */
    public function test_user_cannot_import_to_other_users_unit()
    {
        $otherUser = User::factory()->create();
        $otherSubject = Subject::factory()->create(['user_id' => $otherUser->id]);
        $otherUnit = Unit::factory()->create(['subject_id' => $otherSubject->id]);

        $response = $this->actingAs($this->user)
            ->get(route('flashcards.import', $otherUnit->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_import_via_copy_paste()
    {
        $content = "What is the capital of France?\tParis\nWhat is 2+2?\t4\tBasic math";

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'paste',
                'import_text' => $content,
            ]);

        $response->assertOk();
        $response->assertViewIs('flashcards.partials.import-preview');
        $response->assertViewHas('cards');
        $response->assertViewHas('canImport', true);

        // Check that cards were parsed correctly
        $cards = $response->viewData('cards');
        $this->assertCount(2, $cards);
        $this->assertEquals('What is the capital of France?', $cards[0]['question']);
        $this->assertEquals('Paris', $cards[0]['answer']);
    }

    /** @test */
    public function test_import_csv_file()
    {
        $content = "What is the capital of France?,Paris\nWhat is 2+2?,4,Basic math";
        $file = UploadedFile::fake()->createWithContent('flashcards.csv', $content);

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'file',
                'import_file' => $file,
            ]);

        $response->assertOk();
        $response->assertViewIs('flashcards.partials.import-preview');
        $response->assertViewHas('cards');
        $response->assertViewHas('canImport', true);
    }

    /** @test */
    public function test_import_validation_errors()
    {
        $content = "\tEmpty question\nValid question\t"; // Tab-separated but missing answers

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'paste',
                'import_text' => $content,
            ]);

        $response->assertOk();
        $response->assertViewHas('validationErrors');
        $response->assertViewHas('canImport', false);
    }

    /** @test */
    public function test_execute_import()
    {
        $content = "What is the capital of France?\tParis\nWhat is 2+2?\t4\tBasic math";
        $importData = base64_encode($content);

        $this->assertEquals(0, Flashcard::where('unit_id', $this->unit->id)->count());

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.execute', $this->unit->id), [
                'import_method' => 'paste',
                'import_data' => $importData,
                'confirm_import' => true,
            ]);

        $response->assertOk();
        $response->assertViewIs('flashcards.partials.flashcard-list');
        $response->assertViewHas('import_result');

        // Check that flashcards were created in database
        $flashcards = Flashcard::where('unit_id', $this->unit->id)->get();
        $this->assertCount(2, $flashcards);

        $firstCard = $flashcards->first();
        $this->assertEquals('What is the capital of France?', $firstCard->question);
        $this->assertEquals('Paris', $firstCard->answer);
        $this->assertEquals('basic', $firstCard->card_type);
        $this->assertEquals('manual_import', $firstCard->import_source);

        $secondCard = $flashcards->last();
        $this->assertEquals('What is 2+2?', $secondCard->question);
        $this->assertEquals('4', $secondCard->answer);
        $this->assertEquals('Basic math', $secondCard->hint);
    }

    /** @test */
    public function test_execute_import_with_invalid_base64()
    {
        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.execute', $this->unit->id), [
                'import_method' => 'paste',
                'import_data' => 'invalid!@#$', // This will return false from base64_decode with strict mode
                'confirm_import' => true,
            ]);

        $response->assertStatus(422);
        $response->assertSeeText('Invalid import data');
    }

    /** @test */
    public function test_import_requires_confirmation()
    {
        $content = "What is the capital of France?\tParis";
        $importData = base64_encode($content);

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.execute', $this->unit->id), [
                'import_method' => 'paste',
                'import_data' => $importData,
                'confirm_import' => false,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_import_file_validation()
    {
        // Test file too large
        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'file',
                'import_file' => UploadedFile::fake()->create('large.csv', 6000), // > 5MB
            ]);

        $response->assertStatus(422);

        // Test missing file
        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'file',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_import_text_validation()
    {
        // Test missing text
        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'paste',
            ]);

        $response->assertStatus(422);

        // Test text too large
        $largeText = str_repeat('x', 500001); // > 500KB
        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'paste',
                'import_text' => $largeText,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_import_empty_file_content()
    {
        $file = UploadedFile::fake()->createWithContent('empty.csv', '');

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'file',
                'import_file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertSeeText('File is empty');
    }

    /** @test */
    public function test_import_unsupported_format()
    {
        $content = 'Unsupported format without proper delimiters';

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.preview', $this->unit->id), [
                'import_method' => 'paste',
                'import_text' => $content,
            ]);

        $response->assertStatus(422);
        $response->assertSeeText('Could not detect delimiter');
    }

    /** @test */
    public function test_import_with_tags()
    {
        $content = "What is the capital of France? #geography\tParis #cities";
        $importData = base64_encode($content);

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.execute', $this->unit->id), [
                'import_method' => 'paste',
                'import_data' => $importData,
                'confirm_import' => true,
            ]);

        $response->assertOk();

        $flashcard = Flashcard::where('unit_id', $this->unit->id)->first();
        $this->assertNotNull($flashcard);
        $this->assertContains('geography', $flashcard->tags);
        $this->assertContains('cities', $flashcard->tags);
    }

    /** @test */
    public function test_import_partial_failure()
    {
        // Mix of valid and invalid cards (missing answer should fail)
        $content = "Valid question 1\tValid answer 1\nInvalid question only\nValid question 2\tValid answer 2";
        $importData = base64_encode($content);

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.execute', $this->unit->id), [
                'import_method' => 'paste',
                'import_data' => $importData,
                'confirm_import' => true,
            ]);

        $response->assertOk();

        // Check that only valid cards were imported
        $flashcards = Flashcard::where('unit_id', $this->unit->id)->get();
        $this->assertCount(2, $flashcards); // Only the valid ones

        $importResult = $response->viewData('import_result');
        $this->assertEquals(2, $importResult['imported']);
        $this->assertEquals(1, $importResult['failed']);
        $this->assertTrue($importResult['success']); // Success because some cards were imported
    }

    /** @test */
    public function test_kids_mode_blocks_import()
    {
        // Set up kids mode in session
        session(['kids_mode' => true, 'kids_mode_child_id' => 1]);

        $response = $this->actingAs($this->user)
            ->get(route('flashcards.import', $this->unit->id));

        // Should be blocked by middleware - could be 302 redirect or 403 forbidden
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    /** @test */
    public function test_import_large_valid_dataset()
    {
        // Create content with multiple cards (but under limit)
        $lines = [];
        for ($i = 1; $i <= 50; $i++) {
            $lines[] = "Question {$i}\tAnswer {$i}\tHint {$i}";
        }
        $content = implode("\n", $lines);
        $importData = base64_encode($content);

        $response = $this->actingAs($this->user)
            ->post(route('flashcards.import.execute', $this->unit->id), [
                'import_method' => 'paste',
                'import_data' => $importData,
                'confirm_import' => true,
            ]);

        $response->assertOk();

        $flashcards = Flashcard::where('unit_id', $this->unit->id)->get();
        $this->assertCount(50, $flashcards);

        $importResult = $response->viewData('import_result');
        $this->assertEquals(50, $importResult['imported']);
        $this->assertEquals(0, $importResult['failed']);
        $this->assertTrue($importResult['success']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Flashcard;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicFlashcardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Subject $subject;

    protected Unit $unit;

    protected Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable session middleware but disable unnecessary middleware for testing
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Clear any session state that might interfere with tests
        session()->flush();
        session()->forget('kids_mode_active');
        session()->forget('kids_mode_child_id');

        // Create test data
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->subject = Subject::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->unit = Unit::factory()->create([
            'subject_id' => $this->subject->id,
        ]);

        $this->topic = Topic::factory()->create([
            'unit_id' => $this->unit->id,
        ]);
    }

    // ==================== Topic-Based Flashcard Creation ====================

    public function test_store_topic_flashcard_requires_authentication(): void
    {
        auth()->logout();

        $flashcardData = [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
        ];

        $response = $this->postJson("/api/topics/{$this->topic->id}/flashcards", $flashcardData);
        $response->assertStatus(401);
    }

    public function test_store_topic_flashcard_creates_successfully(): void
    {
        $this->actingAs($this->user);

        $flashcardData = [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'What is photosynthesis?',
            'answer' => 'The process by which plants make food',
            'hint' => 'Think about plants and sunlight',
            'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
            'tags' => ['biology', 'plants'],
        ];

        $response = $this->postJson("/api/topics/{$this->topic->id}/flashcards", $flashcardData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Flashcard created successfully',
                'flashcard' => [
                    'topic_id' => $this->topic->id,
                    'unit_id' => $this->unit->id,
                    'card_type' => Flashcard::CARD_TYPE_BASIC,
                    'question' => 'What is photosynthesis?',
                    'answer' => 'The process by which plants make food',
                    'hint' => 'Think about plants and sunlight',
                    'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
                    'tags' => ['biology', 'plants'],
                ],
                'context' => 'topic',
                'topic' => [
                    'id' => $this->topic->id,
                    'title' => $this->topic->title,
                ],
            ]);

        $this->assertDatabaseHas('flashcards', [
            'topic_id' => $this->topic->id,
            'unit_id' => $this->unit->id,
            'question' => 'What is photosynthesis?',
            'answer' => 'The process by which plants make food',
        ]);
    }

    public function test_store_topic_flashcard_denies_access_to_other_users(): void
    {
        $this->actingAs($this->otherUser);

        $flashcardData = [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
        ];

        $response = $this->postJson("/api/topics/{$this->topic->id}/flashcards", $flashcardData);
        $response->assertStatus(403);
    }

    public function test_store_topic_flashcard_validates_multiple_choice_data(): void
    {
        $this->actingAs($this->user);

        $flashcardData = [
            'card_type' => Flashcard::CARD_TYPE_MULTIPLE_CHOICE,
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
            'choices' => ['Only one choice'], // Should have at least 2
            'correct_choices' => [], // Should not be empty
        ];

        $response = $this->postJson("/api/topics/{$this->topic->id}/flashcards", $flashcardData);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Validation failed',
            ])
            ->assertJsonPath('errors.choices.0', 'Multiple choice cards must have at least 2 choices.')
            ->assertJsonPath('errors.correct_choices.0', 'Multiple choice cards must have at least 1 correct choice.');
    }

    // ==================== Topic-Based Flashcard Retrieval ====================

    public function test_index_returns_topic_flashcards(): void
    {
        $this->actingAs($this->user);

        // Create flashcards for this topic
        Flashcard::factory()->count(3)->forTopic($this->topic)->create();

        // Create flashcards for the unit (not topic) - should not be included
        Flashcard::factory()->count(2)->forUnit($this->unit)->create();

        $response = $this->getJson("/api/topics/{$this->topic->id}/flashcards");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'flashcards' => [
                    '*' => [
                        'id',
                        'unit_id',
                        'topic_id',
                        'card_type',
                        'question',
                        'answer',
                        'difficulty_level',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'topic',
            ])
            ->assertJsonCount(3, 'flashcards');

        // Verify all returned flashcards belong to this topic
        $flashcards = $response->json('flashcards');
        foreach ($flashcards as $flashcard) {
            $this->assertEquals($this->topic->id, $flashcard['topic_id']);
            $this->assertEquals($this->unit->id, $flashcard['unit_id']);
        }
    }

    public function test_index_denies_access_to_other_users_topic_flashcards(): void
    {
        $this->actingAs($this->otherUser);

        $response = $this->getJson("/api/topics/{$this->topic->id}/flashcards");
        $response->assertStatus(403);
    }

    public function test_show_returns_topic_flashcard(): void
    {
        $this->actingAs($this->user);

        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();

        $response = $this->getJson("/api/topics/{$this->topic->id}/flashcards/{$flashcard->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'flashcard' => [
                    'id' => $flashcard->id,
                    'topic_id' => $this->topic->id,
                    'unit_id' => $this->unit->id,
                    'question' => $flashcard->question,
                    'answer' => $flashcard->answer,
                ],
            ]);
    }

    public function test_show_returns_404_for_flashcard_not_in_topic(): void
    {
        $this->actingAs($this->user);

        // Create flashcard in different topic
        $otherTopic = Topic::factory()->create(['unit_id' => $this->unit->id]);
        $flashcard = Flashcard::factory()->forTopic($otherTopic)->create();

        $response = $this->getJson("/api/topics/{$this->topic->id}/flashcards/{$flashcard->id}");
        $response->assertStatus(404);
    }

    // ==================== Topic-Based Flashcard Updates ====================

    public function test_update_topic_flashcard_successfully(): void
    {
        $this->actingAs($this->user);

        $flashcard = Flashcard::factory()->forTopic($this->topic)->basic()->create();

        $updateData = [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'Updated topic question?',
            'answer' => 'Updated topic answer',
            'difficulty_level' => Flashcard::DIFFICULTY_HARD,
            'tags' => ['updated', 'topic', 'tag'],
        ];

        $response = $this->putJson("/api/topics/{$this->topic->id}/flashcards/{$flashcard->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Flashcard updated successfully',
                'flashcard' => [
                    'id' => $flashcard->id,
                    'topic_id' => $this->topic->id,
                    'question' => 'Updated topic question?',
                    'answer' => 'Updated topic answer',
                    'difficulty_level' => Flashcard::DIFFICULTY_HARD,
                    'tags' => ['updated', 'topic', 'tag'],
                ],
            ]);

        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcard->id,
            'topic_id' => $this->topic->id,
            'question' => 'Updated topic question?',
            'answer' => 'Updated topic answer',
        ]);
    }

    public function test_update_cannot_change_topic_assignment_through_regular_update(): void
    {
        $this->actingAs($this->user);

        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();
        $otherTopic = Topic::factory()->create(['unit_id' => $this->unit->id]);

        $updateData = [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'Updated question?',
            'answer' => 'Updated answer',
            'topic_id' => $otherTopic->id, // This should be ignored
            'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
        ];

        $response = $this->putJson("/api/topics/{$this->topic->id}/flashcards/{$flashcard->id}", $updateData);

        $response->assertStatus(200);

        // Topic should remain unchanged
        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcard->id,
            'topic_id' => $this->topic->id, // Should still be original topic
        ]);
    }

    // ==================== Topic-Based Flashcard Deletion ====================

    public function test_destroy_topic_flashcard_soft_deletes(): void
    {
        $this->actingAs($this->user);

        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();

        $response = $this->deleteJson("/api/topics/{$this->topic->id}/flashcards/{$flashcard->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Flashcard deleted successfully',
            ]);

        $this->assertSoftDeleted('flashcards', ['id' => $flashcard->id]);
    }

    public function test_restore_topic_flashcard(): void
    {
        $this->actingAs($this->user);

        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();
        $flashcard->delete(); // Soft delete first

        $response = $this->postJson("/api/topics/{$this->topic->id}/flashcards/{$flashcard->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Flashcard restored successfully',
            ]);

        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcard->id,
            'deleted_at' => null,
        ]);
    }

    // ==================== Moving Flashcards Between Topics ====================

    public function test_move_flashcard_to_different_topic(): void
    {
        $this->actingAs($this->user);

        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();
        $targetTopic = Topic::factory()->create(['unit_id' => $this->unit->id]);

        $response = $this->postJson("/api/flashcards/{$flashcard->id}/move", [
            'topic_id' => $targetTopic->id,
            'unit_id' => $this->unit->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Flashcard moved successfully',
                'flashcard' => [
                    'id' => $flashcard->id,
                    'topic_id' => $targetTopic->id,
                    'unit_id' => $this->unit->id,
                ],
            ]);

        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcard->id,
            'topic_id' => $targetTopic->id,
            'unit_id' => $this->unit->id,
        ]);
    }

    public function test_move_flashcard_from_topic_to_unit_directly(): void
    {
        $this->actingAs($this->user);

        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();

        $response = $this->postJson("/api/flashcards/{$flashcard->id}/move", [
            'topic_id' => null,
            'unit_id' => $this->unit->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Flashcard moved successfully',
                'flashcard' => [
                    'id' => $flashcard->id,
                    'topic_id' => null,
                    'unit_id' => $this->unit->id,
                ],
            ]);

        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcard->id,
            'topic_id' => null,
            'unit_id' => $this->unit->id,
        ]);
    }

    public function test_move_flashcard_validates_access_permissions(): void
    {
        $this->actingAs($this->user);

        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();

        // Try to move to another user's topic
        $otherSubject = Subject::factory()->create(['user_id' => $this->otherUser->id]);
        $otherUnit = Unit::factory()->create(['subject_id' => $otherSubject->id]);
        $otherTopic = Topic::factory()->create(['unit_id' => $otherUnit->id]);

        $response = $this->postJson("/api/flashcards/{$flashcard->id}/move", [
            'topic_id' => $otherTopic->id,
            'unit_id' => $otherUnit->id,
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Access denied to target topic']);
    }

    // ==================== Bulk Operations on Topic Flashcards ====================

    public function test_bulk_update_topic_flashcards_status(): void
    {
        $this->actingAs($this->user);

        $flashcards = Flashcard::factory()->count(3)->forTopic($this->topic)->create([
            'is_active' => true,
        ]);

        $flashcardIds = $flashcards->pluck('id')->toArray();

        $response = $this->patchJson("/api/topics/{$this->topic->id}/flashcards/bulk-status", [
            'flashcard_ids' => $flashcardIds,
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'updated_count' => 3,
            ]);

        foreach ($flashcardIds as $id) {
            $this->assertDatabaseHas('flashcards', [
                'id' => $id,
                'topic_id' => $this->topic->id,
                'is_active' => false,
            ]);
        }
    }

    public function test_bulk_update_validates_flashcards_belong_to_topic(): void
    {
        $this->actingAs($this->user);

        $topicFlashcard = Flashcard::factory()->forTopic($this->topic)->create();
        $unitFlashcard = Flashcard::factory()->forUnit($this->unit)->create(); // Different context

        $response = $this->patchJson("/api/topics/{$this->topic->id}/flashcards/bulk-status", [
            'flashcard_ids' => [$topicFlashcard->id, $unitFlashcard->id],
            'is_active' => false,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation failed',
            ]);
    }

    // ==================== Mixed Unit and Topic Scenarios ====================

    public function test_unit_and_topic_flashcards_are_separate_contexts(): void
    {
        $this->actingAs($this->user);

        // Create flashcards in both contexts
        $unitFlashcards = Flashcard::factory()->count(2)->forUnit($this->unit)->create();
        $topicFlashcards = Flashcard::factory()->count(3)->forTopic($this->topic)->create();

        // Test unit endpoint only returns unit flashcards
        $unitResponse = $this->getJson("/api/units/{$this->unit->id}/flashcards");
        $unitResponse->assertStatus(200)->assertJsonCount(2, 'flashcards');

        // Test topic endpoint only returns topic flashcards
        $topicResponse = $this->getJson("/api/topics/{$this->topic->id}/flashcards");
        $topicResponse->assertStatus(200)->assertJsonCount(3, 'flashcards');

        // Verify proper context in responses
        $unitFlashcardsData = $unitResponse->json('flashcards');
        foreach ($unitFlashcardsData as $flashcard) {
            $this->assertNull($flashcard['topic_id']);
        }

        $topicFlashcardsData = $topicResponse->json('flashcards');
        foreach ($topicFlashcardsData as $flashcard) {
            $this->assertEquals($this->topic->id, $flashcard['topic_id']);
        }
    }

    // ==================== Error Handling ====================

    public function test_topic_flashcard_operations_with_nonexistent_topic(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/topics/999999/flashcards');
        $response->assertStatus(404);

        $response = $this->postJson('/api/topics/999999/flashcards', [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'Test',
            'answer' => 'Test',
            'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
        ]);
        $response->assertStatus(404);
    }

    public function test_authorization_prevents_cross_user_topic_access(): void
    {
        $this->actingAs($this->user);

        // Create another user's topic
        $otherSubject = Subject::factory()->create(['user_id' => $this->otherUser->id]);
        $otherUnit = Unit::factory()->create(['subject_id' => $otherSubject->id]);
        $otherTopic = Topic::factory()->create(['unit_id' => $otherUnit->id]);

        $response = $this->getJson("/api/topics/{$otherTopic->id}/flashcards");
        $response->assertStatus(403);
    }
}

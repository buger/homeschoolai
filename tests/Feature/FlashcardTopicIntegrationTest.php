<?php

namespace Tests\Feature;

use App\Models\Flashcard;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashcardTopicIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Subject $subject;

    protected Unit $unit;

    protected Topic $topic1;

    protected Topic $topic2;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF for API testing
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Create test data hierarchy
        $this->user = User::factory()->create();
        $this->subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $this->unit = Unit::factory()->create(['subject_id' => $this->subject->id]);
        $this->topic1 = Topic::factory()->create(['unit_id' => $this->unit->id, 'title' => 'Biology Basics']);
        $this->topic2 = Topic::factory()->create(['unit_id' => $this->unit->id, 'title' => 'Advanced Biology']);
    }

    // ==================== Complete Workflow Integration Tests ====================

    public function test_complete_topic_flashcard_lifecycle(): void
    {
        $this->actingAs($this->user);

        // Step 1: Create a topic-based flashcard
        $createResponse = $this->postJson("/api/topics/{$this->topic1->id}/flashcards", [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'What is photosynthesis?',
            'answer' => 'The process by which plants make food using sunlight',
            'hint' => 'Think about plants and sunlight',
            'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
            'tags' => ['biology', 'plants', 'energy'],
        ]);

        $createResponse->assertStatus(201);
        $flashcardId = $createResponse->json('flashcard.id');

        // Verify creation in database
        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcardId,
            'topic_id' => $this->topic1->id,
            'unit_id' => $this->unit->id,
            'question' => 'What is photosynthesis?',
        ]);

        // Step 2: Retrieve the flashcard
        $showResponse = $this->getJson("/api/topics/{$this->topic1->id}/flashcards/{$flashcardId}");
        $showResponse->assertStatus(200)
            ->assertJson([
                'flashcard' => [
                    'id' => $flashcardId,
                    'topic_id' => $this->topic1->id,
                    'question' => 'What is photosynthesis?',
                ],
            ]);

        // Step 3: Update the flashcard
        $updateResponse = $this->putJson("/api/topics/{$this->topic1->id}/flashcards/{$flashcardId}", [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'What is photosynthesis in plants?',
            'answer' => 'The process by which plants convert sunlight, water, and CO2 into glucose',
            'difficulty_level' => Flashcard::DIFFICULTY_HARD,
            'tags' => ['biology', 'plants', 'energy', 'glucose'],
        ]);

        $updateResponse->assertStatus(200);

        // Verify update in database
        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcardId,
            'question' => 'What is photosynthesis in plants?',
            'difficulty_level' => Flashcard::DIFFICULTY_HARD,
        ]);

        // Step 4: Move flashcard to different topic
        $moveResponse = $this->postJson("/api/flashcards/{$flashcardId}/move", [
            'topic_id' => $this->topic2->id,
            'unit_id' => $this->unit->id,
        ]);

        $moveResponse->assertStatus(200);

        // Verify move in database
        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcardId,
            'topic_id' => $this->topic2->id,
            'unit_id' => $this->unit->id,
        ]);

        // Step 5: Soft delete the flashcard
        $deleteResponse = $this->deleteJson("/api/topics/{$this->topic2->id}/flashcards/{$flashcardId}");
        $deleteResponse->assertStatus(200);

        // Verify soft deletion
        $this->assertSoftDeleted('flashcards', ['id' => $flashcardId]);

        // Step 6: Restore the flashcard
        $restoreResponse = $this->postJson("/api/topics/{$this->topic2->id}/flashcards/{$flashcardId}/restore");
        $restoreResponse->assertStatus(200);

        // Verify restoration
        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcardId,
            'deleted_at' => null,
        ]);
    }

    public function test_multiple_topics_with_flashcards_data_integrity(): void
    {
        $this->actingAs($this->user);

        // Create flashcards in multiple topics
        $topic1Flashcards = [];
        $topic2Flashcards = [];
        $unitFlashcards = [];

        // Topic 1 flashcards
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson("/api/topics/{$this->topic1->id}/flashcards", [
                'card_type' => Flashcard::CARD_TYPE_BASIC,
                'question' => "Topic 1 Question {$i}",
                'answer' => "Topic 1 Answer {$i}",
                'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
            ]);
            $topic1Flashcards[] = $response->json('flashcard.id');
        }

        // Topic 2 flashcards
        for ($i = 1; $i <= 2; $i++) {
            $response = $this->postJson("/api/topics/{$this->topic2->id}/flashcards", [
                'card_type' => Flashcard::CARD_TYPE_MULTIPLE_CHOICE,
                'question' => "Topic 2 Question {$i}",
                'answer' => 'A',
                'choices' => ['A', 'B', 'C'],
                'correct_choices' => [0],
                'difficulty_level' => Flashcard::DIFFICULTY_EASY,
            ]);
            $topic2Flashcards[] = $response->json('flashcard.id');
        }

        // Unit flashcards (no topic)
        for ($i = 1; $i <= 2; $i++) {
            $response = $this->postJson("/api/units/{$this->unit->id}/flashcards", [
                'card_type' => Flashcard::CARD_TYPE_BASIC,
                'question' => "Unit Question {$i}",
                'answer' => "Unit Answer {$i}",
                'difficulty_level' => Flashcard::DIFFICULTY_HARD,
            ]);
            $unitFlashcards[] = $response->json('flashcard.id');
        }

        // Verify data integrity
        $this->assertEquals(3, count($topic1Flashcards));
        $this->assertEquals(2, count($topic2Flashcards));
        $this->assertEquals(2, count($unitFlashcards));

        // Test topic 1 endpoint returns only topic 1 flashcards
        $topic1Response = $this->getJson("/api/topics/{$this->topic1->id}/flashcards");
        $topic1Response->assertStatus(200)->assertJsonCount(3, 'flashcards');

        $topic1Data = $topic1Response->json('flashcards');
        foreach ($topic1Data as $flashcard) {
            $this->assertEquals($this->topic1->id, $flashcard['topic_id']);
            $this->assertContains($flashcard['id'], $topic1Flashcards);
        }

        // Test topic 2 endpoint returns only topic 2 flashcards
        $topic2Response = $this->getJson("/api/topics/{$this->topic2->id}/flashcards");
        $topic2Response->assertStatus(200)->assertJsonCount(2, 'flashcards');

        $topic2Data = $topic2Response->json('flashcards');
        foreach ($topic2Data as $flashcard) {
            $this->assertEquals($this->topic2->id, $flashcard['topic_id']);
            $this->assertContains($flashcard['id'], $topic2Flashcards);
        }

        // Test unit endpoint returns only unit flashcards (no topic_id)
        $unitResponse = $this->getJson("/api/units/{$this->unit->id}/flashcards");
        $unitResponse->assertStatus(200)->assertJsonCount(2, 'flashcards');

        $unitData = $unitResponse->json('flashcards');
        foreach ($unitData as $flashcard) {
            $this->assertNull($flashcard['topic_id']);
            $this->assertContains($flashcard['id'], $unitFlashcards);
        }

        // Test Unit model methods
        $unit = Unit::find($this->unit->id);
        $this->assertEquals(2, $unit->getDirectFlashcardsCount());
        $this->assertEquals(5, $unit->getTopicFlashcardsCount()); // 3 + 2
        $this->assertEquals(7, $unit->getAllFlashcardsCount()); // 2 + 3 + 2
        $this->assertTrue($unit->hasDirectFlashcards());
        $this->assertTrue($unit->hasTopicFlashcards());
        $this->assertTrue($unit->hasAnyFlashcards());

        // Test Topic model methods
        $topic1 = Topic::find($this->topic1->id);
        $topic2 = Topic::find($this->topic2->id);
        $this->assertEquals(3, $topic1->getFlashcardsCount());
        $this->assertEquals(2, $topic2->getFlashcardsCount());
        $this->assertTrue($topic1->hasFlashcards());
        $this->assertTrue($topic2->hasFlashcards());
    }

    public function test_moving_flashcards_between_contexts(): void
    {
        $this->actingAs($this->user);

        // Create flashcard in topic 1
        $createResponse = $this->postJson("/api/topics/{$this->topic1->id}/flashcards", [
            'card_type' => Flashcard::CARD_TYPE_BASIC,
            'question' => 'Original question',
            'answer' => 'Original answer',
            'difficulty_level' => Flashcard::DIFFICULTY_MEDIUM,
        ]);

        $flashcardId = $createResponse->json('flashcard.id');

        // Step 1: Move from topic 1 to topic 2
        $moveToTopic2 = $this->postJson("/api/flashcards/{$flashcardId}/move", [
            'topic_id' => $this->topic2->id,
            'unit_id' => $this->unit->id,
        ]);

        $moveToTopic2->assertStatus(200);
        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcardId,
            'topic_id' => $this->topic2->id,
            'unit_id' => $this->unit->id,
        ]);

        // Verify it's no longer in topic 1
        $topic1Response = $this->getJson("/api/topics/{$this->topic1->id}/flashcards");
        $topic1Response->assertJsonCount(0, 'flashcards');

        // Verify it's now in topic 2
        $topic2Response = $this->getJson("/api/topics/{$this->topic2->id}/flashcards");
        $topic2Response->assertJsonCount(1, 'flashcards');

        // Step 2: Move from topic 2 to unit directly (no topic)
        $moveToUnit = $this->postJson("/api/flashcards/{$flashcardId}/move", [
            'topic_id' => null,
            'unit_id' => $this->unit->id,
        ]);

        $moveToUnit->assertStatus(200);
        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcardId,
            'topic_id' => null,
            'unit_id' => $this->unit->id,
        ]);

        // Verify it's no longer in topic 2
        $topic2Response = $this->getJson("/api/topics/{$this->topic2->id}/flashcards");
        $topic2Response->assertJsonCount(0, 'flashcards');

        // Verify it's now in unit
        $unitResponse = $this->getJson("/api/units/{$this->unit->id}/flashcards");
        $unitResponse->assertJsonCount(1, 'flashcards');

        // Step 3: Move from unit back to topic 1
        $moveBackToTopic = $this->postJson("/api/flashcards/{$flashcardId}/move", [
            'topic_id' => $this->topic1->id,
            'unit_id' => $this->unit->id,
        ]);

        $moveBackToTopic->assertStatus(200);
        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcardId,
            'topic_id' => $this->topic1->id,
            'unit_id' => $this->unit->id,
        ]);

        // Final verification
        $topic1Response = $this->getJson("/api/topics/{$this->topic1->id}/flashcards");
        $topic1Response->assertJsonCount(1, 'flashcards');

        $unitResponse = $this->getJson("/api/units/{$this->unit->id}/flashcards");
        $unitResponse->assertJsonCount(0, 'flashcards');
    }
}

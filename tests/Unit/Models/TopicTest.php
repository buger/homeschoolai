<?php

namespace Tests\Unit\Models;

use App\Models\Flashcard;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Subject $subject;

    protected Unit $unit;

    protected Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
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

    public function test_topic_belongs_to_unit(): void
    {
        $this->assertInstanceOf(Unit::class, $this->topic->unit);
        $this->assertEquals($this->unit->id, $this->topic->unit->id);
    }

    public function test_topic_has_many_flashcards(): void
    {
        $flashcards = Flashcard::factory()->count(3)->forTopic($this->topic)->create();

        $this->assertCount(3, $this->topic->flashcards);
        $this->assertInstanceOf(Flashcard::class, $this->topic->flashcards->first());

        // Verify all flashcards belong to this topic
        foreach ($this->topic->flashcards as $flashcard) {
            $this->assertEquals($this->topic->id, $flashcard->topic_id);
            $this->assertEquals($this->unit->id, $flashcard->unit_id);
        }
    }

    public function test_topic_flashcards_relationship_only_returns_active(): void
    {
        $activeFlashcard = Flashcard::factory()->forTopic($this->topic)->create(['is_active' => true]);
        $inactiveFlashcard = Flashcard::factory()->forTopic($this->topic)->create(['is_active' => false]);

        $flashcards = $this->topic->flashcards;

        $this->assertCount(1, $flashcards);
        $this->assertEquals($activeFlashcard->id, $flashcards->first()->id);
    }

    public function test_topic_can_have_no_flashcards(): void
    {
        $this->assertCount(0, $this->topic->flashcards);
        $this->assertFalse($this->topic->hasFlashcards());
        $this->assertEquals(0, $this->topic->getFlashcardsCount());
    }

    public function test_topic_flashcard_count_methods(): void
    {
        // Create different types of flashcards
        Flashcard::factory()->count(2)->forTopic($this->topic)->create(['is_active' => true]);
        Flashcard::factory()->count(1)->forTopic($this->topic)->create(['is_active' => false]);

        $this->assertEquals(2, $this->topic->getFlashcardsCount());
        $this->assertTrue($this->topic->hasFlashcards());

        // Test that count only includes active flashcards
        $allFlashcards = Flashcard::where('topic_id', $this->topic->id)->get();
        $this->assertCount(3, $allFlashcards); // 2 active + 1 inactive
    }

    public function test_topic_with_flashcards_scope(): void
    {
        $topicWithFlashcards = Topic::factory()->create(['unit_id' => $this->unit->id]);
        $topicWithoutFlashcards = Topic::factory()->create(['unit_id' => $this->unit->id]);

        Flashcard::factory()->forTopic($topicWithFlashcards)->create();

        $topicsWithFlashcards = Topic::withFlashcards()->get();

        $this->assertCount(1, $topicsWithFlashcards);
        $this->assertEquals($topicWithFlashcards->id, $topicsWithFlashcards->first()->id);
    }

    public function test_topic_for_unit_scope(): void
    {
        $otherUnit = Unit::factory()->create(['subject_id' => $this->subject->id]);
        $otherTopic = Topic::factory()->create(['unit_id' => $otherUnit->id]);

        $topicsForUnit = Topic::forUnit($this->unit->id);

        $this->assertCount(1, $topicsForUnit);
        $this->assertEquals($this->topic->id, $topicsForUnit->first()->id);
    }

    public function test_topic_required_and_optional_scopes(): void
    {
        $requiredTopic = Topic::factory()->required()->create(['unit_id' => $this->unit->id]);
        $optionalTopic = Topic::factory()->optional()->create(['unit_id' => $this->unit->id]);

        $requiredTopics = Topic::required()->get();
        $optionalTopics = Topic::optional()->get();

        $this->assertTrue($requiredTopics->contains($requiredTopic));
        $this->assertFalse($requiredTopics->contains($optionalTopic));

        $this->assertTrue($optionalTopics->contains($optionalTopic));
        $this->assertFalse($optionalTopics->contains($requiredTopic));
    }

    public function test_topic_has_prerequisites(): void
    {
        $topicWithPrerequisites = Topic::factory()->withPrerequisites([1, 2, 3])->create(['unit_id' => $this->unit->id]);
        $topicWithoutPrerequisites = Topic::factory()->create(['unit_id' => $this->unit->id]);

        $this->assertTrue($topicWithPrerequisites->hasPrerequisites());
        $this->assertFalse($topicWithoutPrerequisites->hasPrerequisites());
    }

    public function test_topic_complexity_score_calculation(): void
    {
        $simpleTopic = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'learning_content' => 'Simple content',
            'content_assets' => ['images' => [], 'files' => []],
            'prerequisites' => [],
        ]);

        $complexTopic = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'learning_content' => str_repeat('Complex content with many words. ', 100), // ~500 words
            'content_assets' => [
                'images' => ['image1.jpg', 'image2.jpg'],
                'files' => ['file1.pdf', 'file2.doc', 'file3.txt'],
            ],
            'prerequisites' => [1, 2],
        ]);

        $simpleScore = $simpleTopic->getComplexityScore();
        $complexScore = $complexTopic->getComplexityScore();

        $this->assertGreaterThan($simpleScore, $complexScore);
        $this->assertLessThanOrEqual(10, $complexScore); // Cap at 10
        $this->assertGreaterThanOrEqual(1, $simpleScore); // Minimum 1
    }

    public function test_topic_estimated_reading_time(): void
    {
        $shortTopic = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'learning_content' => 'Short content.',
            'content_assets' => ['images' => [], 'files' => []],
        ]);

        $longTopic = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'learning_content' => str_repeat('This is a long piece of content. ', 200), // ~1000 words
            'content_assets' => [
                'images' => ['img1.jpg', 'img2.jpg'], // +1 minute
                'files' => ['file1.pdf'], // +1 minute
            ],
        ]);

        $shortTime = $shortTopic->getEstimatedReadingTime();
        $longTime = $longTopic->getEstimatedReadingTime();

        $this->assertGreaterThanOrEqual(1, $shortTime); // Minimum 1 minute
        $this->assertGreaterThan($shortTime, $longTime);
        $this->assertGreaterThan(6, $longTime); // Should be >6min (5min reading + 2min assets)
    }

    public function test_topic_content_assets_methods(): void
    {
        $topicWithAssets = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'content_assets' => [
                'images' => ['image1.jpg'],
                'files' => ['document.pdf'],
            ],
        ]);

        $topicWithoutAssets = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'content_assets' => null,
        ]);

        $this->assertTrue($topicWithAssets->hasContentAssets());
        $this->assertFalse($topicWithoutAssets->hasContentAssets());

        $assets = $topicWithAssets->getContentAssets();
        $this->assertIsArray($assets);
        $this->assertArrayHasKey('images', $assets);
        $this->assertArrayHasKey('files', $assets);
        $this->assertCount(1, $assets['images']);
        $this->assertCount(1, $assets['files']);
    }

    public function test_topic_learning_materials_methods(): void
    {
        $topicWithContent = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'learning_content' => 'Some learning content',
        ]);

        $topicWithoutContent = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'learning_content' => null,
        ]);

        $this->assertTrue($topicWithContent->hasLearningMaterials());
        $this->assertTrue($topicWithContent->hasRichContent());
        $this->assertFalse($topicWithoutContent->hasLearningMaterials());
        $this->assertFalse($topicWithoutContent->hasRichContent());

        $this->assertEquals('Some learning content', $topicWithContent->getContent());
        $this->assertEquals('Some learning content', $topicWithContent->getUnifiedContent());
    }

    public function test_topic_update_content_assets(): void
    {
        $topic = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'content_assets' => ['images' => ['old.jpg'], 'files' => []],
        ]);

        $topic->updateContentAssets(['images' => ['new.jpg'], 'files' => ['new.pdf']]);

        $updatedAssets = $topic->fresh()->getContentAssets();
        // array_merge replaces keys, so only new values will be present
        $this->assertEquals(['new.jpg'], $updatedAssets['images']);
        $this->assertEquals(['new.pdf'], $updatedAssets['files']);
    }

    public function test_topic_to_array_includes_computed_properties(): void
    {
        Flashcard::factory()->count(2)->forTopic($this->topic)->create();

        $array = $this->topic->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('unit_id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('has_content_assets', $array);
        $this->assertArrayHasKey('estimated_reading_time', $array);
        $this->assertArrayHasKey('complexity_score', $array);
        $this->assertArrayHasKey('has_prerequisites', $array);
        $this->assertArrayHasKey('flashcards_count', $array);
        $this->assertArrayHasKey('has_flashcards', $array);

        $this->assertEquals(2, $array['flashcards_count']);
        $this->assertTrue($array['has_flashcards']);
    }

    public function test_topic_static_methods_compatibility(): void
    {
        $topics = Topic::forUnit($this->unit->id);

        $this->assertCount(1, $topics);
        $this->assertEquals($this->topic->id, $topics->first()->id);
    }

    public function test_topic_find_with_string_id_compatibility(): void
    {
        $found = Topic::find((string) $this->topic->id);

        $this->assertInstanceOf(Topic::class, $found);
        $this->assertEquals($this->topic->id, $found->id);
    }

    public function test_topic_kids_and_admin_content_methods(): void
    {
        $topic = Topic::factory()->create([
            'unit_id' => $this->unit->id,
            'learning_content' => 'Test content for kids and admin',
        ]);

        $this->assertEquals('Test content for kids and admin', $topic->getKidsContent());
        $this->assertEquals('Test content for kids and admin', $topic->getAdminContent());
        $this->assertEquals('Test content for kids and admin', $topic->getContent());
    }

    public function test_topic_flashcard_deletion_behavior(): void
    {
        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();

        $this->assertTrue($this->topic->hasFlashcards());

        // Soft delete flashcard
        $flashcard->delete();

        // Topic should no longer show it has flashcards (only active)
        $this->assertFalse($this->topic->fresh()->hasFlashcards());
        $this->assertEquals(0, $this->topic->fresh()->getFlashcardsCount());
    }

    public function test_topic_flashcard_cascade_considerations(): void
    {
        $flashcard = Flashcard::factory()->forTopic($this->topic)->create();

        // Verify relationship integrity
        $this->assertEquals($this->topic->id, $flashcard->topic_id);
        $this->assertEquals($this->unit->id, $flashcard->unit_id);

        // Note: Actual cascade behavior would depend on database foreign key constraints
        // This test verifies the relationship setup is correct
        $this->assertTrue($flashcard->exists);
        $this->assertTrue($this->topic->exists);
    }
}

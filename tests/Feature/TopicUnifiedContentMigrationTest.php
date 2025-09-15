<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicUnifiedContentMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $subject;

    protected $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test subject and unit
        $this->subject = Subject::create([
            'name' => 'Test Subject',
            'color' => '#3B82F6',
            'user_id' => $this->user->id,
        ]);

        $this->unit = Unit::create([
            'subject_id' => $this->subject->id,
            'name' => 'Test Unit',
            'description' => 'A test unit for migration testing',
        ]);
    }

    /** @test */
    public function it_can_migrate_topic_with_description_only()
    {
        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Test Topic',
            'description' => 'This is a test topic description with **markdown** content.',
            'estimated_minutes' => 30,
        ]);

        $this->assertFalse($topic->migrated_to_unified);
        $this->assertNull($topic->learning_content);

        // Perform migration
        $result = $topic->migrateToUnified();

        $this->assertTrue($result);
        $topic->refresh();

        $this->assertTrue($topic->migrated_to_unified);
        $this->assertEquals('This is a test topic description with **markdown** content.', $topic->learning_content);
        $this->assertEquals(['images' => [], 'files' => []], $topic->content_assets);
    }

    /** @test */
    public function it_can_migrate_topic_with_videos()
    {
        $learningMaterials = [
            'videos' => [
                [
                    'title' => 'Khan Academy Intro',
                    'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
                [
                    'title' => 'Educational Video',
                    'url' => 'https://vimeo.com/123456789',
                ],
            ],
        ];

        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Video Topic',
            'description' => 'A topic with video content.',
            'learning_materials' => $learningMaterials,
            'estimated_minutes' => 45,
        ]);

        $result = $topic->migrateToUnified();
        $this->assertTrue($result);

        $topic->refresh();
        $expectedContent = "A topic with video content.\n\n## Video Resources\n\n[Khan Academy Intro](https://www.youtube.com/watch?v=dQw4w9WgXcQ)\n[Educational Video](https://vimeo.com/123456789)";

        $this->assertEquals($expectedContent, $topic->learning_content);
        $this->assertTrue($topic->migrated_to_unified);
    }

    /** @test */
    public function it_can_migrate_topic_with_links()
    {
        $learningMaterials = [
            'links' => [
                [
                    'title' => 'Wikipedia Article',
                    'url' => 'https://en.wikipedia.org/wiki/Test',
                    'description' => 'A comprehensive overview',
                ],
                [
                    'title' => 'Research Paper',
                    'url' => 'https://example.com/paper.pdf',
                ],
            ],
        ];

        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Links Topic',
            'description' => 'Topic with external links.',
            'learning_materials' => $learningMaterials,
            'estimated_minutes' => 30,
        ]);

        $result = $topic->migrateToUnified();
        $this->assertTrue($result);

        $topic->refresh();
        $expectedContent = "Topic with external links.\n\n## Additional Resources\n\n- [Wikipedia Article](https://en.wikipedia.org/wiki/Test) - A comprehensive overview\n- [Research Paper](https://example.com/paper.pdf)";

        $this->assertEquals($expectedContent, $topic->learning_content);
    }

    /** @test */
    public function it_can_migrate_topic_with_files()
    {
        $learningMaterials = [
            'files' => [
                [
                    'title' => 'Worksheet PDF',
                    'name' => 'worksheet.pdf',
                    'path' => '/storage/files/worksheet.pdf',
                    'size' => 1024000,
                    'type' => 'application/pdf',
                    'description' => 'Practice problems',
                ],
                [
                    'title' => 'Answer Key',
                    'name' => 'answers.pdf',
                    'path' => '/storage/files/answers.pdf',
                    'size' => 512000,
                    'type' => 'application/pdf',
                ],
            ],
        ];

        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Files Topic',
            'description' => 'Topic with downloadable files.',
            'learning_materials' => $learningMaterials,
            'estimated_minutes' => 60,
        ]);

        $result = $topic->migrateToUnified();
        $this->assertTrue($result);

        $topic->refresh();

        // Check content
        $expectedContent = "Topic with downloadable files.\n\n## Downloads\n\n- [Worksheet PDF](/storage/files/worksheet.pdf) - Practice problems\n- [Answer Key](/storage/files/answers.pdf)";
        $this->assertEquals($expectedContent, $topic->learning_content);

        // Check assets tracking
        $assets = $topic->content_assets;
        $this->assertCount(2, $assets['files']);
        $this->assertEquals('worksheet.pdf', $assets['files'][0]['filename']);
        $this->assertEquals('/storage/files/worksheet.pdf', $assets['files'][0]['path']);
        $this->assertTrue($assets['files'][0]['referenced_in_content']);
    }

    /** @test */
    public function it_can_migrate_complex_topic_with_all_types()
    {
        $learningMaterials = [
            'videos' => [
                [
                    'title' => 'Introduction Video',
                    'url' => 'https://www.youtube.com/watch?v=abc123',
                ],
            ],
            'links' => [
                [
                    'title' => 'External Resource',
                    'url' => 'https://example.com/resource',
                ],
            ],
            'files' => [
                [
                    'title' => 'Study Guide',
                    'name' => 'guide.pdf',
                    'path' => '/storage/files/guide.pdf',
                ],
            ],
        ];

        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Complex Topic',
            'description' => 'A complex topic with all material types.',
            'learning_materials' => $learningMaterials,
            'estimated_minutes' => 90,
        ]);

        $result = $topic->migrateToUnified();
        $this->assertTrue($result);

        $topic->refresh();

        $content = $topic->learning_content;
        $this->assertStringContainsString('A complex topic with all material types.', $content);
        $this->assertStringContainsString('## Video Resources', $content);
        $this->assertStringContainsString('## Additional Resources', $content);
        $this->assertStringContainsString('## Downloads', $content);
        $this->assertStringContainsString('Introduction Video', $content);
        $this->assertStringContainsString('External Resource', $content);
        $this->assertStringContainsString('Study Guide', $content);
    }

    /** @test */
    public function it_skips_empty_topics()
    {
        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Empty Topic',
            'estimated_minutes' => 30,
        ]);

        $result = $topic->migrateToUnified();
        $this->assertTrue($result); // Should succeed but not change content

        $topic->refresh();
        $this->assertTrue($topic->migrated_to_unified);
        $this->assertEquals('', $topic->learning_content);
    }

    /** @test */
    public function it_extracts_content_assets_correctly()
    {
        $embeddedImages = [
            [
                'path' => '/storage/images/diagram.png',
                'size' => 204800,
            ],
        ];

        $learningMaterials = [
            'files' => [
                [
                    'name' => 'document.pdf',
                    'path' => '/storage/files/document.pdf',
                    'size' => 1048576,
                    'type' => 'application/pdf',
                ],
            ],
        ];

        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Assets Topic',
            'description' => 'Topic with various assets.',
            'embedded_images' => $embeddedImages,
            'learning_materials' => $learningMaterials,
            'estimated_minutes' => 45,
        ]);

        $assets = $topic->extractContentAssets();

        $this->assertCount(1, $assets['images']);
        $this->assertCount(1, $assets['files']);

        $this->assertEquals('diagram.png', $assets['images'][0]['filename']);
        $this->assertEquals('/storage/images/diagram.png', $assets['images'][0]['path']);

        $this->assertEquals('document.pdf', $assets['files'][0]['filename']);
        $this->assertEquals('/storage/files/document.pdf', $assets['files'][0]['path']);
    }

    /** @test */
    public function it_provides_backward_compatibility()
    {
        // Create a topic with old format
        $originalMaterials = [
            'videos' => [
                ['title' => 'Test Video', 'url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ'],
            ],
            'links' => [
                ['title' => 'Test Link', 'url' => 'https://example.com'],
            ],
        ];

        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Compatibility Topic',
            'description' => 'Test backward compatibility.',
            'learning_materials' => $originalMaterials,
            'estimated_minutes' => 30,
        ]);

        // Before migration - should return original materials
        $legacyMaterials = $topic->getLegacyMaterials();
        $this->assertEquals($originalMaterials, $legacyMaterials);

        // After migration - should extract from unified content
        $topic->migrateToUnified();
        $topic->refresh();

        $extractedMaterials = $topic->getLegacyMaterials();
        $this->assertCount(1, $extractedMaterials['videos']);
        $this->assertCount(1, $extractedMaterials['links']);
        $this->assertEquals('Test Video', $extractedMaterials['videos'][0]['title']);
        $this->assertEquals('Test Link', $extractedMaterials['links'][0]['title']);
    }

    /** @test */
    public function it_provides_unified_content_with_fallback()
    {
        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Fallback Topic',
            'description' => 'This should work as fallback.',
            'learning_materials' => [
                'videos' => [
                    ['title' => 'Video', 'url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ'],
                ],
            ],
            'estimated_minutes' => 30,
        ]);

        // Before migration - should generate content on-the-fly
        $unifiedContent = $topic->getUnifiedContent();
        $this->assertStringContainsString('This should work as fallback.', $unifiedContent);
        $this->assertStringContainsString('## Video Resources', $unifiedContent);

        // After migration - should use stored content
        $topic->migrateToUnified();
        $topic->refresh();

        $storedContent = $topic->getUnifiedContent();
        $this->assertEquals($topic->learning_content, $storedContent);
    }

    /** @test */
    public function it_tracks_migration_status_correctly()
    {
        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Status Topic',
            'description' => 'Testing status tracking.',
            'estimated_minutes' => 30,
        ]);

        $this->assertFalse($topic->isUnified());
        $this->assertFalse($topic->migrated_to_unified);

        $topic->migrateToUnified();
        $topic->refresh();

        $this->assertTrue($topic->isUnified());
        $this->assertTrue($topic->migrated_to_unified);

        // Test scopes
        $migratedTopics = Topic::migrated()->get();
        $notMigratedTopics = Topic::notMigrated()->get();

        $this->assertCount(1, $migratedTopics);
        $this->assertCount(0, $notMigratedTopics);
    }

    /** @test */
    public function it_handles_malformed_learning_materials()
    {
        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Malformed Topic',
            'description' => 'Testing error handling.',
            'learning_materials' => [
                'videos' => [
                    ['title' => 'Incomplete Video'], // Missing URL
                    ['url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ'], // Missing title
                ],
            ],
            'estimated_minutes' => 30,
        ]);

        $result = $topic->migrateToUnified();
        $this->assertTrue($result); // Should not fail

        $topic->refresh();
        $content = $topic->learning_content;

        // Should handle missing fields gracefully
        $this->assertStringContainsString('## Video Resources', $content);
    }

    /** @test */
    public function it_updates_to_array_with_unified_fields()
    {
        $topic = Topic::create([
            'unit_id' => $this->unit->id,
            'title' => 'Array Topic',
            'description' => 'Testing toArray method.',
            'learning_materials' => [
                'videos' => [
                    ['title' => 'Test Video', 'url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ'],
                ],
            ],
            'estimated_minutes' => 30,
        ]);

        $array = $topic->toArray();

        // Should include new unified fields
        $this->assertArrayHasKey('learning_content', $array);
        $this->assertArrayHasKey('content_assets', $array);
        $this->assertArrayHasKey('migrated_to_unified', $array);
        $this->assertArrayHasKey('is_unified', $array);
        $this->assertArrayHasKey('has_content_assets', $array);

        // Should still include legacy fields for compatibility
        $this->assertArrayHasKey('learning_materials', $array);
        $this->assertArrayHasKey('has_learning_materials', $array);

        $this->assertFalse($array['is_unified']);
        $this->assertFalse($array['has_content_assets']);

        // After migration
        $topic->migrateToUnified();
        $topic->refresh();
        $array = $topic->toArray();

        $this->assertTrue($array['is_unified']);
        $this->assertNotEmpty($array['learning_content']);
    }
}

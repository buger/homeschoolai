<?php

namespace Tests\Unit\Models;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive unit tests for Topic model's unified content functionality
 *
 * Tests all unified content system methods:
 * - Migration to unified format
 * - Content conversion and asset extraction
 * - Legacy compatibility methods
 * - Content metadata handling
 * - Asset management and cleanup
 * - Video URL parsing
 * - Content validation
 */
class TopicUnifiedContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createTestTopic(array $attributes = []): Topic
    {
        $subject = Subject::factory()->create();
        $unit = Unit::factory()->create(['subject_id' => $subject->id]);

        return Topic::factory()->create(array_merge([
            'unit_id' => $unit->id,
            'title' => 'Test Topic',
            'description' => 'Test description',
            'migrated_to_unified' => false,
        ], $attributes));
    }

    public function test_migrate_to_unified_success()
    {
        $topic = $this->createTestTopic([
            'description' => 'Original description content',
            'learning_materials' => [
                'videos' => [
                    ['title' => 'Test Video', 'url' => 'https://youtube.com/watch?v=test123'],
                ],
                'links' => [
                    ['title' => 'Test Link', 'url' => 'https://example.com', 'description' => 'Helpful resource'],
                ],
                'files' => [
                    ['title' => 'Test File', 'name' => 'document.pdf', 'path' => '/uploads/document.pdf'],
                ],
            ],
        ]);

        $result = $topic->migrateToUnified();

        $this->assertTrue($result);
        $this->assertTrue($topic->migrated_to_unified);
        $this->assertNotNull($topic->learning_content);
        $this->assertNotNull($topic->content_assets);

        // Verify content structure
        $this->assertStringContainsString('Original description content', $topic->learning_content);
        $this->assertStringContainsString('## Video Resources', $topic->learning_content);
        $this->assertStringContainsString('Test Video', $topic->learning_content);
        $this->assertStringContainsString('## Additional Resources', $topic->learning_content);
        $this->assertStringContainsString('Test Link', $topic->learning_content);
        $this->assertStringContainsString('## Downloads', $topic->learning_content);
        $this->assertStringContainsString('Test File', $topic->learning_content);
    }

    public function test_migrate_to_unified_already_migrated()
    {
        $topic = $this->createTestTopic([
            'migrated_to_unified' => true,
            'learning_content' => 'Already migrated content',
        ]);

        $result = $topic->migrateToUnified();

        $this->assertTrue($result);
        $this->assertEquals('Already migrated content', $topic->learning_content);
    }

    public function test_convert_to_unified_markdown_with_videos()
    {
        $topic = $this->createTestTopic([
            'description' => 'Basic description',
            'learning_materials' => [
                'videos' => [
                    ['title' => 'YouTube Video', 'url' => 'https://www.youtube.com/watch?v=abc123'],
                    ['title' => 'Vimeo Video', 'url' => 'https://vimeo.com/123456789'],
                ],
            ],
        ]);

        $markdown = $topic->convertToUnifiedMarkdown();

        $this->assertStringContainsString('Basic description', $markdown);
        $this->assertStringContainsString('## Video Resources', $markdown);
        $this->assertStringContainsString('[YouTube Video](https://www.youtube.com/watch?v=abc123)', $markdown);
        $this->assertStringContainsString('[Vimeo Video](https://vimeo.com/123456789)', $markdown);
    }

    public function test_convert_to_unified_markdown_with_links()
    {
        $topic = $this->createTestTopic([
            'description' => 'Content with links',
            'learning_materials' => [
                'links' => [
                    ['title' => 'Educational Site', 'url' => 'https://education.com', 'description' => 'Great resource'],
                    ['title' => 'Simple Link', 'url' => 'https://simple.com'],
                ],
            ],
        ]);

        $markdown = $topic->convertToUnifiedMarkdown();

        $this->assertStringContainsString('## Additional Resources', $markdown);
        $this->assertStringContainsString('- [Educational Site](https://education.com) - Great resource', $markdown);
        $this->assertStringContainsString('- [Simple Link](https://simple.com)', $markdown);
    }

    public function test_convert_to_unified_markdown_with_files()
    {
        $topic = $this->createTestTopic([
            'description' => 'Content with files',
            'learning_materials' => [
                'files' => [
                    ['title' => 'Study Guide', 'name' => 'guide.pdf', 'path' => '/uploads/guide.pdf', 'description' => 'Comprehensive guide'],
                    ['title' => 'Worksheet', 'path' => '/uploads/worksheet.docx'],
                ],
            ],
        ]);

        $markdown = $topic->convertToUnifiedMarkdown();

        $this->assertStringContainsString('## Downloads', $markdown);
        $this->assertStringContainsString('- [Study Guide](/uploads/guide.pdf) - Comprehensive guide', $markdown);
        $this->assertStringContainsString('- [Worksheet](/uploads/worksheet.docx)', $markdown);
    }

    public function test_extract_content_assets()
    {
        $topic = $this->createTestTopic([
            'learning_materials' => [
                'files' => [
                    ['title' => 'Document', 'name' => 'doc.pdf', 'path' => '/uploads/doc.pdf', 'size' => 1024, 'type' => 'application/pdf'],
                ],
            ],
            'embedded_images' => [
                ['path' => '/images/test.jpg', 'size' => 2048],
            ],
        ]);

        $assets = $topic->extractContentAssets();

        $this->assertArrayHasKey('images', $assets);
        $this->assertArrayHasKey('files', $assets);

        // Check file assets
        $this->assertCount(1, $assets['files']);
        $fileAsset = $assets['files'][0];
        $this->assertEquals('doc.pdf', $fileAsset['filename']);
        $this->assertEquals('doc.pdf', $fileAsset['original_name']);
        $this->assertEquals('/uploads/doc.pdf', $fileAsset['path']);
        $this->assertEquals(1024, $fileAsset['size']);
        $this->assertEquals('application/pdf', $fileAsset['type']);
        $this->assertTrue($fileAsset['referenced_in_content']);

        // Check image assets
        $this->assertCount(1, $assets['images']);
        $imageAsset = $assets['images'][0];
        $this->assertEquals('test.jpg', $imageAsset['filename']);
        $this->assertEquals('/images/test.jpg', $imageAsset['path']);
        $this->assertEquals(2048, $imageAsset['size']);
        $this->assertTrue($imageAsset['referenced_in_content']);
    }

    public function test_cleanup_orphaned_assets()
    {
        $topic = $this->createTestTopic([
            'learning_content' => '# Content\n\n[Referenced File](referenced.pdf)\n\n![Referenced Image](referenced.jpg)',
            'content_assets' => [
                'files' => [
                    ['filename' => 'referenced.pdf', 'path' => 'referenced.pdf', 'referenced_in_content' => true],
                    ['filename' => 'orphaned.pdf', 'path' => 'orphaned.pdf', 'referenced_in_content' => true],
                ],
                'images' => [
                    ['filename' => 'referenced.jpg', 'path' => 'referenced.jpg', 'referenced_in_content' => true],
                    ['filename' => 'orphaned.jpg', 'path' => 'orphaned.jpg', 'referenced_in_content' => true],
                ],
            ],
        ]);

        $topic->cleanupOrphanedAssets();
        $topic->refresh();

        $assets = $topic->content_assets;

        // Referenced files should remain unchanged
        $referencedFile = collect($assets['files'])->firstWhere('filename', 'referenced.pdf');
        $this->assertTrue($referencedFile['referenced_in_content']);

        // Orphaned files should be marked
        $orphanedFile = collect($assets['files'])->firstWhere('filename', 'orphaned.pdf');
        $this->assertFalse($orphanedFile['referenced_in_content']);
        $this->assertTrue($orphanedFile['orphaned']);

        // Same for images
        $referencedImage = collect($assets['images'])->firstWhere('filename', 'referenced.jpg');
        $this->assertTrue($referencedImage['referenced_in_content']);

        $orphanedImage = collect($assets['images'])->firstWhere('filename', 'orphaned.jpg');
        $this->assertFalse($orphanedImage['referenced_in_content']);
        $this->assertTrue($orphanedImage['orphaned']);
    }

    public function test_get_unified_content_migrated()
    {
        $topic = $this->createTestTopic([
            'migrated_to_unified' => true,
            'learning_content' => '# Unified Content\n\nThis is unified content.',
            'description' => 'Legacy description',
        ]);

        $content = $topic->getUnifiedContent();

        $this->assertEquals('# Unified Content\n\nThis is unified content.', $content);
    }

    public function test_get_unified_content_fallback()
    {
        $topic = $this->createTestTopic([
            'migrated_to_unified' => false,
            'description' => 'Legacy description',
            'learning_materials' => [
                'videos' => [
                    ['title' => 'Legacy Video', 'url' => 'https://youtube.com/watch?v=legacy'],
                ],
            ],
        ]);

        $content = $topic->getUnifiedContent();

        // Should convert on-the-fly
        $this->assertStringContainsString('Legacy description', $content);
        $this->assertStringContainsString('## Video Resources', $content);
        $this->assertStringContainsString('Legacy Video', $content);
    }

    public function test_get_legacy_materials_not_migrated()
    {
        $originalMaterials = [
            'videos' => [['title' => 'Test Video', 'url' => 'https://youtube.com/test']],
            'links' => [['title' => 'Test Link', 'url' => 'https://example.com']],
        ];

        $topic = $this->createTestTopic([
            'migrated_to_unified' => false,
            'learning_materials' => $originalMaterials,
        ]);

        $materials = $topic->getLegacyMaterials();

        $this->assertEquals($originalMaterials, $materials);
    }

    public function test_extract_legacy_materials_from_unified()
    {
        $topic = $this->createTestTopic([
            'migrated_to_unified' => true,
            'learning_content' => "# Content\n\n[YouTube Video](https://www.youtube.com/watch?v=test123)\n\n[Educational Link](https://example.com)\n\n[PDF Document](document.pdf)",
        ]);

        $materials = $topic->getLegacyMaterials();

        $this->assertArrayHasKey('videos', $materials);
        $this->assertArrayHasKey('links', $materials);
        $this->assertArrayHasKey('files', $materials);

        // Check video extraction
        $this->assertCount(1, $materials['videos']);
        $this->assertEquals('YouTube Video', $materials['videos'][0]['title']);
        $this->assertEquals('https://www.youtube.com/watch?v=test123', $materials['videos'][0]['url']);
        $this->assertEquals('youtube', $materials['videos'][0]['type']);

        // Check link extraction
        $this->assertCount(1, $materials['links']);
        $this->assertEquals('Educational Link', $materials['links'][0]['title']);
        $this->assertEquals('https://example.com', $materials['links'][0]['url']);

        // Check file extraction
        $this->assertCount(1, $materials['files']);
        $this->assertEquals('PDF Document', $materials['files'][0]['title']);
        $this->assertEquals('document.pdf', $materials['files'][0]['path']);
    }

    public function test_is_unified()
    {
        $unifiedTopic = $this->createTestTopic(['migrated_to_unified' => true]);
        $legacyTopic = $this->createTestTopic(['migrated_to_unified' => false]);

        $this->assertTrue($unifiedTopic->isUnified());
        $this->assertFalse($legacyTopic->isUnified());
    }

    public function test_get_content_assets_with_defaults()
    {
        $topic = $this->createTestTopic(['content_assets' => null]);

        $assets = $topic->getContentAssets();

        $this->assertArrayHasKey('images', $assets);
        $this->assertArrayHasKey('files', $assets);
        $this->assertEquals([], $assets['images']);
        $this->assertEquals([], $assets['files']);
    }

    public function test_has_content_assets()
    {
        $topicWithAssets = $this->createTestTopic([
            'content_assets' => [
                'images' => [['filename' => 'test.jpg']],
                'files' => [],
            ],
        ]);

        $topicWithoutAssets = $this->createTestTopic([
            'content_assets' => [
                'images' => [],
                'files' => [],
            ],
        ]);

        $this->assertTrue($topicWithAssets->hasContentAssets());
        $this->assertFalse($topicWithoutAssets->hasContentAssets());
    }

    public function test_scope_not_migrated()
    {
        $this->createTestTopic(['migrated_to_unified' => true]);
        $this->createTestTopic(['migrated_to_unified' => false]);
        $this->createTestTopic(['migrated_to_unified' => false]);

        $notMigrated = Topic::notMigrated()->get();

        $this->assertCount(2, $notMigrated);
        foreach ($notMigrated as $topic) {
            $this->assertFalse($topic->migrated_to_unified);
        }
    }

    public function test_scope_migrated()
    {
        $this->createTestTopic(['migrated_to_unified' => true]);
        $this->createTestTopic(['migrated_to_unified' => true]);
        $this->createTestTopic(['migrated_to_unified' => false]);

        $migrated = Topic::migrated()->get();

        $this->assertCount(2, $migrated);
        foreach ($migrated as $topic) {
            $this->assertTrue($topic->migrated_to_unified);
        }
    }

    public function test_parse_video_url_youtube_variations()
    {
        $youtubeTests = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
        ];

        foreach ($youtubeTests as $url => $expectedId) {
            $result = Topic::parseVideoUrl($url);

            $this->assertNotNull($result);
            $this->assertEquals('youtube', $result['type']);
            $this->assertEquals($expectedId, $result['id']);
            $this->assertStringContainsString($expectedId, $result['thumbnail']);
        }
    }

    public function test_parse_video_url_vimeo()
    {
        $vimeoTests = [
            'https://vimeo.com/123456789' => '123456789',
            'https://vimeo.com/video/123456789' => '123456789',
        ];

        foreach ($vimeoTests as $url => $expectedId) {
            $result = Topic::parseVideoUrl($url);

            $this->assertNotNull($result);
            $this->assertEquals('vimeo', $result['type']);
            $this->assertEquals($expectedId, $result['id']);
            $this->assertNull($result['thumbnail']); // Vimeo requires API call
        }
    }

    public function test_parse_video_url_khan_academy()
    {
        $url = 'https://www.khanacademy.org/science/physics/forces-newtons-laws/newtons-laws-of-motion/a/what-is-newtons-first-law';
        $result = Topic::parseVideoUrl($url);

        $this->assertNotNull($result);
        $this->assertEquals('khan_academy', $result['type']);
        $this->assertNotNull($result['id']);
        $this->assertNull($result['thumbnail']);
    }

    public function test_parse_video_url_invalid()
    {
        $invalidUrls = [
            'https://example.com/video',
            'not-a-url',
            'https://invalidplatform.com/video/123',
        ];

        foreach ($invalidUrls as $url) {
            $result = Topic::parseVideoUrl($url);
            $this->assertNull($result);
        }
    }

    public function test_validate_estimated_minutes()
    {
        $this->assertTrue(Topic::validateEstimatedMinutes(30));
        $this->assertTrue(Topic::validateEstimatedMinutes(1));
        $this->assertTrue(Topic::validateEstimatedMinutes(480)); // 8 hours

        $this->assertFalse(Topic::validateEstimatedMinutes(0));
        $this->assertFalse(Topic::validateEstimatedMinutes(-5));
        $this->assertFalse(Topic::validateEstimatedMinutes(500)); // Over 8 hours
    }

    public function test_has_rich_content()
    {
        $richTopic = $this->createTestTopic([
            'content_format' => 'markdown',
            'description' => '# Rich content with markdown',
        ]);

        $plainTopic = $this->createTestTopic([
            'content_format' => 'plain',
            'description' => 'Plain text content',
        ]);

        $emptyTopic = $this->createTestTopic([
            'content_format' => 'markdown',
            'description' => null,
        ]);

        $this->assertTrue($richTopic->hasRichContent());
        $this->assertFalse($plainTopic->hasRichContent());
        $this->assertFalse($emptyTopic->hasRichContent());
    }

    public function test_get_content_metadata_with_defaults()
    {
        $topic = $this->createTestTopic([
            'content_metadata' => null,
            'content_format' => 'markdown',
        ]);

        $metadata = $topic->getContentMetadata();

        $this->assertArrayHasKey('word_count', $metadata);
        $this->assertArrayHasKey('reading_time', $metadata);
        $this->assertArrayHasKey('character_count', $metadata);
        $this->assertArrayHasKey('format', $metadata);
        $this->assertEquals('markdown', $metadata['format']);
        $this->assertEquals(0, $metadata['word_count']);
    }

    public function test_get_embedded_images()
    {
        $topic = $this->createTestTopic([
            'embedded_images' => [
                ['path' => '/images/test1.jpg', 'alt' => 'Test 1'],
                ['path' => '/images/test2.png', 'alt' => 'Test 2'],
            ],
        ]);

        $images = $topic->getEmbeddedImages();

        $this->assertCount(2, $images);
        $this->assertEquals('/images/test1.jpg', $images[0]['path']);
        $this->assertEquals('Test 1', $images[0]['alt']);
    }

    public function test_has_embedded_images()
    {
        $topicWithImages = $this->createTestTopic([
            'embedded_images' => [['path' => '/images/test.jpg']],
        ]);

        $topicWithoutImages = $this->createTestTopic([
            'embedded_images' => [],
        ]);

        $this->assertTrue($topicWithImages->hasEmbeddedImages());
        $this->assertFalse($topicWithoutImages->hasEmbeddedImages());
    }

    public function test_get_reading_time()
    {
        $topic1 = $this->createTestTopic([
            'content_metadata' => ['reading_time' => 0],
        ]);

        $topic2 = $this->createTestTopic([
            'content_metadata' => ['reading_time' => 1],
        ]);

        $topic3 = $this->createTestTopic([
            'content_metadata' => ['reading_time' => 5],
        ]);

        $this->assertEquals('Less than 1 minute', $topic1->getReadingTime());
        $this->assertEquals('1 minute', $topic2->getReadingTime());
        $this->assertEquals('5 minutes', $topic3->getReadingTime());
    }

    public function test_get_word_count()
    {
        $topic = $this->createTestTopic([
            'content_metadata' => ['word_count' => 250],
        ]);

        $this->assertEquals(250, $topic->getWordCount());
    }

    public function test_update_content_metadata()
    {
        $topic = $this->createTestTopic([
            'content_metadata' => ['word_count' => 100, 'reading_time' => 1],
        ]);

        $result = $topic->updateContentMetadata(['word_count' => 200, 'format' => 'markdown']);

        $this->assertTrue($result);
        $this->assertEquals(200, $topic->content_metadata['word_count']);
        $this->assertEquals(1, $topic->content_metadata['reading_time']); // Should preserve existing
        $this->assertEquals('markdown', $topic->content_metadata['format']); // Should add new
    }

    public function test_to_array_includes_unified_content_data()
    {
        $topic = $this->createTestTopic([
            'migrated_to_unified' => true,
            'learning_content' => '# Unified content',
            'content_assets' => ['images' => [], 'files' => []],
            'content_metadata' => ['word_count' => 10, 'reading_time' => 1],
            'estimated_minutes' => 45,
        ]);

        $array = $topic->toArray();

        $this->assertArrayHasKey('learning_content', $array);
        $this->assertArrayHasKey('content_assets', $array);
        $this->assertArrayHasKey('migrated_to_unified', $array);
        $this->assertArrayHasKey('is_unified', $array);
        $this->assertArrayHasKey('has_content_assets', $array);
        $this->assertArrayHasKey('has_rich_content', $array);
        $this->assertArrayHasKey('reading_time', $array);
        $this->assertArrayHasKey('word_count', $array);
        $this->assertArrayHasKey('estimated_duration', $array);

        $this->assertEquals('# Unified content', $array['learning_content']);
        $this->assertTrue($array['is_unified']);
        $this->assertEquals('1 minute', $array['reading_time']);
        $this->assertEquals(10, $array['word_count']);
        $this->assertEquals('45 min', $array['estimated_duration']);
    }

    public function test_get_estimated_duration_formatting()
    {
        $testCases = [
            ['minutes' => 30, 'expected' => '30 min'],
            ['minutes' => 60, 'expected' => '1h'],
            ['minutes' => 90, 'expected' => '1h 30m'],
            ['minutes' => 120, 'expected' => '2h'],
            ['minutes' => 150, 'expected' => '2h 30m'],
        ];

        foreach ($testCases as $testCase) {
            $topic = $this->createTestTopic(['estimated_minutes' => $testCase['minutes']]);
            $this->assertEquals($testCase['expected'], $topic->getEstimatedDuration());
        }
    }

    public function test_migration_handles_empty_materials()
    {
        $topic = $this->createTestTopic([
            'description' => 'Simple description',
            'learning_materials' => null,
        ]);

        $result = $topic->migrateToUnified();

        $this->assertTrue($result);
        $this->assertEquals('Simple description', $topic->learning_content);
        $this->assertEquals(['images' => [], 'files' => []], $topic->content_assets);
    }

    public function test_migration_handles_partial_materials()
    {
        $topic = $this->createTestTopic([
            'description' => 'Content with only videos',
            'learning_materials' => [
                'videos' => [
                    ['title' => 'Only Video', 'url' => 'https://youtube.com/watch?v=only'],
                ],
                // No links or files
            ],
        ]);

        $result = $topic->migrateToUnified();

        $this->assertTrue($result);
        $this->assertStringContainsString('Content with only videos', $topic->learning_content);
        $this->assertStringContainsString('## Video Resources', $topic->learning_content);
        $this->assertStringContainsString('Only Video', $topic->learning_content);
        $this->assertStringNotContainsString('## Additional Resources', $topic->learning_content);
        $this->assertStringNotContainsString('## Downloads', $topic->learning_content);
    }
}

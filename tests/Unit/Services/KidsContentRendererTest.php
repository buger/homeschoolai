<?php

namespace Tests\Unit\Services;

use App\Models\Child;
use App\Models\Topic;
use App\Services\KidsContentRenderer;
use App\Services\RichContentService;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Comprehensive unit tests for KidsContentRenderer
 *
 * Tests the kids-specific content rendering functionality:
 * - Age-appropriate content rendering
 * - Independence level features
 * - Interactive element generation
 * - Safety features and filtering
 * - Gamification data generation
 * - Media enhancement for kids
 */
class KidsContentRendererTest extends TestCase
{
    use RefreshDatabase;

    protected KidsContentRenderer $renderer;

    protected $mockRichContentService;

    protected $mockSecurityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRichContentService = Mockery::mock(RichContentService::class);
        $this->mockSecurityService = Mockery::mock(SecurityService::class);

        $this->renderer = new KidsContentRenderer(
            $this->mockRichContentService,
            $this->mockSecurityService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createTestChild(array $attributes = []): Child
    {
        return Child::factory()->create(array_merge([
            'name' => 'Test Child',
            'grade' => '5th',
            'independence_level' => 2,
        ], $attributes));
    }

    protected function createTestTopic(array $attributes = []): Topic
    {
        return Topic::factory()->create(array_merge([
            'title' => 'Test Topic',
            'description' => 'Test description',
            'learning_content' => '# Test Content',
        ], $attributes));
    }

    public function test_render_for_kids_preschool_age_group()
    {
        $child = $this->createTestChild([
            'grade' => 'PreK',
            'independence_level' => 1,
        ]);
        $topic = $this->createTestTopic();
        $content = "# Fun Learning Time\n\nLet's explore colors together!";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->with($content)
            ->andReturn([
                'html' => '<h1>Fun Learning Time</h1><p>Let\'s explore colors together!</p>',
                'metadata' => ['word_count' => 5, 'reading_time' => 1],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('gamification', $result);
        $this->assertArrayHasKey('safety_level', $result);
        $this->assertArrayHasKey('reading_level', $result);

        // Verify preschool-specific styling
        $this->assertStringContainsString('data-age-group="preschool"', $result['html']);
        $this->assertStringContainsString('data-independence="1"', $result['html']);
        $this->assertStringContainsString('kids-content-container', $result['html']);
    }

    public function test_render_for_kids_elementary_age_group()
    {
        $child = $this->createTestChild([
            'grade' => '3rd',
            'independence_level' => 2,
        ]);
        $topic = $this->createTestTopic();
        $content = "# Science Adventure\n\nToday we'll learn about animals.";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<h1>Science Adventure</h1><p>Today we\'ll learn about animals.</p>',
                'metadata' => ['word_count' => 7, 'reading_time' => 1],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('data-age-group="elementary"', $result['html']);
        $this->assertStringContainsString('data-independence="2"', $result['html']);
        $this->assertEquals('elementary', $result['reading_level']);
    }

    public function test_render_for_kids_middle_school_age_group()
    {
        $child = $this->createTestChild([
            'grade' => '7th',
            'independence_level' => 3,
        ]);
        $topic = $this->createTestTopic();
        $content = "# Advanced Science\n\nExploring cellular biology concepts.";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<h1>Advanced Science</h1><p>Exploring cellular biology concepts.</p>',
                'metadata' => ['word_count' => 6, 'reading_time' => 1],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('data-age-group="middle"', $result['html']);
        $this->assertStringContainsString('data-independence="3"', $result['html']);
        $this->assertEquals('middle', $result['reading_level']);
    }

    public function test_render_for_kids_high_school_age_group()
    {
        $child = $this->createTestChild([
            'grade' => '11th',
            'independence_level' => 4,
        ]);
        $topic = $this->createTestTopic();
        $content = "# Physics Research\n\nQuantum mechanics principles.";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<h1>Physics Research</h1><p>Quantum mechanics principles.</p>',
                'metadata' => ['word_count' => 4, 'reading_time' => 1],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('data-age-group="high"', $result['html']);
        $this->assertStringContainsString('data-independence="4"', $result['html']);
        $this->assertEquals('high', $result['reading_level']);
    }

    public function test_age_appropriate_styling_preschool()
    {
        $child = $this->createTestChild(['grade' => 'K', 'independence_level' => 1]);
        $topic = $this->createTestTopic();
        $content = "# Heading\n\n- Item 1\n- Item 2";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<h1>Heading</h1><ul><li>Item 1</li><li>Item 2</li></ul>',
                'metadata' => ['word_count' => 4],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        // Check for preschool-specific styling
        $this->assertStringContainsString('kids-heading-xl kids-colorful animate-bounce-gentle', $result['html']);
        $this->assertStringContainsString('🌟', $result['html']); // Preschool icons
        $this->assertStringContainsString('kids-list-fun', $result['html']);
        $this->assertStringContainsString('📌', $result['html']); // List item icons
    }

    public function test_age_appropriate_styling_elementary()
    {
        $child = $this->createTestChild(['grade' => '2nd', 'independence_level' => 2]);
        $topic = $this->createTestTopic();
        $content = "# Study Guide\n\n- Learn about plants\n- Complete worksheet";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<h1>Study Guide</h1><ul><li>Learn about plants</li><li>Complete worksheet</li></ul>',
                'metadata' => ['word_count' => 7],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        // Check for elementary-specific styling
        $this->assertStringContainsString('kids-heading-xl kids-educational', $result['html']);
        $this->assertStringContainsString('📚', $result['html']); // Elementary icons
        $this->assertStringContainsString('kids-list-organized', $result['html']);
        $this->assertStringContainsString('kids-checkable', $result['html']);
    }

    public function test_interactive_features_by_independence_level()
    {
        $testCases = [
            ['level' => 1, 'features' => ['read-aloud', 'highlighting', 'emoji-reactions']],
            ['level' => 2, 'features' => ['read-aloud', 'highlighting', 'checkboxes', 'progress-tracking']],
            ['level' => 3, 'features' => ['read-aloud', 'highlighting', 'checkboxes', 'note-taking', 'bookmarks']],
            ['level' => 4, 'features' => ['read-aloud', 'highlighting', 'checkboxes', 'note-taking', 'content-creation']],
        ];

        foreach ($testCases as $testCase) {
            $child = $this->createTestChild(['independence_level' => $testCase['level']]);
            $topic = $this->createTestTopic();
            $content = "# Interactive Content\n\nContent for testing features.";

            $this->mockRichContentService->shouldReceive('processUnifiedContent')
                ->once()
                ->andReturn([
                    'html' => '<h1>Interactive Content</h1><p>Content for testing features.</p>',
                    'metadata' => ['word_count' => 5],
                ]);

            $result = $this->renderer->renderForKids($content, $child, $topic);

            // Verify expected features are present in metadata
            $expectedFeatures = $testCase['features'];
            $actualFeatures = $result['metadata']['independence_features'];

            foreach ($expectedFeatures as $feature) {
                $this->assertContains($feature, $actualFeatures,
                    "Feature '{$feature}' should be available for independence level {$testCase['level']}");
            }
        }
    }

    public function test_read_aloud_feature_added()
    {
        $child = $this->createTestChild(['independence_level' => 1]);
        $topic = $this->createTestTopic();
        $content = "# Content\n\nThis is some text to read.";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p class="kids-text">This is some text to read.</p>',
                'metadata' => ['word_count' => 7],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-read-aloud-btn', $result['html']);
        $this->assertStringContainsString('🔊', $result['html']);
        $this->assertStringContainsString('Read aloud', $result['html']);
    }

    public function test_checkboxes_feature_independence_level_2()
    {
        $child = $this->createTestChild(['independence_level' => 2]);
        $topic = $this->createTestTopic();
        $content = "# Tasks\n\n- Complete assignment\n- Review notes";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<ul><li class="kids-list-item kids-checkable">Complete assignment</li><li class="kids-list-item kids-checkable">Review notes</li></ul>',
                'metadata' => ['word_count' => 4],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-checkbox-container', $result['html']);
        $this->assertStringContainsString('kids-checkbox', $result['html']);
        $this->assertStringContainsString('data-persist="true"', $result['html']);
    }

    public function test_note_taking_feature_independence_level_3()
    {
        $child = $this->createTestChild(['independence_level' => 3]);
        $topic = $this->createTestTopic();
        $content = "# Learning\n\nImportant concepts to remember.";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p class="kids-text">Important concepts to remember.</p>',
                'metadata' => ['word_count' => 5],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-add-note-btn', $result['html']);
        $this->assertStringContainsString('📝 Add Note', $result['html']);
        $this->assertStringContainsString('kids-paragraph-container', $result['html']);
    }

    public function test_bookmark_feature_independence_level_3()
    {
        $child = $this->createTestChild(['independence_level' => 3]);
        $topic = $this->createTestTopic();
        $content = "# Important Section\n\nKey information here.";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<h1 class="kids-heading">Important Section</h1><p>Key information here.</p>',
                'metadata' => ['word_count' => 4],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-bookmark-btn', $result['html']);
        $this->assertStringContainsString('🔖', $result['html']);
        $this->assertStringContainsString('data-bookmark-section="true"', $result['html']);
    }

    public function test_video_enhancement_for_kids()
    {
        $child = $this->createTestChild(['independence_level' => 2]);
        $topic = $this->createTestTopic();
        $content = "# Video Lesson\n\n[Educational Video](https://youtube.com/watch?v=test)";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<div class="video-embed-container"><iframe src="https://youtube.com/embed/test"></iframe></div>',
                'metadata' => ['word_count' => 3, 'has_videos' => true],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-video-enhancements', $result['html']);
        $this->assertStringContainsString('kids-video-progress-container', $result['html']);
        $this->assertStringContainsString('📺 Ready to watch!', $result['html']);
        $this->assertStringContainsString('👥 Ask a grown-up to watch with you!', $result['html']);
        $this->assertStringContainsString('😊 Reactions', $result['html']);
    }

    public function test_video_safety_notice_for_young_children()
    {
        $child = $this->createTestChild(['independence_level' => 1]);
        $topic = $this->createTestTopic();
        $content = 'Video content';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<div class="video-embed-container">video content</div>',
                'metadata' => ['word_count' => 2],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-video-safety-notice', $result['html']);
        $this->assertStringContainsString('Ask a grown-up to watch with you!', $result['html']);
    }

    public function test_image_enhancement_for_kids()
    {
        $child = $this->createTestChild(['independence_level' => 2]);
        $topic = $this->createTestTopic();
        $content = 'Image content';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<div class="file-embed image-embed"><img src="test.jpg" alt="Test"></div>',
                'metadata' => ['word_count' => 2],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-image-enhancements', $result['html']);
        $this->assertStringContainsString('kids-zoom-btn', $result['html']);
        $this->assertStringContainsString('🔍 Zoom', $result['html']);
        $this->assertStringContainsString('⛶ Full Screen', $result['html']);
        $this->assertStringContainsString('✏️ Draw', $result['html']); // Independence level 2+ feature
    }

    public function test_file_enhancement_for_kids()
    {
        $child = $this->createTestChild(['independence_level' => 1]);
        $topic = $this->createTestTopic();
        $content = 'File content';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<div class="file-embed"><a href="document.pdf">Document</a></div>',
                'metadata' => ['word_count' => 2],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-file-enhancements', $result['html']);
        $this->assertStringContainsString('kids-download-feedback', $result['html']);
        $this->assertStringContainsString('📥', $result['html']);
        $this->assertStringContainsString('Getting your file ready...', $result['html']);
        $this->assertStringContainsString('🛡️ Downloaded files are safe for you to use!', $result['html']);
    }

    public function test_progress_tracking_elements()
    {
        $child = $this->createTestChild(['independence_level' => 2]);
        $topic = $this->createTestTopic(['id' => 123]);
        $content = 'Content with progress tracking';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p>Content with progress tracking</p>',
                'metadata' => ['word_count' => 4],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-progress-tracking', $result['html']);
        $this->assertStringContainsString('data-topic-id="123"', $result['html']);
        $this->assertStringContainsString('data-child-id="'.$child->id.'"', $result['html']);
        $this->assertStringContainsString('📊 Your Reading Progress', $result['html']);
        $this->assertStringContainsString('🏆 Your Achievements', $result['html']);
    }

    public function test_time_tracking_for_older_kids()
    {
        $child = $this->createTestChild(['independence_level' => 3]);
        $topic = $this->createTestTopic();
        $content = 'Time tracked content';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p>Time tracked content</p>',
                'metadata' => ['word_count' => 3],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertStringContainsString('kids-time-tracking', $result['html']);
        $this->assertStringContainsString('⏰ Learning Time', $result['html']);
        $this->assertStringContainsString('data-timer="true"', $result['html']);
        $this->assertStringContainsString('⏸️ Pause', $result['html']);
    }

    public function test_gamification_data_generation()
    {
        $child = $this->createTestChild(['independence_level' => 2]);
        $topic = $this->createTestTopic();
        $content = str_repeat('word ', 200); // 200 words for testing

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p>'.str_repeat('word ', 200).'</p>',
                'metadata' => ['word_count' => 200],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $gamification = $result['gamification'];
        $this->assertArrayHasKey('points_available', $gamification);
        $this->assertArrayHasKey('estimated_time', $gamification);
        $this->assertArrayHasKey('difficulty_level', $gamification);
        $this->assertArrayHasKey('achievements_possible', $gamification);
        $this->assertArrayHasKey('completion_rewards', $gamification);
        $this->assertArrayHasKey('encouragement_messages', $gamification);

        // 200 words should give points between 10-100
        $this->assertGreaterThanOrEqual(10, $gamification['points_available']);
        $this->assertLessThanOrEqual(100, $gamification['points_available']);

        // Should calculate reading time
        $this->assertEquals(1, $gamification['estimated_time']); // 200 words = 1 minute
    }

    public function test_difficulty_calculation()
    {
        $child = $this->createTestChild();
        $topic = $this->createTestTopic();

        $testCases = [
            ['words' => 50, 'expected' => 'easy'],
            ['words' => 300, 'expected' => 'medium'],
            ['words' => 600, 'expected' => 'hard'],
        ];

        foreach ($testCases as $testCase) {
            $content = str_repeat('word ', $testCase['words']);

            $this->mockRichContentService->shouldReceive('processUnifiedContent')
                ->once()
                ->andReturn([
                    'html' => '<p>'.$content.'</p>',
                    'metadata' => ['word_count' => $testCase['words']],
                ]);

            $result = $this->renderer->renderForKids($content, $child, $topic);

            $this->assertEquals(
                $testCase['expected'],
                $result['gamification']['difficulty_level'],
                "Content with {$testCase['words']} words should be {$testCase['expected']}"
            );
        }
    }

    public function test_encouragement_messages_by_age_group()
    {
        $ageGroups = [
            ['grade' => 'PreK', 'group' => 'preschool'],
            ['grade' => '3rd', 'group' => 'elementary'],
            ['grade' => '7th', 'group' => 'middle'],
            ['grade' => '11th', 'group' => 'high'],
        ];

        foreach ($ageGroups as $ageGroup) {
            $child = $this->createTestChild(['grade' => $ageGroup['grade']]);
            $topic = $this->createTestTopic();
            $content = 'Test content';

            $this->mockRichContentService->shouldReceive('processUnifiedContent')
                ->once()
                ->andReturn([
                    'html' => '<p>Test content</p>',
                    'metadata' => ['word_count' => 2],
                ]);

            $result = $this->renderer->renderForKids($content, $child, $topic);

            $messages = $result['gamification']['encouragement_messages'];
            $this->assertArrayHasKey('start', $messages);
            $this->assertArrayHasKey('middle', $messages);
            $this->assertArrayHasKey('end', $messages);

            // Each age group should have different message tones
            if ($ageGroup['group'] === 'preschool') {
                $this->assertStringContainsString('🌟', $messages['start']);
                $this->assertStringContainsString('amazing', $messages['end']);
            } elseif ($ageGroup['group'] === 'high') {
                $this->assertStringContainsString('🎓', $messages['start']);
                $this->assertStringContainsString('mastery', $messages['end']);
            }
        }
    }

    public function test_safety_level_calculation()
    {
        $child = $this->createTestChild();
        $topic = $this->createTestTopic();
        $content = 'Safe educational content';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p>Safe educational content</p>',
                'metadata' => ['word_count' => 3],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $this->assertEquals('safe', $result['safety_level']);
    }

    public function test_accessibility_features()
    {
        $child = $this->createTestChild(['independence_level' => 2]);
        $topic = $this->createTestTopic();
        $content = 'Accessible content';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p>Accessible content</p>',
                'metadata' => ['word_count' => 2],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $accessibility = $result['metadata']['accessibility_features'];
        $this->assertTrue($accessibility['high_contrast_mode']);
        $this->assertTrue($accessibility['large_text_mode']);
        $this->assertTrue($accessibility['screen_reader_support']);
        $this->assertTrue($accessibility['keyboard_navigation']);
        $this->assertTrue($accessibility['reduced_motion_support']);
    }

    public function test_parental_controls()
    {
        $youngChild = $this->createTestChild(['independence_level' => 1]);
        $olderChild = $this->createTestChild(['independence_level' => 4]);
        $topic = $this->createTestTopic();
        $content = 'Test content';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->twice()
            ->andReturn([
                'html' => '<p>Test content</p>',
                'metadata' => ['word_count' => 2],
            ]);

        // Test young child
        $youngResult = $this->renderer->renderForKids($content, $youngChild, $topic);
        $youngControls = $youngResult['metadata']['parental_controls'];
        $this->assertTrue($youngControls['time_limits_enabled']);
        $this->assertTrue($youngControls['content_filtering_active']);

        // Test older child
        $olderResult = $this->renderer->renderForKids($content, $olderChild, $topic);
        $olderControls = $olderResult['metadata']['parental_controls'];
        $this->assertFalse($olderControls['time_limits_enabled']);
        $this->assertTrue($olderControls['content_filtering_active']); // Always active
    }

    public function test_error_handling_fallback()
    {
        $child = $this->createTestChild();
        $topic = $this->createTestTopic();
        $content = 'Content that causes error';

        // Mock an exception in the rich content service
        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andThrow(new \Exception('Processing error'));

        $result = $this->renderer->renderForKids($content, $child, $topic);

        // Should return fallback content
        $this->assertStringContainsString('kids-content-fallback', $result['html']);
        $this->assertStringContainsString('🎨', $result['html']);
        $this->assertEquals(['error' => 'fallback_mode'], $result['metadata']);
        $this->assertEquals('safe', $result['safety_level']);
        $this->assertEquals(50, $result['engagement_score']);
    }

    public function test_engagement_score_calculation()
    {
        $child = $this->createTestChild();
        $topic = $this->createTestTopic();

        // Test with rich content
        $richContent = str_repeat('word ', 300)."\n\n![Image](test.jpg)\n\n[Video](https://youtube.com/watch?v=test)";

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p>'.str_repeat('word ', 300).'</p><img src="test.jpg"><iframe src="youtube.com"></iframe>',
                'metadata' => ['word_count' => 300],
            ]);

        $result = $this->renderer->renderForKids($richContent, $child, $topic);

        // Should have high engagement score due to:
        // - 300+ words (+20)
        // - Image (+15)
        // - Video (+20)
        // - Base score (50)
        // - Preschool bonus (if applicable)
        $this->assertGreaterThan(50, $result['engagement_score']);
    }

    public function test_interactive_elements_extraction()
    {
        $child = $this->createTestChild(['independence_level' => 3]);
        $topic = $this->createTestTopic();
        $content = 'Content with interactive elements';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<div class="kids-content"><input class="kids-checkbox"><span class="kids-highlightable"><button class="kids-bookmark-btn"><button class="kids-add-note-btn"></div>',
                'metadata' => ['word_count' => 5],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        $elements = $result['interactive_elements'];
        $this->assertContains('checkboxes', $elements);
        $this->assertContains('highlighting', $elements);
        $this->assertContains('bookmarks', $elements);
        $this->assertContains('notes', $elements);
    }

    public function test_kids_styles_and_scripts_generation()
    {
        $child = $this->createTestChild(['grade' => '3rd', 'independence_level' => 2]);
        $topic = $this->createTestTopic();
        $content = 'Test content';

        $this->mockRichContentService->shouldReceive('processUnifiedContent')
            ->once()
            ->andReturn([
                'html' => '<p>Test content</p>',
                'metadata' => ['word_count' => 2],
            ]);

        $result = $this->renderer->renderForKids($content, $child, $topic);

        // Should include age-group specific CSS and JS
        $this->assertStringContainsString('/css/kids-content-elementary.css', $result['html']);
        $this->assertStringContainsString('/js/kids-interactions-2.js', $result['html']);
        $this->assertStringContainsString('window.kidsConfig', $result['html']);
        $this->assertStringContainsString('ageGroup: "elementary"', $result['html']);
        $this->assertStringContainsString('independenceLevel: 2', $result['html']);
    }
}

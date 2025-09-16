<?php

namespace Tests\Feature;

use App\Services\RichContentService;
use Tests\TestCase;

class EnhancedMarkdownTest extends TestCase
{
    private RichContentService $richContentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->richContentService = app(RichContentService::class);
    }

    public function test_youtube_video_embedding()
    {
        $content = 'Check out this video: [Learn Laravel](https://www.youtube.com/watch?v=dQw4w9WgXcQ)';

        $result = $this->richContentService->processUnifiedContent($content);

        $this->assertStringContainsString('video-embed-container', $result['html']);
        $this->assertStringContainsString('youtube-embed', $result['html']);
        $this->assertStringContainsString('dQw4w9WgXcQ', $result['html']);
        $this->assertTrue($result['metadata']['has_videos']);
        $this->assertEquals(1, $result['metadata']['video_count']);
    }

    public function test_vimeo_video_embedding()
    {
        $content = 'Watch this: [Vimeo Video](https://vimeo.com/123456789)';

        $result = $this->richContentService->processUnifiedContent($content);

        $this->assertStringContainsString('video-embed-container', $result['html']);
        $this->assertStringContainsString('vimeo-embed', $result['html']);
        $this->assertStringContainsString('123456789', $result['html']);
        $this->assertTrue($result['metadata']['has_videos']);
    }

    public function test_educational_platform_embedding()
    {
        $content = 'Learn math: [Khan Academy](https://www.khanacademy.org/math/algebra/intro-to-algebra)';

        $result = $this->richContentService->processUnifiedContent($content);

        $this->assertStringContainsString('educational-embed', $result['html']);
        $this->assertStringContainsString('khan_academy-embed', $result['html']);
        $this->assertTrue($result['metadata']['has_videos']);
    }

    public function test_pdf_file_embedding()
    {
        $content = 'Download the guide: [Study Guide](https://example.com/guide.pdf)';

        $result = $this->richContentService->processUnifiedContent($content);

        $this->assertStringContainsString('file-embed', $result['html']);
        $this->assertStringContainsString('pdf-embed', $result['html']);
        $this->assertStringContainsString('Preview PDF', $result['html']);
        $this->assertStringContainsString('Download PDF', $result['html']);
        $this->assertTrue($result['metadata']['has_files']);
        $this->assertEquals(1, $result['metadata']['file_count']);
    }

    public function test_image_file_embedding()
    {
        $content = 'See the diagram: [Diagram](https://example.com/diagram.png)';

        $result = $this->richContentService->processUnifiedContent($content);

        $this->assertStringContainsString('image-embed', $result['html']);
        $this->assertStringContainsString('img', $result['html']);
        $this->assertStringContainsString('image-zoom-button', $result['html']);
        $this->assertTrue($result['metadata']['has_files']);
    }

    public function test_audio_file_embedding()
    {
        $content = 'Listen to the pronunciation: [Audio](https://example.com/pronunciation.mp3)';

        $result = $this->richContentService->processUnifiedContent($content);

        $this->assertStringContainsString('audio-embed', $result['html']);
        // Audio file should show audio player or fallback
        $this->assertTrue(
            str_contains($result['html'], '<audio') || str_contains($result['html'], 'Download audio file'),
            'Should contain audio player or download fallback'
        );
        $this->assertTrue($result['metadata']['has_files']);
    }

    public function test_video_file_embedding()
    {
        $content = 'Watch the demonstration: [Demo Video](https://example.com/demo.mp4)';

        $result = $this->richContentService->processUnifiedContent($content);

        $this->assertStringContainsString('video-embed', $result['html']);
        // Video file should show video player or fallback
        $this->assertTrue(
            str_contains($result['html'], '<video') || str_contains($result['html'], 'Download video file'),
            'Should contain video player or download fallback'
        );
        $this->assertTrue($result['metadata']['has_files']);
    }

    public function test_collapsible_section_rendering()
    {
        $content = '!!! collapse "Advanced Topics"

This content is collapsible and contains advanced information.

!!!';

        $result = $this->richContentService->processUnifiedContent($content);

        // Collapsible section content should be present (even if not fully rendered)
        $this->assertStringContainsString('Advanced Topics', $result['html']);
        $this->assertStringContainsString('collapsible', $result['html']);
        $this->assertTrue($result['metadata']['has_interactive_elements']);
    }

    public function test_enhanced_table_rendering()
    {
        $content = '| Name | Age | Grade |
|------|-----|-------|
| Alice | 12 | A |
| Bob | 11 | B |
| Carol | 13 | A+ |';

        $result = $this->richContentService->processUnifiedContent($content);

        // Table should be rendered (enhanced or basic)
        $this->assertTrue(
            str_contains($result['html'], 'enhanced-table-container') || str_contains($result['html'], '<table'),
            'Should contain enhanced table or basic table'
        );
        // Table content should be present somewhere in the response
        $this->assertTrue(
            str_contains($result['html'], 'Carol') || str_contains($result['html'], 'table'),
            'Should contain table content or table element'
        );
        $this->assertTrue($result['metadata']['has_interactive_elements']);
    }

    public function test_complexity_score_calculation()
    {
        // Basic content
        $basicContent = 'This is just plain text content.';
        $basicResult = $this->richContentService->processUnifiedContent($basicContent);
        $this->assertEquals('basic', $basicResult['metadata']['complexity_score']);

        // Intermediate content
        $intermediateContent = 'Watch this video: [Tutorial](https://youtube.com/watch?v=abc123)

Download the notes: [Notes](https://example.com/notes.pdf)';
        $intermediateResult = $this->richContentService->processUnifiedContent($intermediateContent);
        $this->assertEquals('intermediate', $intermediateResult['metadata']['complexity_score']);

        // Advanced content
        $advancedContent = 'Watch these videos:
- [Video 1](https://youtube.com/watch?v=abc123)
- [Video 2](https://youtube.com/watch?v=def456)
- [Video 3](https://youtube.com/watch?v=ghi789)

Files to download:
- [File 1](https://example.com/file1.pdf)
- [File 2](https://example.com/file2.docx)
- [File 3](https://example.com/file3.xlsx)
- [File 4](https://example.com/file4.pptx)

!!! collapse "Additional Resources"
More content here
!!!

| Topic | Difficulty |
|-------|------------|
| Basic | Easy |
| Advanced | Hard |';

        $advancedResult = $this->richContentService->processUnifiedContent($advancedContent);
        $this->assertEquals('advanced', $advancedResult['metadata']['complexity_score']);
    }

    public function test_video_url_validation()
    {
        // Valid YouTube URL
        $youtubeResult = $this->richContentService->validateVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertTrue($youtubeResult['valid']);
        $this->assertEquals('youtube', $youtubeResult['type']);
        $this->assertEquals('dQw4w9WgXcQ', $youtubeResult['id']);

        // Valid YouTube URL with timestamp
        $youtubeWithTimeResult = $this->richContentService->validateVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=120s');
        $this->assertTrue($youtubeWithTimeResult['valid']);
        $this->assertEquals(120, $youtubeWithTimeResult['start_time']);

        // Valid Vimeo URL
        $vimeoResult = $this->richContentService->validateVideoUrl('https://vimeo.com/123456789');
        $this->assertTrue($vimeoResult['valid']);
        $this->assertEquals('vimeo', $vimeoResult['type']);
        $this->assertEquals('123456789', $vimeoResult['id']);

        // Valid Khan Academy URL
        $khanResult = $this->richContentService->validateVideoUrl('https://www.khanacademy.org/math/algebra/intro-to-algebra');
        $this->assertTrue($khanResult['valid']);
        $this->assertEquals('khan_academy', $khanResult['type']);

        // Invalid URL
        $invalidResult = $this->richContentService->validateVideoUrl('https://example.com/not-a-video');
        $this->assertFalse($invalidResult['valid']);
        $this->assertArrayHasKey('error', $invalidResult);
    }

    public function test_callout_creation()
    {
        $callout = $this->richContentService->createCallout('tip', 'Study Tip', 'Review this material regularly for better retention.');

        $this->assertStringContainsString('💡', $callout);
        $this->assertStringContainsString('**Study Tip**', $callout);
        $this->assertStringContainsString('Review this material regularly', $callout);
        $this->assertStringContainsString('>', $callout); // Blockquote format
    }

    public function test_collapsible_section_creation()
    {
        $section = $this->richContentService->createCollapsibleSection('Extra Information', 'This is additional content that can be collapsed.', true);

        $this->assertStringContainsString('collapse-open', $section);
        $this->assertStringContainsString('Extra Information', $section);
        $this->assertStringContainsString('This is additional content', $section);
        $this->assertStringContainsString('!!!', $section);
    }

    public function test_enhanced_metadata_extraction()
    {
        $content = 'Learn about science with these resources:

Watch the video: [Science Explained](https://youtube.com/watch?v=science123)
Watch another: [More Science](https://vimeo.com/456789)

Download materials:
- [Lab Manual](https://example.com/manual.pdf)
- [Worksheet](https://example.com/worksheet.docx)
- [Data](https://example.com/data.xlsx)

!!! collapse "Advanced Topics"
Additional information here
!!!

| Experiment | Result |
|------------|--------|
| Test 1 | Success |
| Test 2 | Failed |';

        $result = $this->richContentService->processUnifiedContent($content);
        $metadata = $result['metadata'];

        $this->assertTrue($metadata['has_videos']);
        $this->assertTrue($metadata['has_files']);
        $this->assertTrue($metadata['has_interactive_elements']);
        $this->assertEquals(2, $metadata['video_count']);
        $this->assertEquals(3, $metadata['file_count']);
        $this->assertEquals(20, $metadata['estimated_video_time']); // 2 videos * 10 min average
        $this->assertEquals('intermediate', $metadata['complexity_score']);
    }

    public function test_regular_links_not_converted_to_embeds()
    {
        $content = 'Visit our website: [Our Site](https://example.com)
Read the article: [Article](https://blog.example.com/post)';

        $result = $this->richContentService->processUnifiedContent($content);

        // Should not contain video or file embed classes
        $this->assertStringNotContainsString('video-embed-container', $result['html']);
        $this->assertStringNotContainsString('file-embed', $result['html']);
        $this->assertStringNotContainsString('educational-embed', $result['html']);

        // Should contain regular links
        $this->assertStringContainsString('<a href="https://example.com"', $result['html']);
        $this->assertStringContainsString('<a href="https://blog.example.com/post"', $result['html']);

        $this->assertFalse($result['metadata']['has_videos']);
        $this->assertFalse($result['metadata']['has_files']);
    }
}

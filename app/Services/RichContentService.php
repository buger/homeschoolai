<?php

namespace App\Services;

use App\Services\Markdown\Extensions\InteractiveExtension;
use App\Services\Markdown\Extensions\UnifiedLinkRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link as CommonMarkLink;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
use League\HTMLToMarkdown\HtmlConverter;
use Mews\Purifier\Facades\Purifier;

class RichContentService
{
    protected MarkdownConverter $markdownConverter;

    protected HtmlConverter $htmlConverter;

    public function __construct()
    {
        $this->initializeMarkdownConverter();
        $this->initializeHtmlConverter();
    }

    /**
     * Initialize the markdown converter with extensions
     */
    private function initializeMarkdownConverter(): void
    {
        $config = [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ];

        $environment = new Environment($config);

        // Add useful extensions
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new TaskListExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addExtension(new SmartPunctExtension);
        $environment->addExtension(new FrontMatterExtension);

        // Add our custom extensions for enhanced learning content
        $environment->addRenderer(CommonMarkLink::class, new UnifiedLinkRenderer, 100);
        $environment->addExtension(new InteractiveExtension);

        $this->markdownConverter = new MarkdownConverter($environment);
    }

    /**
     * Initialize HTML to Markdown converter
     */
    private function initializeHtmlConverter(): void
    {
        $this->htmlConverter = new HtmlConverter([
            'header_style' => 'atx',
            'bold_style' => '**',
            'italic_style' => '*',
            'remove_nodes' => 'script style',
            'strip_tags' => true,
            'preserve_comments' => false,
        ]);
    }

    /**
     * Convert markdown to safe HTML
     */
    public function markdownToHtml(string $markdown): string
    {
        $html = $this->markdownConverter->convert($markdown)->getContent();

        // Sanitize the HTML using HTMLPurifier with educational content profile
        return Purifier::clean($html, 'educational');
    }

    /**
     * Convert HTML to markdown
     */
    public function htmlToMarkdown(string $html): string
    {
        // First sanitize the HTML
        $cleanHtml = Purifier::clean($html, 'educational');

        return $this->htmlConverter->convert($cleanHtml);
    }

    /**
     * Process rich content based on format type
     */
    public function processRichContent(string $content, string $format): array
    {
        switch ($format) {
            case 'markdown':
                $html = $this->markdownToHtml($content);
                $metadata = $this->generateContentMetadata($content, 'markdown');
                break;

            case 'html':
                $html = Purifier::clean($content, 'educational');
                $metadata = $this->generateContentMetadata($content, 'html');
                break;

            case 'plain':
            default:
                $html = nl2br(e($content));
                $metadata = $this->generateContentMetadata($content, 'plain');
                break;
        }

        return [
            'html' => $html,
            'metadata' => $metadata,
        ];
    }

    /**
     * Generate content metadata (word count, reading time, etc.)
     */
    public function generateContentMetadata(string $content, string $format): array
    {
        // Strip markdown/HTML for accurate word count
        $plainText = match ($format) {
            'markdown' => strip_tags($this->markdownToHtml($content)),
            'html' => strip_tags($content),
            'plain' => $content,
            default => $content,
        };

        $wordCount = str_word_count($plainText);
        $readingTime = max(1, ceil($wordCount / 200)); // 200 words per minute average

        return [
            'word_count' => $wordCount,
            'reading_time' => $readingTime,
            'character_count' => mb_strlen($plainText),
            'last_updated' => now()->toISOString(),
            'format' => $format,
        ];
    }

    /**
     * Upload image for rich content and return markdown-ready reference
     */
    public function uploadContentImage(int $topicId, UploadedFile $image, ?string $altText = null): array
    {
        // Validate image
        $this->validateContentImage($image);

        // Generate unique filename
        $filename = $this->generateImageFilename($topicId, $image);

        // Store image
        $path = $image->storeAs("topic-content/{$topicId}/images", $filename, 'public');
        $url = Storage::url($path);

        // Generate thumbnail for large images
        $thumbnailPath = $this->generateThumbnail($path, $topicId);

        $imageData = [
            'filename' => $filename,
            'original_name' => $image->getClientOriginalName(),
            'path' => $path,
            'url' => $url,
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_url' => $thumbnailPath ? Storage::url($thumbnailPath) : null,
            'alt_text' => $altText ?: pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME),
            'mime_type' => $image->getMimeType(),
            'size' => $image->getSize(),
            'dimensions' => $this->getImageDimensions($image),
            'uploaded_at' => now()->toISOString(),
        ];

        return $imageData;
    }

    /**
     * Generate markdown reference for uploaded image
     */
    public function generateImageMarkdown(array $imageData): string
    {
        return "![{$imageData['alt_text']}]({$imageData['url']})";
    }

    /**
     * Extract and process embedded images from content
     */
    public function extractEmbeddedImages(string $content, string $format): array
    {
        $images = [];

        if ($format === 'markdown') {
            // Extract markdown images: ![alt](url)
            preg_match_all('/!\[([^\]]*)\]\(([^)]+)\)/', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $images[] = [
                    'alt_text' => $match[1],
                    'url' => $match[2],
                    'markdown' => $match[0],
                ];
            }
        } elseif ($format === 'html') {
            // Extract HTML images
            preg_match_all('/<img[^>]+src="([^"]+)"[^>]*alt="([^"]*)"[^>]*>/i', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $images[] = [
                    'url' => $match[1],
                    'alt_text' => $match[2] ?? '',
                    'html' => $match[0],
                ];
            }
        }

        return $images;
    }

    /**
     * Validate content image upload
     */
    private function validateContentImage(UploadedFile $image): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (! in_array($image->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid image format. Allowed: JPEG, PNG, GIF, WebP, SVG');
        }

        if ($image->getSize() > $maxSize) {
            throw new \InvalidArgumentException('Image too large. Maximum size: 5MB');
        }
    }

    /**
     * Generate unique image filename
     */
    private function generateImageFilename(int $topicId, UploadedFile $image): string
    {
        $extension = $image->getClientOriginalExtension();
        $basename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

        // Sanitize filename
        $basename = Str::slug($basename);
        $basename = substr($basename, 0, 50);

        // Ensure uniqueness
        $filename = $basename.'.'.$extension;
        $counter = 1;

        while (Storage::disk('public')->exists("topic-content/{$topicId}/images/{$filename}")) {
            $filename = $basename.'-'.$counter.'.'.$extension;
            $counter++;
        }

        return $filename;
    }

    /**
     * Generate thumbnail for large images
     */
    private function generateThumbnail(string $imagePath, int $topicId): ?string
    {
        // For now, we'll skip thumbnail generation
        // In a production app, you'd use intervention/image or similar
        return null;
    }

    /**
     * Get image dimensions
     */
    private function getImageDimensions(UploadedFile $image): array
    {
        $imagePath = $image->getRealPath();
        $imageInfo = getimagesize($imagePath);

        return [
            'width' => $imageInfo[0] ?? null,
            'height' => $imageInfo[1] ?? null,
        ];
    }

    /**
     * Clean up content images when topic is deleted
     */
    public function cleanupContentImages(int $topicId): bool
    {
        $directory = "topic-content/{$topicId}/images";

        if (Storage::disk('public')->exists($directory)) {
            return Storage::disk('public')->deleteDirectory($directory);
        }

        return true;
    }

    /**
     * Convert content between formats
     */
    public function convertContentFormat(string $content, string $fromFormat, string $toFormat): string
    {
        if ($fromFormat === $toFormat) {
            return $content;
        }

        switch ($fromFormat.'_to_'.$toFormat) {
            case 'plain_to_markdown':
                return $content; // Plain text is valid markdown

            case 'plain_to_html':
                return nl2br(e($content));

            case 'markdown_to_html':
                return $this->markdownToHtml($content);

            case 'markdown_to_plain':
                return strip_tags($this->markdownToHtml($content));

            case 'html_to_markdown':
                return $this->htmlToMarkdown($content);

            case 'html_to_plain':
                return strip_tags($content);

            default:
                throw new \InvalidArgumentException("Conversion from {$fromFormat} to {$toFormat} not supported");
        }
    }

    /**
     * Create callout box markdown
     */
    public function createCallout(string $type, string $title, string $content): string
    {
        $icon = match ($type) {
            'tip' => '💡',
            'warning' => '⚠️',
            'note' => '📝',
            'info' => 'ℹ️',
            'success' => '✅',
            'error' => '❌',
            default => '📌',
        };

        return <<<MARKDOWN
> {$icon} **{$title}**
>
> {$content}

MARKDOWN;
    }

    /**
     * Process unified learning content with enhanced rendering
     */
    public function processUnifiedContent(string $content): array
    {
        // Process the content with enhanced markdown parsing
        $html = $this->markdownToHtml($content);
        $metadata = $this->generateContentMetadata($content, 'markdown');

        // Extract additional metadata from enhanced content
        $enhancedMetadata = $this->extractEnhancedMetadata($content);
        $metadata = array_merge($metadata, $enhancedMetadata);

        return [
            'html' => $html,
            'metadata' => $metadata,
        ];
    }

    /**
     * Extract enhanced metadata from content (videos, files, interactive elements)
     */
    private function extractEnhancedMetadata(string $content): array
    {
        $metadata = [
            'has_videos' => false,
            'has_files' => false,
            'has_interactive_elements' => false,
            'video_count' => 0,
            'file_count' => 0,
            'estimated_video_time' => 0,
            'complexity_score' => 'basic',
        ];

        // Detect video embeds
        $videoPatterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/vimeo\.com\/(?:video\/)?(\d+)/',
            '/khanacademy\.org\/.*\/([a-zA-Z0-9_-]+)/',
            '/coursera\.org\/learn\/([^\/]+)/',
            '/edx\.org\/course\/([^\/]+)/',
        ];

        foreach ($videoPatterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $metadata['has_videos'] = true;
                $metadata['video_count'] += count($matches[0]);
            }
        }

        // Detect file links
        $filePattern = '/\[([^\]]+)\]\(([^)]+\.(?:pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar|mp3|mp4|avi|mov|jpg|jpeg|png|gif))\)/i';
        if (preg_match_all($filePattern, $content, $matches)) {
            $metadata['has_files'] = true;
            $metadata['file_count'] = count($matches[0]);
        }

        // Detect interactive elements
        $interactivePatterns = [
            '/!!!\s+(collapse|collapse-open)/',
            '/<details/',
            '/\|.*\|.*\|/', // Simple table detection
        ];

        foreach ($interactivePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $metadata['has_interactive_elements'] = true;
                break;
            }
        }

        // Estimate video time (rough calculation)
        if ($metadata['has_videos']) {
            $metadata['estimated_video_time'] = $metadata['video_count'] * 10; // 10 minutes average per video
        }

        // Calculate complexity score
        $complexityFactors = 0;
        if ($metadata['has_videos']) {
            $complexityFactors++;
        }
        if ($metadata['has_files']) {
            $complexityFactors++;
        }
        if ($metadata['has_interactive_elements']) {
            $complexityFactors++;
        }
        if ($metadata['video_count'] > 2) {
            $complexityFactors++;
        }
        if ($metadata['file_count'] > 3) {
            $complexityFactors++;
        }

        $metadata['complexity_score'] = match (true) {
            $complexityFactors >= 4 => 'advanced',
            $complexityFactors >= 2 => 'intermediate',
            default => 'basic'
        };

        return $metadata;
    }

    /**
     * Create collapsible section markdown
     */
    public function createCollapsibleSection(string $title, string $content, bool $isOpen = false): string
    {
        $directive = $isOpen ? 'collapse-open' : 'collapse';

        return <<<MARKDOWN
!!! {$directive} "{$title}"

{$content}

!!!

MARKDOWN;
    }

    /**
     * Validate video URL and return embed information
     */
    public function validateVideoUrl(string $url): ?array
    {
        // YouTube patterns
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})(?:\S+t=(\d+))?/', $url, $matches)) {
            return [
                'valid' => true,
                'type' => 'youtube',
                'id' => $matches[1],
                'start_time' => isset($matches[2]) ? (int) $matches[2] : null,
                'embed_url' => "https://www.youtube.com/embed/{$matches[1]}",
                'thumbnail' => "https://img.youtube.com/vi/{$matches[1]}/maxresdefault.jpg",
            ];
        }

        // Vimeo patterns
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
            return [
                'valid' => true,
                'type' => 'vimeo',
                'id' => $matches[1],
                'embed_url' => "https://player.vimeo.com/video/{$matches[1]}",
                'thumbnail' => null,
            ];
        }

        // Educational platforms
        $educationalPatterns = [
            'khan_academy' => '/khanacademy\.org\/.*\/([a-zA-Z0-9_-]+)/',
            'coursera' => '/coursera\.org\/learn\/([^\/]+)/',
            'edx' => '/edx\.org\/course\/([^\/]+)/',
        ];

        foreach ($educationalPatterns as $platform => $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return [
                    'valid' => true,
                    'type' => $platform,
                    'id' => $matches[1],
                    'embed_url' => null, // These platforms don't typically support embedding
                    'original_url' => $url,
                    'platform_name' => ucfirst(str_replace('_', ' ', $platform)),
                ];
            }
        }

        return [
            'valid' => false,
            'error' => 'Unsupported video platform or invalid URL format',
        ];
    }
}

<?php

namespace App\Services\Markdown\Extensions;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class UnifiedLinkRenderer implements NodeRendererInterface
{
    /**
     * @param  Link  $node
     */
    public function render($node, ChildNodeRendererInterface $childRenderer)
    {
        if (! $node instanceof Link) {
            throw new \InvalidArgumentException('Incompatible node type: '.get_class($node));
        }

        $url = $node->getUrl();
        $title = $childRenderer->renderNodes($node->children()) ?: basename($url);

        // 1. Check if this is a video URL first (highest priority)
        $videoInfo = $this->parseVideoUrl($url);
        if ($videoInfo) {
            return $this->renderVideoEmbed($videoInfo, $title, $url);
        }

        // 2. Check if this is a file URL
        $fileInfo = $this->analyzeFileUrl($url);
        if ($fileInfo) {
            return $this->renderFileEmbed($fileInfo, $title, $url);
        }

        // 3. Fall back to regular link rendering
        $attrs = $node->data->get('attributes', []);
        $attrs['href'] = $url;

        if ($node->getTitle() !== null && $node->getTitle() !== '') {
            $attrs['title'] = $node->getTitle();
        }

        return new HtmlElement('a', $attrs, $childRenderer->renderNodes($node->children()));
    }

    private function parseVideoUrl(string $url): ?array
    {
        // YouTube patterns
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})(?:\S+t=(\d+))?/', $url, $matches)) {
            $startTime = isset($matches[2]) ? (int) $matches[2] : null;

            return [
                'type' => 'youtube',
                'id' => $matches[1],
                'start_time' => $startTime,
                'thumbnail' => "https://img.youtube.com/vi/{$matches[1]}/maxresdefault.jpg",
            ];
        }

        // Vimeo patterns
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)(?:#t=(\d+))?/', $url, $matches)) {
            $startTime = isset($matches[2]) ? (int) $matches[2] : null;

            return [
                'type' => 'vimeo',
                'id' => $matches[1],
                'start_time' => $startTime,
                'thumbnail' => null, // Vimeo thumbnails require API call
            ];
        }

        // Khan Academy patterns
        if (preg_match('/khanacademy\.org\/.*\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return [
                'type' => 'khan_academy',
                'id' => $matches[1],
                'url' => $url, // Keep original URL for Khan Academy
                'thumbnail' => null,
            ];
        }

        // Coursera patterns
        if (preg_match('/coursera\.org\/learn\/([^\/]+)/', $url, $matches)) {
            return [
                'type' => 'coursera',
                'id' => $matches[1],
                'url' => $url,
                'thumbnail' => null,
            ];
        }

        // edX patterns
        if (preg_match('/edx\.org\/course\/([^\/]+)/', $url, $matches)) {
            return [
                'type' => 'edx',
                'id' => $matches[1],
                'url' => $url,
                'thumbnail' => null,
            ];
        }

        return null;
    }

    private function analyzeFileUrl(string $url): ?array
    {
        // Extract file extension from URL
        $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
        $extension = strtolower($pathInfo['extension'] ?? '');

        if (empty($extension)) {
            return null;
        }

        $filename = $pathInfo['basename'] ?? basename($url);

        // Define file type categories
        $fileTypes = [
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'],
            'spreadsheet' => ['xls', 'xlsx', 'csv', 'ods'],
            'presentation' => ['ppt', 'pptx', 'odp'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'],
            'code' => ['js', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'rb', 'go'],
            'ebook' => ['epub', 'mobi', 'azw3'],
        ];

        $fileType = 'file'; // default
        foreach ($fileTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                $fileType = $type;
                break;
            }
        }

        return [
            'type' => $fileType,
            'extension' => $extension,
            'filename' => $filename,
            'url' => $url,
            'is_downloadable' => $this->isDownloadableFile($fileType, $extension),
            'is_previewable' => $this->isPreviewableFile($fileType, $extension),
        ];
    }

    private function isDownloadableFile(string $fileType, string $extension): bool
    {
        // Most file types are downloadable except web content
        $nonDownloadable = ['html', 'htm', 'php', 'js', 'css'];

        return ! in_array($extension, $nonDownloadable);
    }

    private function isPreviewableFile(string $fileType, string $extension): bool
    {
        // Files that can be previewed inline
        $previewable = [
            'image' => true,
            'audio' => true,
            'video' => true,
            'document' => in_array($extension, ['pdf', 'txt']),
            'code' => true,
        ];

        return $previewable[$fileType] ?? false;
    }

    private function renderVideoEmbed(array $videoInfo, string $title, string $originalUrl): HtmlElement
    {
        $type = $videoInfo['type'];

        switch ($type) {
            case 'youtube':
                return $this->renderYouTubeEmbed($videoInfo, $title, $originalUrl);

            case 'vimeo':
                return $this->renderVimeoEmbed($videoInfo, $title, $originalUrl);

            case 'khan_academy':
            case 'coursera':
            case 'edx':
                return $this->renderEducationalPlatformEmbed($videoInfo, $title, $originalUrl);

            default:
                // Fallback to regular link
                return new HtmlElement('a', ['href' => $originalUrl, 'target' => '_blank'], $title);
        }
    }

    private function renderYouTubeEmbed(array $videoInfo, string $title, string $originalUrl): HtmlElement
    {
        $id = $videoInfo['id'];
        $startTime = $videoInfo['start_time'] ? "&start={$videoInfo['start_time']}" : '';
        $embedUrl = "https://www.youtube.com/embed/{$id}?rel=0{$startTime}";

        $videoContainer = new HtmlElement('div', [
            'class' => 'video-embed-container youtube-embed',
            'data-video-type' => 'youtube',
            'data-video-id' => $id,
            'data-original-url' => $originalUrl,
        ]);

        // Responsive iframe wrapper
        $iframeWrapper = new HtmlElement('div', [
            'class' => 'video-embed-responsive',
        ]);

        $iframe = new HtmlElement('iframe', [
            'src' => $embedUrl,
            'title' => $title,
            'frameborder' => '0',
            'allow' => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
            'allowfullscreen' => 'allowfullscreen',
            'loading' => 'lazy',
            'width' => '560',
            'height' => '315',
        ], '');

        $iframeWrapper->setContents([$iframe]);

        // Video metadata
        $metadata = new HtmlElement('div', ['class' => 'video-embed-metadata']);
        $titleElement = new HtmlElement('h4', ['class' => 'video-embed-title'], $title);
        $linkElement = new HtmlElement('a', [
            'href' => $originalUrl,
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
            'class' => 'video-embed-link',
        ], 'Watch on YouTube');

        $metadata->setContents([$titleElement, $linkElement]);
        $videoContainer->setContents([$iframeWrapper, $metadata]);

        return $videoContainer;
    }

    private function renderVimeoEmbed(array $videoInfo, string $title, string $originalUrl): HtmlElement
    {
        $id = $videoInfo['id'];
        $startTime = $videoInfo['start_time'] ? "#t={$videoInfo['start_time']}s" : '';
        $embedUrl = "https://player.vimeo.com/video/{$id}{$startTime}";

        $videoContainer = new HtmlElement('div', [
            'class' => 'video-embed-container vimeo-embed',
            'data-video-type' => 'vimeo',
            'data-video-id' => $id,
            'data-original-url' => $originalUrl,
        ]);

        $iframeWrapper = new HtmlElement('div', [
            'class' => 'video-embed-responsive',
        ]);

        $iframe = new HtmlElement('iframe', [
            'src' => $embedUrl,
            'title' => $title,
            'frameborder' => '0',
            'allow' => 'autoplay; fullscreen; picture-in-picture',
            'allowfullscreen' => 'allowfullscreen',
            'loading' => 'lazy',
            'width' => '560',
            'height' => '315',
        ], '');

        $iframeWrapper->setContents([$iframe]);

        $metadata = new HtmlElement('div', ['class' => 'video-embed-metadata']);
        $titleElement = new HtmlElement('h4', ['class' => 'video-embed-title'], $title);
        $linkElement = new HtmlElement('a', [
            'href' => $originalUrl,
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
            'class' => 'video-embed-link',
        ], 'Watch on Vimeo');

        $metadata->setContents([$titleElement, $linkElement]);
        $videoContainer->setContents([$iframeWrapper, $metadata]);

        return $videoContainer;
    }

    private function renderEducationalPlatformEmbed(array $videoInfo, string $title, string $originalUrl): HtmlElement
    {
        $type = $videoInfo['type'];
        $url = $videoInfo['url'];

        $platformNames = [
            'khan_academy' => 'Khan Academy',
            'coursera' => 'Coursera',
            'edx' => 'edX',
        ];

        $platformName = $platformNames[$type] ?? ucfirst($type);

        $embedContainer = new HtmlElement('div', [
            'class' => "educational-embed {$type}-embed",
            'data-platform' => $type,
            'data-original-url' => $originalUrl,
        ]);

        $icon = $this->getPlatformIcon($type);

        $iconElement = new HtmlElement('div', ['class' => 'educational-embed-icon'], $icon);
        $titleElement = new HtmlElement('h4', ['class' => 'educational-embed-title'], $title);
        $platformElement = new HtmlElement('span', ['class' => 'educational-embed-platform'], $platformName);
        $linkElement = new HtmlElement('a', [
            'href' => $url,
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
            'class' => 'educational-embed-link',
        ], "Access on {$platformName}");

        $content = new HtmlElement('div', ['class' => 'educational-embed-content'], [
            $titleElement,
            $platformElement,
            $linkElement,
        ]);

        $embedContainer->setContents([$iconElement, $content]);

        return $embedContainer;
    }

    private function getPlatformIcon(string $type): string
    {
        $icons = [
            'khan_academy' => '🎓',
            'coursera' => '📚',
            'edx' => '🏫',
        ];

        return $icons[$type] ?? '🎥';
    }

    private function renderFileEmbed(array $fileInfo, string $title, string $url): HtmlElement
    {
        $type = $fileInfo['type'];
        $extension = $fileInfo['extension'];

        // Handle special file types
        switch ($type) {
            case 'image':
                return $this->renderImageFile($fileInfo, $title, $url);

            case 'audio':
                return $this->renderAudioFile($fileInfo, $title, $url);

            case 'video':
                return $this->renderVideoFile($fileInfo, $title, $url);

            case 'document':
                if ($extension === 'pdf') {
                    return $this->renderPdfFile($fileInfo, $title, $url);
                }

                return $this->renderGenericFile($fileInfo, $title, $url);

            default:
                return $this->renderGenericFile($fileInfo, $title, $url);
        }
    }

    private function renderImageFile(array $fileInfo, string $title, string $url): HtmlElement
    {
        $container = new HtmlElement('div', [
            'class' => 'file-embed image-embed',
            'data-file-type' => 'image',
        ]);

        $imageWrapper = new HtmlElement('div', ['class' => 'image-embed-wrapper']);

        $image = new HtmlElement('img', [
            'src' => $url,
            'alt' => $title,
            'class' => 'image-embed-preview',
            'loading' => 'lazy',
        ], '');

        $overlay = new HtmlElement('div', ['class' => 'image-embed-overlay']);
        $zoomButton = new HtmlElement('button', [
            'class' => 'image-zoom-button',
            'data-lightbox-url' => $url,
            'aria-label' => 'View full size',
        ], '🔍');

        $overlay->setContents([$zoomButton]);
        $imageWrapper->setContents([$image, $overlay]);

        $metadata = $this->createFileMetadata($fileInfo, $title, $url);

        $container->setContents([$imageWrapper, $metadata]);

        return $container;
    }

    private function renderAudioFile(array $fileInfo, string $title, string $url): HtmlElement
    {
        $container = new HtmlElement('div', [
            'class' => 'file-embed audio-embed',
            'data-file-type' => 'audio',
        ]);

        $audioPlayer = new HtmlElement('audio', [
            'controls' => 'controls',
            'preload' => 'metadata',
            'class' => 'audio-player',
        ]);

        $source = new HtmlElement('source', [
            'src' => $url,
            'type' => $this->getMimeType($fileInfo['extension']),
        ], '');

        $fallback = new HtmlElement('p', [], [
            'Your browser does not support the audio element. ',
            new HtmlElement('a', ['href' => $url, 'download' => $fileInfo['filename']], 'Download audio file'),
        ]);

        $audioPlayer->setContents([$source, $fallback]);

        $metadata = $this->createFileMetadata($fileInfo, $title, $url);

        $container->setContents([$audioPlayer, $metadata]);

        return $container;
    }

    private function renderVideoFile(array $fileInfo, string $title, string $url): HtmlElement
    {
        $container = new HtmlElement('div', [
            'class' => 'file-embed video-embed',
            'data-file-type' => 'video',
        ]);

        $videoWrapper = new HtmlElement('div', ['class' => 'video-embed-responsive']);

        $videoPlayer = new HtmlElement('video', [
            'controls' => 'controls',
            'preload' => 'metadata',
            'class' => 'video-player',
            'width' => '100%',
        ]);

        $source = new HtmlElement('source', [
            'src' => $url,
            'type' => $this->getMimeType($fileInfo['extension']),
        ], '');

        $fallback = new HtmlElement('p', [], [
            'Your browser does not support the video element. ',
            new HtmlElement('a', ['href' => $url, 'download' => $fileInfo['filename']], 'Download video file'),
        ]);

        $videoPlayer->setContents([$source, $fallback]);
        $videoWrapper->setContents([$videoPlayer]);

        $metadata = $this->createFileMetadata($fileInfo, $title, $url);

        $container->setContents([$videoWrapper, $metadata]);

        return $container;
    }

    private function renderPdfFile(array $fileInfo, string $title, string $url): HtmlElement
    {
        $container = new HtmlElement('div', [
            'class' => 'file-embed pdf-embed',
            'data-file-type' => 'pdf',
        ]);

        $metadata = $this->createFileMetadata($fileInfo, $title, $url, false);

        $controls = new HtmlElement('div', ['class' => 'pdf-controls']);

        $previewButton = new HtmlElement('button', [
            'class' => 'pdf-preview-toggle btn btn-secondary',
            'data-target' => 'pdf-preview-wrapper',
        ], '👁️ Preview PDF');

        $downloadButton = new HtmlElement('a', [
            'href' => $url,
            'download' => $fileInfo['filename'],
            'class' => 'btn btn-primary',
        ], '⬇️ Download PDF');

        $openButton = new HtmlElement('a', [
            'href' => $url,
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
            'class' => 'btn btn-secondary',
        ], '🔗 Open in New Tab');

        $controls->setContents([$previewButton, $downloadButton, $openButton]);

        $container->setContents([$metadata, $controls]);

        return $container;
    }

    private function renderGenericFile(array $fileInfo, string $title, string $url): HtmlElement
    {
        $container = new HtmlElement('div', [
            'class' => 'file-embed generic-file-embed',
            'data-file-type' => $fileInfo['type'],
        ]);

        $icon = $this->getFileIcon($fileInfo['type'], $fileInfo['extension']);

        $iconElement = new HtmlElement('div', ['class' => 'file-icon'], $icon);

        $fileInfoElement = new HtmlElement('div', ['class' => 'file-info']);
        $titleElement = new HtmlElement('h4', ['class' => 'file-title'], $title);
        $typeElement = new HtmlElement('span', ['class' => 'file-type'], strtoupper($fileInfo['extension']).' File');

        $fileInfoElement->setContents([$titleElement, $typeElement]);

        $actions = new HtmlElement('div', ['class' => 'file-actions']);

        $actionButtons = [];
        if ($fileInfo['is_downloadable']) {
            $actionButtons[] = new HtmlElement('a', [
                'href' => $url,
                'download' => $fileInfo['filename'],
                'class' => 'btn btn-primary btn-sm',
            ], '⬇️ Download');
        }

        $actionButtons[] = new HtmlElement('a', [
            'href' => $url,
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
            'class' => 'btn btn-secondary btn-sm',
        ], '🔗 View');

        $actions->setContents($actionButtons);

        $container->setContents([$iconElement, $fileInfoElement, $actions]);

        return $container;
    }

    private function createFileMetadata(array $fileInfo, string $title, string $url, bool $includeActions = true): HtmlElement
    {
        $metadata = new HtmlElement('div', ['class' => 'file-metadata']);

        $titleElement = new HtmlElement('h4', ['class' => 'file-title'], $title);
        $typeElement = new HtmlElement('span', ['class' => 'file-type'], strtoupper($fileInfo['extension']).' File');

        $info = new HtmlElement('div', ['class' => 'file-info'], [$titleElement, $typeElement]);

        $contents = [$info];

        if ($includeActions) {
            $actions = new HtmlElement('div', ['class' => 'file-actions']);

            $actionButtons = [];
            if ($fileInfo['is_downloadable']) {
                $actionButtons[] = new HtmlElement('a', [
                    'href' => $url,
                    'download' => $fileInfo['filename'],
                    'class' => 'btn btn-primary btn-sm',
                ], '⬇️ Download');
            }

            $actionButtons[] = new HtmlElement('a', [
                'href' => $url,
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
                'class' => 'btn btn-secondary btn-sm',
            ], '🔗 View');

            $actions->setContents($actionButtons);
            $contents[] = $actions;
        }

        $metadata->setContents($contents);

        return $metadata;
    }

    private function getFileIcon(string $type, string $extension): string
    {
        $icons = [
            'document' => '📄',
            'spreadsheet' => '📊',
            'presentation' => '📽️',
            'image' => '🖼️',
            'audio' => '🎵',
            'video' => '🎥',
            'archive' => '📦',
            'code' => '💻',
            'ebook' => '📚',
            'pdf' => '📕',
        ];

        if ($extension === 'pdf') {
            return $icons['pdf'];
        }

        return $icons[$type] ?? '📎';
    }

    private function getMimeType(string $extension): string
    {
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'flac' => 'audio/flac',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'mkv' => 'video/x-matroska',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}

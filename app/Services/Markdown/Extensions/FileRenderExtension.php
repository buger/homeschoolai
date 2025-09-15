<?php

namespace App\Services\Markdown\Extensions;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class FileRenderExtension implements ExtensionInterface
{
    public function configureSchema(EnvironmentBuilderInterface $environment): void
    {
        // No custom configuration needed
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(Link::class, new FileRenderRenderer, 50);
    }
}

class FileRenderRenderer implements NodeRendererInterface
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

        // Check if this is a file URL
        $fileInfo = $this->analyzeFileUrl($url);

        if ($fileInfo) {
            return $this->renderFileEmbed($fileInfo, $title, $url);
        }

        // Fall back to regular link rendering
        $attrs = $node->data->get('attributes', []);
        $attrs['href'] = $url;

        if ($node->getTitle() !== null && $node->getTitle() !== '') {
            $attrs['title'] = $node->getTitle();
        }

        return new HtmlElement('a', $attrs, $childRenderer->renderNodes($node->children()));
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

    private function renderFileEmbed(array $fileInfo, string $title, string $url): HtmlElement
    {
        $type = $fileInfo['type'];
        $extension = $fileInfo['extension'];
        $filename = $fileInfo['filename'];

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

                return $this->renderDocumentFile($fileInfo, $title, $url);

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

        $overlay->setContents($zoomButton);
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
        $videoWrapper->setContents($videoPlayer);

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

        // PDF preview iframe (optional, can be toggled)
        $previewWrapper = new HtmlElement('div', [
            'class' => 'pdf-preview-wrapper',
            'style' => 'display: none;', // Hidden by default
        ]);

        $iframe = new HtmlElement('iframe', [
            'src' => $url.'#toolbar=0',
            'class' => 'pdf-preview',
            'width' => '100%',
            'height' => '500px',
        ], '');

        $previewWrapper->setContents($iframe);

        // PDF controls
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

        $metadata = $this->createFileMetadata($fileInfo, $title, $url, false);

        $container->setContents([$metadata, $controls, $previewWrapper]);

        return $container;
    }

    private function renderDocumentFile(array $fileInfo, string $title, string $url): HtmlElement
    {
        return $this->renderGenericFile($fileInfo, $title, $url);
    }

    private function renderGenericFile(array $fileInfo, string $title, string $url): HtmlElement
    {
        $container = new HtmlElement('div', [
            'class' => 'file-embed generic-file-embed',
            'data-file-type' => $fileInfo['type'],
        ]);

        $icon = $this->getFileIcon($fileInfo['type'], $fileInfo['extension']);

        $iconElement = new HtmlElement('div', ['class' => 'file-icon'], $icon);

        $fileInfo_element = new HtmlElement('div', ['class' => 'file-info']);
        $titleElement = new HtmlElement('h4', ['class' => 'file-title'], $title);
        $typeElement = new HtmlElement('span', ['class' => 'file-type'], strtoupper($fileInfo['extension']).' File');

        $fileInfo_element->setContents([$titleElement, $typeElement]);

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

        $container->setContents([$iconElement, $fileInfo_element, $actions]);

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

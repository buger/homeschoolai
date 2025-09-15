<?php

namespace App\Services\Markdown\Extensions;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class VideoEmbedExtension implements ExtensionInterface
{
    public function configureSchema(EnvironmentBuilderInterface $environment): void
    {
        // No custom configuration needed
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(Link::class, new VideoEmbedRenderer, 100);
    }
}

class VideoEmbedRenderer implements NodeRendererInterface
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
        $title = $childRenderer->renderNodes($node->children()) ?: 'Video';

        // Check if this is a video URL
        $videoInfo = $this->parseVideoUrl($url);

        if ($videoInfo) {
            return $this->renderVideoEmbed($videoInfo, $title, $url);
        }

        // Not a video URL, fall back to regular link rendering
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

    private function renderVideoEmbed(array $videoInfo, string $title, string $originalUrl): HtmlElement
    {
        $type = $videoInfo['type'];
        $id = $videoInfo['id'];

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
        $thumbnail = $videoInfo['thumbnail'];

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

        $iframeWrapper->setContents($iframe);

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

        // Responsive iframe wrapper
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

        $iframeWrapper->setContents($iframe);

        // Video metadata
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
}

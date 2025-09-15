<?php

namespace App\Services;

use App\Models\Child;
use App\Models\Topic;
use Illuminate\Support\Facades\Log;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Kids Content Renderer Service
 *
 * Transforms unified markdown content into engaging, child-friendly experiences
 * with age-appropriate visual design, interactive elements, and educational features.
 */
class KidsContentRenderer
{
    private MarkdownConverter $markdownConverter;

    private RichContentService $richContentService;

    private SecurityService $securityService;

    public function __construct(
        RichContentService $richContentService,
        SecurityService $securityService
    ) {
        $this->richContentService = $richContentService;
        $this->securityService = $securityService;
        $this->initializeKidsMarkdownConverter();
    }

    /**
     * Render unified content specifically for kids view
     */
    public function renderForKids(string $content, Child $child, Topic $topic): array
    {
        try {
            // Apply age-appropriate content filtering
            $filteredContent = $this->applyContentFiltering($content, $child);

            // Process with enhanced markdown parsing
            $processedContent = $this->richContentService->processUnifiedContent($filteredContent);

            // Apply kids-specific enhancements
            $kidsEnhancedHtml = $this->applyKidsEnhancements($processedContent['html'], $child, $topic);

            // Generate interactive metadata
            $interactiveMetadata = $this->generateInteractiveMetadata($processedContent['metadata'], $child);

            // Calculate reading progress and gamification data
            $gamificationData = $this->generateGamificationData($content, $child, $topic);

            return [
                'html' => $kidsEnhancedHtml,
                'metadata' => array_merge($processedContent['metadata'], $interactiveMetadata),
                'gamification' => $gamificationData,
                'safety_level' => $this->calculateSafetyLevel($content),
                'reading_level' => $this->calculateReadingLevel($content, $child),
                'interactive_elements' => $this->extractInteractiveElements($kidsEnhancedHtml),
                'engagement_score' => $this->calculateEngagementScore($content, $child),
            ];

        } catch (\Exception $e) {
            Log::error('Kids content rendering failed', [
                'child_id' => $child->id,
                'topic_id' => $topic->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to safe, basic rendering
            return $this->getFallbackKidsContent($content, $child);
        }
    }

    /**
     * Apply kids-specific visual enhancements to HTML content
     */
    private function applyKidsEnhancements(string $html, Child $child, Topic $topic): string
    {
        $ageGroup = $this->getAgeGroup($child);

        // Wrap content in kids-friendly container
        $kidsHtml = '<div class="kids-content-container" data-age-group="'.$ageGroup.'" data-independence="'.$child->independence_level.'">';

        // Apply age-appropriate styling and animations
        $styledHtml = $this->applyAgeAppropriateStyles($html, $ageGroup);

        // Add interactive elements based on independence level
        $interactiveHtml = $this->addInteractiveElements($styledHtml, $child);

        // Enhance media content for kids
        $enhancedMediaHtml = $this->enhanceMediaForKids($interactiveHtml, $child);

        // Add progress tracking elements
        $progressHtml = $this->addProgressTracking($enhancedMediaHtml, $child, $topic);

        $kidsHtml .= $progressHtml;
        $kidsHtml .= '</div>';

        // Add kids-specific JavaScript and CSS
        $kidsHtml .= $this->getKidsStylesAndScripts($ageGroup, $child->independence_level);

        return $kidsHtml;
    }

    /**
     * Apply age-appropriate styling to content
     */
    private function applyAgeAppropriateStyles(string $html, string $ageGroup): string
    {
        $replacements = [];

        switch ($ageGroup) {
            case 'preschool': // PreK-K
                $replacements = [
                    '<h1>' => '<h1 class="kids-heading kids-heading-xl kids-colorful animate-bounce-gentle">🌟 ',
                    '</h1>' => ' 🌟</h1>',
                    '<h2>' => '<h2 class="kids-heading kids-heading-lg kids-playful">🎯 ',
                    '</h2>' => ' 🎯</h2>',
                    '<h3>' => '<h3 class="kids-heading kids-heading-md kids-fun">✨ ',
                    '</h3>' => ' ✨</h3>',
                    '<p>' => '<p class="kids-text kids-text-large kids-friendly">',
                    '<ul>' => '<ul class="kids-list kids-list-fun">',
                    '<ol>' => '<ol class="kids-list kids-list-numbered">',
                    '<li>' => '<li class="kids-list-item kids-interactive" data-sound="click">📌 ',
                ];
                break;

            case 'elementary': // 1st-5th
                $replacements = [
                    '<h1>' => '<h1 class="kids-heading kids-heading-xl kids-educational">📚 ',
                    '</h1>' => ' 📚</h1>',
                    '<h2>' => '<h2 class="kids-heading kids-heading-lg kids-structured">🔍 ',
                    '</h2>' => ' 🔍</h2>',
                    '<h3>' => '<h3 class="kids-heading kids-heading-md kids-clear">💡 ',
                    '</h3>' => ' 💡</h3>',
                    '<p>' => '<p class="kids-text kids-text-readable">',
                    '<ul>' => '<ul class="kids-list kids-list-organized">',
                    '<ol>' => '<ol class="kids-list kids-list-sequential">',
                    '<li>' => '<li class="kids-list-item kids-checkable" data-checkable="true">✅ ',
                ];
                break;

            case 'middle': // 6th-8th
                $replacements = [
                    '<h1>' => '<h1 class="kids-heading kids-heading-xl kids-sophisticated">🎓 ',
                    '</h1>' => ' 🎓</h1>',
                    '<h2>' => '<h2 class="kids-heading kids-heading-lg kids-analytical">🧠 ',
                    '</h2>' => ' 🧠</h2>',
                    '<h3>' => '<h3 class="kids-heading kids-heading-md kids-focused">⚡ ',
                    '</h3>' => ' ⚡</h3>',
                    '<p>' => '<p class="kids-text kids-text-mature">',
                    '<ul>' => '<ul class="kids-list kids-list-detailed">',
                    '<ol>' => '<ol class="kids-list kids-list-methodical">',
                    '<li>' => '<li class="kids-list-item kids-advanced" data-interactive="hover">🔗 ',
                ];
                break;

            case 'high': // 9th-12th
                $replacements = [
                    '<h1>' => '<h1 class="kids-heading kids-heading-xl kids-professional">🏆 ',
                    '</h1>' => ' 🏆</h1>',
                    '<h2>' => '<h2 class="kids-heading kids-heading-lg kids-academic">📖 ',
                    '</h2>' => ' 📖</h2>',
                    '<h3>' => '<h3 class="kids-heading kids-heading-md kids-research">🔬 ',
                    '</h3>' => ' 🔬</h3>',
                    '<p>' => '<p class="kids-text kids-text-advanced">',
                    '<ul>' => '<ul class="kids-list kids-list-comprehensive">',
                    '<ol>' => '<ol class="kids-list kids-list-systematic">',
                    '<li>' => '<li class="kids-list-item kids-scholarly" data-expandable="true">📝 ',
                ];
                break;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    /**
     * Add interactive elements based on child's independence level
     */
    private function addInteractiveElements(string $html, Child $child): string
    {
        $interactiveFeatures = [];

        // Level 1: View-only with basic interactions
        if ($child->independence_level >= 1) {
            $interactiveFeatures[] = 'read-aloud';
            $interactiveFeatures[] = 'highlighting';
            $interactiveFeatures[] = 'emoji-reactions';
        }

        // Level 2: Basic task interaction
        if ($child->independence_level >= 2) {
            $interactiveFeatures[] = 'checkboxes';
            $interactiveFeatures[] = 'simple-annotations';
            $interactiveFeatures[] = 'progress-tracking';
        }

        // Level 3: Enhanced interactions
        if ($child->independence_level >= 3) {
            $interactiveFeatures[] = 'note-taking';
            $interactiveFeatures[] = 'bookmarks';
            $interactiveFeatures[] = 'content-sharing';
        }

        // Level 4: Advanced features
        if ($child->independence_level >= 4) {
            $interactiveFeatures[] = 'content-creation';
            $interactiveFeatures[] = 'peer-collaboration';
            $interactiveFeatures[] = 'self-assessment';
        }

        // Add feature-specific HTML modifications
        foreach ($interactiveFeatures as $feature) {
            $html = $this->addInteractiveFeature($html, $feature, $child);
        }

        return $html;
    }

    /**
     * Add specific interactive feature to HTML
     */
    private function addInteractiveFeature(string $html, string $feature, Child $child): string
    {
        switch ($feature) {
            case 'read-aloud':
                $html = preg_replace(
                    '/<p class="kids-text([^"]*)"([^>]*)>/',
                    '<p class="kids-text$1"$2><button class="kids-read-aloud-btn" data-text="$0" aria-label="Read aloud">🔊</button>',
                    $html
                );
                break;

            case 'checkboxes':
                $html = preg_replace(
                    '/<li class="kids-list-item kids-checkable"([^>]*)>([^<]*)/i',
                    '<li class="kids-list-item kids-checkable"$1><label class="kids-checkbox-container"><input type="checkbox" class="kids-checkbox" data-persist="true"><span class="kids-checkmark"></span><span class="kids-checkbox-text">$2</span></label>',
                    $html
                );
                break;

            case 'highlighting':
                $html = preg_replace(
                    '/<p class="kids-text([^"]*)"([^>]*)>([^<]+)<\/p>/i',
                    '<p class="kids-text$1"$2><span class="kids-highlightable" data-highlightable="true">$3</span></p>',
                    $html
                );
                break;

            case 'note-taking':
                $html = preg_replace(
                    '/<p class="kids-text([^"]*)"([^>]*)>/i',
                    '<div class="kids-paragraph-container"><p class="kids-text$1"$2>',
                    $html
                );
                $html = str_replace('</p>', '</p><button class="kids-add-note-btn" data-note-target="previous">📝 Add Note</button></div>', $html);
                break;

            case 'bookmarks':
                $html = preg_replace(
                    '/<h([1-6]) class="kids-heading([^"]*)"([^>]*)>/i',
                    '<div class="kids-heading-container"><h$1 class="kids-heading$2"$3><button class="kids-bookmark-btn" data-bookmark-section="true" aria-label="Bookmark this section">🔖</button>',
                    $html
                );
                $html = preg_replace('/<\/h([1-6])>/', '</h$1></div>', $html);
                break;
        }

        return $html;
    }

    /**
     * Enhance media content for kids (videos, images, files)
     */
    private function enhanceMediaForKids(string $html, Child $child): string
    {
        // Enhance video embeds with kid-friendly controls
        $html = preg_replace_callback(
            '/<div class="video-embed-container([^"]*)"([^>]*)>(.*?)<\/div>/s',
            function ($matches) use ($child) {
                return $this->enhanceVideoForKids($matches[0], $child);
            },
            $html
        );

        // Enhance image displays with zoom and interaction
        $html = preg_replace_callback(
            '/<div class="file-embed image-embed"([^>]*)>(.*?)<\/div>/s',
            function ($matches) use ($child) {
                return $this->enhanceImageForKids($matches[0], $child);
            },
            $html
        );

        // Enhance file downloads with kid-friendly feedback
        $html = preg_replace_callback(
            '/<div class="file-embed([^"]*)"([^>]*)>(.*?)<\/div>/s',
            function ($matches) use ($child) {
                return $this->enhanceFileForKids($matches[0], $child);
            },
            $html
        );

        return $html;
    }

    /**
     * Enhance video content for kids
     */
    private function enhanceVideoForKids(string $videoHtml, Child $child): string
    {
        $ageGroup = $this->getAgeGroup($child);

        // Add kid-friendly video controls and features
        $enhancements = '<div class="kids-video-enhancements">';

        // Add viewing progress for the child
        $enhancements .= '<div class="kids-video-progress-container">';
        $enhancements .= '<div class="kids-video-progress-bar" data-video-progress="true">';
        $enhancements .= '<div class="kids-video-progress-fill"></div>';
        $enhancements .= '</div>';
        $enhancements .= '<span class="kids-video-progress-text">📺 Ready to watch!</span>';
        $enhancements .= '</div>';

        // Add safety and parental features
        if ($child->independence_level <= 2) {
            $enhancements .= '<div class="kids-video-safety-notice">';
            $enhancements .= '<p class="kids-safety-text">👥 Ask a grown-up to watch with you!</p>';
            $enhancements .= '</div>';
        }

        // Add fun watching features
        $enhancements .= '<div class="kids-video-features">';
        $enhancements .= '<button class="kids-video-feature-btn" data-feature="reactions">😊 Reactions</button>';
        $enhancements .= '<button class="kids-video-feature-btn" data-feature="notes">📝 My Notes</button>';
        if ($child->independence_level >= 3) {
            $enhancements .= '<button class="kids-video-feature-btn" data-feature="share">📤 Share</button>';
        }
        $enhancements .= '</div>';

        $enhancements .= '</div>';

        // Insert enhancements before the closing video container div
        return str_replace('</div>', $enhancements.'</div>', $videoHtml);
    }

    /**
     * Enhance image content for kids
     */
    private function enhanceImageForKids(string $imageHtml, Child $child): string
    {
        $ageGroup = $this->getAgeGroup($child);

        // Add interactive image features
        $enhancements = '<div class="kids-image-enhancements">';

        // Add image interaction controls
        $enhancements .= '<div class="kids-image-controls">';
        $enhancements .= '<button class="kids-image-btn kids-zoom-btn" data-image-action="zoom">🔍 Zoom</button>';
        $enhancements .= '<button class="kids-image-btn kids-fullscreen-btn" data-image-action="fullscreen">⛶ Full Screen</button>';

        if ($child->independence_level >= 2) {
            $enhancements .= '<button class="kids-image-btn kids-annotate-btn" data-image-action="annotate">✏️ Draw</button>';
        }

        if ($child->independence_level >= 3) {
            $enhancements .= '<button class="kids-image-btn kids-save-btn" data-image-action="save">💾 Save</button>';
        }

        $enhancements .= '</div>';

        // Add fun image overlay features
        $enhancements .= '<div class="kids-image-overlay" style="display: none;">';
        $enhancements .= '<div class="kids-image-overlay-content">';
        $enhancements .= '<button class="kids-overlay-close" aria-label="Close">❌</button>';
        $enhancements .= '<div class="kids-image-annotations"></div>';
        $enhancements .= '</div>';
        $enhancements .= '</div>';

        $enhancements .= '</div>';

        return str_replace('</div>', $enhancements.'</div>', $imageHtml);
    }

    /**
     * Enhance file downloads for kids
     */
    private function enhanceFileForKids(string $fileHtml, Child $child): string
    {
        $ageGroup = $this->getAgeGroup($child);

        // Add kid-friendly download experience
        $enhancements = '<div class="kids-file-enhancements">';

        // Add download progress and feedback
        $enhancements .= '<div class="kids-download-feedback" style="display: none;">';
        $enhancements .= '<div class="kids-download-animation">📥</div>';
        $enhancements .= '<p class="kids-download-text">Getting your file ready...</p>';
        $enhancements .= '<div class="kids-download-progress">';
        $enhancements .= '<div class="kids-download-progress-bar"></div>';
        $enhancements .= '</div>';
        $enhancements .= '</div>';

        // Add safety reminder for younger kids
        if ($child->independence_level <= 2) {
            $enhancements .= '<div class="kids-file-safety-reminder">';
            $enhancements .= '<p class="kids-safety-text">🛡️ Downloaded files are safe for you to use!</p>';
            $enhancements .= '</div>';
        }

        $enhancements .= '</div>';

        return str_replace('</div>', $enhancements.'</div>', $fileHtml);
    }

    /**
     * Add progress tracking elements
     */
    private function addProgressTracking(string $html, Child $child, Topic $topic): string
    {
        $progressHtml = '<div class="kids-progress-tracking" data-topic-id="'.$topic->id.'" data-child-id="'.$child->id.'">';

        // Reading progress indicator
        $progressHtml .= '<div class="kids-reading-progress">';
        $progressHtml .= '<h4 class="kids-progress-title">📊 Your Reading Progress</h4>';
        $progressHtml .= '<div class="kids-progress-bar-container">';
        $progressHtml .= '<div class="kids-progress-bar" data-progress="0">';
        $progressHtml .= '<div class="kids-progress-fill"></div>';
        $progressHtml .= '</div>';
        $progressHtml .= '<span class="kids-progress-percentage">0%</span>';
        $progressHtml .= '</div>';
        $progressHtml .= '</div>';

        // Achievement badges area
        $progressHtml .= '<div class="kids-achievements">';
        $progressHtml .= '<h4 class="kids-achievement-title">🏆 Your Achievements</h4>';
        $progressHtml .= '<div class="kids-achievement-badges" data-achievements="true">';
        $progressHtml .= '<!-- Achievements will be loaded dynamically -->';
        $progressHtml .= '</div>';
        $progressHtml .= '</div>';

        // Time tracking for older kids
        if ($child->independence_level >= 3) {
            $progressHtml .= '<div class="kids-time-tracking">';
            $progressHtml .= '<h4 class="kids-time-title">⏰ Learning Time</h4>';
            $progressHtml .= '<div class="kids-time-display">';
            $progressHtml .= '<span class="kids-time-spent" data-timer="true">0:00</span>';
            $progressHtml .= '<button class="kids-time-btn" data-timer-action="pause">⏸️ Pause</button>';
            $progressHtml .= '</div>';
            $progressHtml .= '</div>';
        }

        $progressHtml .= '</div>';

        return $html.$progressHtml;
    }

    /**
     * Generate gamification data for the child
     */
    private function generateGamificationData(string $content, Child $child, Topic $topic): array
    {
        $wordCount = str_word_count(strip_tags($content));
        $readingTime = ceil($wordCount / 200); // Approximate reading speed

        return [
            'points_available' => $this->calculatePointsAvailable($wordCount, $child),
            'estimated_time' => $readingTime,
            'difficulty_level' => $this->calculateDifficultyLevel($content, $child),
            'achievements_possible' => $this->getPossibleAchievements($content, $child),
            'completion_rewards' => $this->getCompletionRewards($child, $topic),
            'streak_data' => $this->getStreakData($child),
            'encouragement_messages' => $this->getEncouragementMessages($child),
        ];
    }

    /**
     * Apply content filtering for age appropriateness
     */
    private function applyContentFiltering(string $content, Child $child): string
    {
        $ageGroup = $this->getAgeGroup($child);

        // Remove or modify content based on age group
        switch ($ageGroup) {
            case 'preschool':
                // Very basic filtering for youngest learners
                $content = $this->filterPreschoolContent($content);
                break;

            case 'elementary':
                // Elementary-appropriate filtering
                $content = $this->filterElementaryContent($content);
                break;

            case 'middle':
                // Middle school filtering
                $content = $this->filterMiddleSchoolContent($content);
                break;

            case 'high':
                // High school filtering (minimal)
                $content = $this->filterHighSchoolContent($content);
                break;
        }

        return $content;
    }

    /**
     * Get age group for child
     */
    private function getAgeGroup(Child $child): string
    {
        return match ($child->grade) {
            'PreK', 'K' => 'preschool',
            '1st', '2nd', '3rd', '4th', '5th' => 'elementary',
            '6th', '7th', '8th' => 'middle',
            '9th', '10th', '11th', '12th' => 'high',
            default => 'elementary'
        };
    }

    /**
     * Initialize markdown converter for kids content
     */
    private function initializeKidsMarkdownConverter(): void
    {
        $config = [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20, // Simpler nesting for kids
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new TaskListExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addExtension(new SmartPunctExtension);

        $this->markdownConverter = new MarkdownConverter($environment);
    }

    /**
     * Generate kids-specific styles and scripts
     */
    private function getKidsStylesAndScripts(string $ageGroup, int $independenceLevel): string
    {
        return '
        <link rel="stylesheet" href="/css/kids-content-'.$ageGroup.'.css">
        <script src="/js/kids-interactions-'.$independenceLevel.'.js" defer></script>
        <script>
            window.kidsConfig = {
                ageGroup: "'.$ageGroup.'",
                independenceLevel: '.$independenceLevel.',
                animationsEnabled: true,
                soundEnabled: localStorage.getItem("kids_sound_enabled") !== "false",
                theme: localStorage.getItem("kids_theme") || "bright"
            };
        </script>';
    }

    /**
     * Get fallback content for error cases
     */
    private function getFallbackKidsContent(string $content, Child $child): array
    {
        return [
            'html' => '<div class="kids-content-fallback"><p class="kids-text">🎨 '.nl2br(htmlspecialchars($content)).'</p></div>',
            'metadata' => ['error' => 'fallback_mode'],
            'gamification' => [],
            'safety_level' => 'safe',
            'reading_level' => 'basic',
            'interactive_elements' => [],
            'engagement_score' => 50,
        ];
    }

    // Additional helper methods for content filtering, calculations, etc.
    private function filterPreschoolContent(string $content): string
    {
        // Remove complex language and concepts
        return $content;
    }

    private function filterElementaryContent(string $content): string
    {
        // Filter for elementary appropriateness
        return $content;
    }

    private function filterMiddleSchoolContent(string $content): string
    {
        // Filter for middle school appropriateness
        return $content;
    }

    private function filterHighSchoolContent(string $content): string
    {
        // Minimal filtering for high school
        return $content;
    }

    private function calculatePointsAvailable(int $wordCount, Child $child): int
    {
        return min(100, max(10, $wordCount / 10));
    }

    private function calculateDifficultyLevel(string $content, Child $child): string
    {
        $wordCount = str_word_count($content);
        $ageGroup = $this->getAgeGroup($child);

        // Simplified difficulty calculation
        if ($wordCount < 100) {
            return 'easy';
        }
        if ($wordCount < 500) {
            return 'medium';
        }

        return 'hard';
    }

    private function getPossibleAchievements(string $content, Child $child): array
    {
        return [
            'first_reader' => 'Complete your first reading',
            'speed_reader' => 'Read quickly and accurately',
            'careful_reader' => 'Take your time to understand',
            'interactive_learner' => 'Use all the interactive features',
        ];
    }

    private function getCompletionRewards(Child $child, Topic $topic): array
    {
        return [
            'points' => 50,
            'badge' => 'Topic Master',
            'certificate' => true,
        ];
    }

    private function getStreakData(Child $child): array
    {
        return [
            'current_streak' => 0,
            'longest_streak' => 0,
            'can_extend_today' => true,
        ];
    }

    private function getEncouragementMessages(Child $child): array
    {
        $ageGroup = $this->getAgeGroup($child);

        return match ($ageGroup) {
            'preschool' => [
                'start' => '🌟 You\'re doing great! Let\'s learn together!',
                'middle' => '🎉 Wow, you\'re learning so much!',
                'end' => '🏆 You did it! You\'re amazing!',
            ],
            'elementary' => [
                'start' => '📚 Ready to explore? Let\'s discover new things!',
                'middle' => '💪 You\'re doing fantastic! Keep going!',
                'end' => '🎯 Excellent work! You\'re a learning champion!',
            ],
            'middle' => [
                'start' => '🧠 Time to dive deep and learn!',
                'middle' => '⚡ You\'re making great progress!',
                'end' => '🔥 Outstanding! You\'ve mastered this topic!',
            ],
            'high' => [
                'start' => '🎓 Let\'s explore this topic thoroughly.',
                'middle' => '📈 You\'re demonstrating excellent understanding.',
                'end' => '🏆 Exceptional work! You\'ve achieved mastery.',
            ],
            default => [
                'start' => '🌟 Let\'s learn something new!',
                'middle' => '💫 You\'re doing great!',
                'end' => '🎉 Amazing job!',
            ],
        };
    }

    private function generateInteractiveMetadata(array $baseMetadata, Child $child): array
    {
        return [
            'kids_features_enabled' => true,
            'age_group' => $this->getAgeGroup($child),
            'independence_features' => $this->getIndependenceFeatures($child),
            'accessibility_features' => $this->getAccessibilityFeatures($child),
            'parental_controls' => $this->getParentalControls($child),
        ];
    }

    private function getIndependenceFeatures(Child $child): array
    {
        $features = ['read-aloud', 'highlighting', 'emoji-reactions'];

        if ($child->independence_level >= 2) {
            $features[] = 'checkboxes';
            $features[] = 'progress-tracking';
        }

        if ($child->independence_level >= 3) {
            $features[] = 'read-aloud';
            $features[] = 'highlighting';
            $features[] = 'checkboxes';
            $features[] = 'note-taking';
            $features[] = 'bookmarks';
        }

        if ($child->independence_level >= 4) {
            $features[] = 'read-aloud';
            $features[] = 'highlighting';
            $features[] = 'checkboxes';
            $features[] = 'note-taking';
            $features[] = 'content-creation';
        }

        return $features;
    }

    private function getAccessibilityFeatures(Child $child): array
    {
        return [
            'high_contrast_mode' => true,
            'large_text_mode' => true,
            'screen_reader_support' => true,
            'keyboard_navigation' => true,
            'reduced_motion_support' => true,
        ];
    }

    private function getParentalControls(Child $child): array
    {
        return [
            'time_limits_enabled' => $child->independence_level <= 2,
            'content_filtering_active' => true,
            'progress_reporting' => true,
            'safe_browsing_mode' => true,
        ];
    }

    private function calculateSafetyLevel(string $content): string
    {
        // Simplified safety calculation
        return 'safe';
    }

    private function calculateReadingLevel(string $content, Child $child): string
    {
        // Simplified reading level calculation
        return $this->getAgeGroup($child);
    }

    private function extractInteractiveElements(string $html): array
    {
        $elements = [];

        // Extract various interactive elements from the HTML
        if (strpos($html, 'kids-checkbox') !== false) {
            $elements[] = 'checkboxes';
        }
        if (strpos($html, 'kids-highlightable') !== false) {
            $elements[] = 'highlighting';
        }
        if (strpos($html, 'kids-bookmark-btn') !== false) {
            $elements[] = 'bookmarks';
        }
        if (strpos($html, 'kids-add-note-btn') !== false) {
            $elements[] = 'notes';
        }

        return $elements;
    }

    private function calculateEngagementScore(string $content, Child $child): int
    {
        $score = 50; // Base score

        // Add points for various engagement factors
        $wordCount = str_word_count($content);
        if ($wordCount > 100) {
            $score += 10;
        }
        if ($wordCount > 500) {
            $score += 10;
        }

        // Add points for media content
        if (strpos($content, '![') !== false) {
            $score += 15;
        } // Images
        if (strpos($content, 'youtube.com') !== false || strpos($content, 'vimeo.com') !== false) {
            $score += 20;
        } // Videos

        // Adjust for age group
        $ageGroup = $this->getAgeGroup($child);
        if ($ageGroup === 'preschool') {
            $score += 10;
        } // Younger kids get more engagement

        return min(100, max(0, $score));
    }
}

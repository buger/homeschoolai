<?php

namespace App\Services;

use App\Models\Child;
use Illuminate\Support\Facades\Log;

/**
 * Security Service for Kids Content
 *
 * Provides content filtering, safety checks, and security measures
 * specifically designed for protecting child users.
 */
class SecurityService
{
    private const BLOCKED_WORDS = [
        // Add age-inappropriate words - keeping this minimal for demonstration
        'inappropriate',
        'unsafe',
        'dangerous',
    ];

    private const BLOCKED_DOMAINS = [
        'example-unsafe-domain.com',
        'blocked-site.net',
        // Add specific domains to block
    ];

    private const SAFE_DOMAINS = [
        'youtube.com',
        'vimeo.com',
        'khan-academy.org',
        'khanacademy.org',
        'coursera.org',
        'edx.org',
        'mit.edu',
        'stanford.edu',
        'harvard.edu',
        'wikipedia.org',
        'britannica.com',
        'nationalgeographic.com',
        'smithsonianmag.com',
        'nasa.gov',
        'google.com',
        'microsoft.com',
        'apple.com',
    ];

    private const SAFE_FILE_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'txt', 'rtf',
        'jpg', 'jpeg', 'png', 'gif', 'svg',
        'mp4', 'mov', 'avi', 'wmv', 'flv',
        'mp3', 'wav', 'ogg', 'm4a',
        'ppt', 'pptx', 'xls', 'xlsx',
        'zip', 'rar', '7z',
    ];

    private const AGE_COMPLEXITY_LIMITS = [
        'preschool' => [
            'max_words' => 200,
            'max_links' => 3,
            'max_media' => 2,
            'reading_level' => 'basic',
        ],
        'elementary' => [
            'max_words' => 800,
            'max_links' => 8,
            'max_media' => 5,
            'reading_level' => 'elementary',
        ],
        'middle' => [
            'max_words' => 1500,
            'max_links' => 15,
            'max_media' => 8,
            'reading_level' => 'intermediate',
        ],
        'high' => [
            'max_words' => 3000,
            'max_links' => 25,
            'max_media' => 12,
            'reading_level' => 'advanced',
        ],
    ];

    /**
     * Filter content for age appropriateness
     */
    public function filterContent(string $content, Child $child): array
    {
        $result = [
            'filtered_content' => $content,
            'safety_warnings' => [],
            'modifications_made' => [],
            'safety_level' => 'safe',
        ];

        try {
            $ageGroup = $this->getAgeGroup($child);

            // 1. Check for blocked words
            $wordFilterResult = $this->filterBlockedWords($content);
            if ($wordFilterResult['modified']) {
                $result['filtered_content'] = $wordFilterResult['content'];
                $result['modifications_made'][] = 'Removed inappropriate language';
                $result['safety_warnings'][] = 'Some words were filtered for safety';
            }

            // 2. Check and filter URLs
            $urlFilterResult = $this->filterUrls($result['filtered_content'], $ageGroup);
            if ($urlFilterResult['modified']) {
                $result['filtered_content'] = $urlFilterResult['content'];
                $result['modifications_made'] = array_merge($result['modifications_made'], $urlFilterResult['modifications']);
                $result['safety_warnings'] = array_merge($result['safety_warnings'], $urlFilterResult['warnings']);
            }

            // 3. Check content complexity
            $complexityResult = $this->checkContentComplexity($result['filtered_content'], $ageGroup);
            if (! $complexityResult['appropriate']) {
                $result['safety_warnings'] = array_merge($result['safety_warnings'], $complexityResult['warnings']);
                $result['safety_level'] = 'caution';
            }

            // 4. Check for potential safety issues
            $safetyResult = $this->checkSafetyIssues($result['filtered_content'], $child);
            if (! empty($safetyResult['issues'])) {
                $result['safety_warnings'] = array_merge($result['safety_warnings'], $safetyResult['issues']);
                $result['safety_level'] = $safetyResult['level'];
            }

            // 5. Apply age-appropriate modifications
            $ageFilterResult = $this->applyAgeAppropriateFiltering($result['filtered_content'], $ageGroup);
            $result['filtered_content'] = $ageFilterResult['content'];
            if (! empty($ageFilterResult['modifications'])) {
                $result['modifications_made'] = array_merge($result['modifications_made'], $ageFilterResult['modifications']);
            }

        } catch (\Exception $e) {
            Log::error('Content filtering error', [
                'child_id' => $child->id,
                'error' => $e->getMessage(),
            ]);

            $result['safety_warnings'][] = 'Content could not be fully verified for safety';
            $result['safety_level'] = 'unknown';
        }

        return $result;
    }

    /**
     * Check if a URL is safe for children
     */
    public function isUrlSafe(string $url, string $ageGroup = 'elementary'): array
    {
        $result = [
            'safe' => true,
            'reason' => '',
            'recommendations' => [],
        ];

        try {
            $parsedUrl = parse_url($url);
            if (! $parsedUrl || ! isset($parsedUrl['host'])) {
                return [
                    'safe' => false,
                    'reason' => 'Invalid URL format',
                    'recommendations' => ['Please provide a valid web address'],
                ];
            }

            $domain = strtolower($parsedUrl['host']);
            $domain = preg_replace('/^www\./', '', $domain); // Remove www

            // Check against blocked domains
            foreach (self::BLOCKED_DOMAINS as $blockedDomain) {
                if (strpos($domain, $blockedDomain) !== false) {
                    return [
                        'safe' => false,
                        'reason' => 'Domain is blocked for safety',
                        'recommendations' => ['This website is not appropriate for children'],
                    ];
                }
            }

            // Check against safe domains
            $isSafeDomain = false;
            foreach (self::SAFE_DOMAINS as $safeDomain) {
                if (strpos($domain, $safeDomain) !== false) {
                    $isSafeDomain = true;
                    break;
                }
            }

            if (! $isSafeDomain) {
                $result['safe'] = false;
                $result['reason'] = 'Domain not in safe list';
                $result['recommendations'][] = 'Adult supervision recommended';

                // Additional checks for unknown domains
                if ($ageGroup === 'preschool' || $ageGroup === 'elementary') {
                    $result['recommendations'][] = 'Please ask a grown-up to check this website first';
                }
            }

            // Check for suspicious URL patterns
            $suspiciousPatterns = [
                '/download\?/',
                '/\.exe$/',
                '/\.zip$/',
                '/\.rar$/',
                '/adult/',
                '/mature/',
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $url)) {
                    $result['safe'] = false;
                    $result['reason'] = 'URL contains suspicious patterns';
                    $result['recommendations'][] = 'This link may not be safe for children';
                    break;
                }
            }

        } catch (\Exception $e) {
            Log::warning('URL safety check failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'safe' => false,
                'reason' => 'Could not verify URL safety',
                'recommendations' => ['Adult supervision recommended for unknown links'],
            ];
        }

        return $result;
    }

    /**
     * Check if a file is safe for children
     */
    public function isFileSafe(string $filename, int $fileSize = 0): array
    {
        $result = [
            'safe' => true,
            'reason' => '',
            'recommendations' => [],
        ];

        try {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Check file extension
            if (! in_array($extension, self::SAFE_FILE_EXTENSIONS)) {
                return [
                    'safe' => false,
                    'reason' => 'File type not allowed',
                    'recommendations' => ['Only safe file types are permitted'],
                ];
            }

            // Check file size (200MB limit)
            if ($fileSize > 200 * 1024 * 1024) {
                return [
                    'safe' => false,
                    'reason' => 'File too large',
                    'recommendations' => ['Files must be smaller than 200MB'],
                ];
            }

            // Check filename for suspicious patterns
            $suspiciousPatterns = [
                '/virus/',
                '/hack/',
                '/crack/',
                '/keygen/',
                '/adult/',
                '/mature/',
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, strtolower($filename))) {
                    $result['safe'] = false;
                    $result['reason'] = 'Filename contains suspicious content';
                    $result['recommendations'][] = 'File name suggests inappropriate content';
                    break;
                }
            }

        } catch (\Exception $e) {
            Log::warning('File safety check failed', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return [
                'safe' => false,
                'reason' => 'Could not verify file safety',
                'recommendations' => ['File could not be verified as safe'],
            ];
        }

        return $result;
    }

    /**
     * Get time-based restrictions for child
     */
    public function getTimeRestrictions(Child $child): array
    {
        $ageGroup = $this->getAgeGroup($child);
        $currentHour = (int) date('H');

        $restrictions = [
            'allowed' => true,
            'reason' => '',
            'recommendations' => [],
        ];

        // Age-based time restrictions
        switch ($ageGroup) {
            case 'preschool':
                if ($currentHour < 8 || $currentHour > 18) {
                    $restrictions['allowed'] = false;
                    $restrictions['reason'] = 'Outside recommended learning hours';
                    $restrictions['recommendations'][] = 'Learning time is 8 AM to 6 PM for young learners';
                }
                break;

            case 'elementary':
                if ($currentHour < 7 || $currentHour > 20) {
                    $restrictions['allowed'] = false;
                    $restrictions['reason'] = 'Outside recommended learning hours';
                    $restrictions['recommendations'][] = 'Learning time is 7 AM to 8 PM for elementary students';
                }
                break;

            case 'middle':
            case 'high':
                if ($currentHour < 6 || $currentHour > 22) {
                    $restrictions['allowed'] = false;
                    $restrictions['reason'] = 'Outside recommended learning hours';
                    $restrictions['recommendations'][] = 'Consider taking a break outside of study hours';
                }
                break;
        }

        return $restrictions;
    }

    // Private helper methods

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

    private function filterBlockedWords(string $content): array
    {
        $originalContent = $content;
        $modifications = 0;

        foreach (self::BLOCKED_WORDS as $blockedWord) {
            $pattern = '/\b'.preg_quote($blockedWord, '/').'\b/i';
            $replacement = str_repeat('*', strlen($blockedWord));

            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modifications++;
            }
        }

        return [
            'content' => $content,
            'modified' => $modifications > 0,
            'modifications_count' => $modifications,
        ];
    }

    private function filterUrls(string $content, string $ageGroup): array
    {
        $result = [
            'content' => $content,
            'modified' => false,
            'modifications' => [],
            'warnings' => [],
        ];

        // Find all URLs in markdown format [text](url)
        $pattern = '/\[([^\]]*)\]\(([^)]+)\)/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$result, $ageGroup) {
            $linkText = $matches[1];
            $url = $matches[2];

            $urlSafety = $this->isUrlSafe($url, $ageGroup);

            if (! $urlSafety['safe']) {
                $result['modified'] = true;
                $result['modifications'][] = "Removed unsafe link: {$linkText}";
                $result['warnings'][] = "Link '{$linkText}' was removed for safety: {$urlSafety['reason']}";

                // Replace with safe text
                return "**{$linkText}** (link removed for safety)";
            }

            return $matches[0]; // Return original if safe
        }, $content);

        $result['content'] = $content;

        return $result;
    }

    private function checkContentComplexity(string $content, string $ageGroup): array
    {
        $limits = self::AGE_COMPLEXITY_LIMITS[$ageGroup] ?? self::AGE_COMPLEXITY_LIMITS['elementary'];

        $wordCount = str_word_count(strip_tags($content));
        $linkCount = substr_count($content, '](');
        $mediaCount = substr_count($content, '![') + substr_count($content, 'youtube.com') + substr_count($content, 'vimeo.com');

        $warnings = [];
        $appropriate = true;

        if ($wordCount > $limits['max_words']) {
            $warnings[] = "Content is quite long ({$wordCount} words). Consider taking breaks.";
            if ($wordCount > $limits['max_words'] * 1.5) {
                $appropriate = false;
            }
        }

        if ($linkCount > $limits['max_links']) {
            $warnings[] = "Many links to explore ({$linkCount}). Take your time with each one.";
        }

        if ($mediaCount > $limits['max_media']) {
            $warnings[] = "Lots of videos and images ({$mediaCount}). Enjoy exploring them!";
        }

        return [
            'appropriate' => $appropriate,
            'warnings' => $warnings,
            'metrics' => [
                'word_count' => $wordCount,
                'link_count' => $linkCount,
                'media_count' => $mediaCount,
            ],
        ];
    }

    private function checkSafetyIssues(string $content, Child $child): array
    {
        $issues = [];
        $level = 'safe';

        // Check for potential safety keywords
        $cautionKeywords = [
            'experiment', 'chemical', 'fire', 'heat', 'sharp', 'cutting',
            'electricity', 'power', 'battery', 'tool', 'adult supervision',
        ];

        $warningKeywords = [
            'danger', 'warning', 'caution', 'risk', 'harmful', 'toxic',
        ];

        $contentLower = strtolower($content);

        foreach ($warningKeywords as $keyword) {
            if (strpos($contentLower, $keyword) !== false) {
                $issues[] = 'Content contains safety warnings. Please read carefully with an adult.';
                $level = 'warning';
                break;
            }
        }

        if ($level === 'safe') {
            foreach ($cautionKeywords as $keyword) {
                if (strpos($contentLower, $keyword) !== false) {
                    $issues[] = 'This content mentions activities that may need adult supervision.';
                    $level = 'caution';
                    break;
                }
            }
        }

        // Age-specific safety checks
        $ageGroup = $this->getAgeGroup($child);
        if ($ageGroup === 'preschool' && (strpos($contentLower, 'small parts') !== false || strpos($contentLower, 'choking') !== false)) {
            $issues[] = 'Content mentions small parts. Always have an adult nearby.';
            $level = 'warning';
        }

        return [
            'issues' => $issues,
            'level' => $level,
        ];
    }

    private function applyAgeAppropriateFiltering(string $content, string $ageGroup): array
    {
        $modifications = [];

        // Add age-appropriate notices for external links
        if (strpos($content, '](http') !== false) {
            switch ($ageGroup) {
                case 'preschool':
                    $content .= "\n\n> 👥 **Remember**: Ask a grown-up before clicking any links!";
                    $modifications[] = 'Added safety reminder for links';
                    break;

                case 'elementary':
                    $content .= "\n\n> 🛡️ **Safety tip**: Make sure a grown-up knows which websites you're visiting.";
                    $modifications[] = 'Added safety guidance for web browsing';
                    break;
            }
        }

        // Add time management hints for longer content
        $wordCount = str_word_count(strip_tags($content));
        if ($wordCount > 500) {
            switch ($ageGroup) {
                case 'preschool':
                    $content = "> ⏰ **Take your time**: This is a longer story. Feel free to take breaks!\n\n".$content;
                    $modifications[] = 'Added time management guidance';
                    break;

                case 'elementary':
                    $content = "> 📚 **Learning tip**: This is a longer reading. Take breaks if you need them!\n\n".$content;
                    $modifications[] = 'Added reading guidance';
                    break;
            }
        }

        return [
            'content' => $content,
            'modifications' => $modifications,
        ];
    }
}

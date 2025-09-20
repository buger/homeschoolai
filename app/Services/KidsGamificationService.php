<?php

namespace App\Services;

use App\Models\Child;
use App\Models\Topic;
use Illuminate\Support\Facades\Cache;

/**
 * Kids Gamification Service
 *
 * Handles achievement tracking, progress monitoring, and reward systems
 * specifically designed for child learners with age-appropriate mechanics.
 */
class KidsGamificationService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const ACHIEVEMENT_TYPES = [
        'first_reader' => [
            'name' => 'First Reader',
            'description' => 'Complete your first reading',
            'icon' => '📚',
            'points' => 10,
            'requirements' => ['read_complete' => 1],
        ],
        'speed_reader' => [
            'name' => 'Speed Reader',
            'description' => 'Read quickly and accurately',
            'icon' => '⚡',
            'points' => 25,
            'requirements' => ['read_time_under' => 300, 'accuracy' => 90],
        ],
        'careful_reader' => [
            'name' => 'Careful Reader',
            'description' => 'Take your time to understand',
            'icon' => '🔍',
            'points' => 20,
            'requirements' => ['read_time_over' => 600, 'completion' => 100],
        ],
        'interactive_learner' => [
            'name' => 'Interactive Learner',
            'description' => 'Use all the interactive features',
            'icon' => '🎮',
            'points' => 30,
            'requirements' => ['interactions' => 10, 'features_used' => 3],
        ],
        'task_master' => [
            'name' => 'Task Master',
            'description' => 'Complete all your tasks',
            'icon' => '✅',
            'points' => 35,
            'requirements' => ['tasks_completed' => 100],
        ],
        'highlight_hero' => [
            'name' => 'Highlight Hero',
            'description' => 'Master the art of highlighting',
            'icon' => '🖍️',
            'points' => 15,
            'requirements' => ['highlights' => 5],
        ],
        'time_keeper' => [
            'name' => 'Time Keeper',
            'description' => 'Focus for extended periods',
            'icon' => '⏰',
            'points' => 25,
            'requirements' => ['focus_time' => 1800], // 30 minutes
        ],
        'streak_starter' => [
            'name' => 'Streak Starter',
            'description' => 'Learn for 3 days in a row',
            'icon' => '🔥',
            'points' => 40,
            'requirements' => ['daily_streak' => 3],
        ],
        'explorer' => [
            'name' => 'Explorer',
            'description' => 'Discover new topics',
            'icon' => '🗺️',
            'points' => 20,
            'requirements' => ['topics_explored' => 5],
        ],
        'helper' => [
            'name' => 'Helper',
            'description' => 'Ask for help when needed',
            'icon' => '🙋',
            'points' => 15,
            'requirements' => ['help_requests' => 3],
        ],
    ];

    private const POINTS_SYSTEM = [
        'read_paragraph' => 2,
        'complete_task' => 5,
        'highlight_text' => 1,
        'use_feature' => 3,
        'ask_question' => 4,
        'complete_topic' => 20,
        'daily_login' => 10,
        'focus_15min' => 8,
        'focus_30min' => 15,
        'perfect_completion' => 25,
    ];

    private const LEVEL_THRESHOLDS = [
        1 => 0,     // Beginner
        2 => 50,    // Explorer
        3 => 150,   // Learner
        4 => 300,   // Scholar
        5 => 500,   // Expert
        6 => 750,   // Master
        7 => 1000,  // Champion
        8 => 1500,  // Legend
        9 => 2000,  // Hero
        10 => 3000,  // Genius
    ];

    public function __construct()
    {
        // Service initialization
    }

    /**
     * Calculate engagement score for content
     */
    public function calculateEngagementScore(string $content, Child $child): int
    {
        $score = 50; // Base score

        // Content factors
        $wordCount = str_word_count(strip_tags($content));
        if ($wordCount > 100) {
            $score += 10;
        }
        if ($wordCount > 500) {
            $score += 10;
        }

        // Media content bonus
        if (strpos($content, '![') !== false) {
            $score += 15;
        } // Images
        if (strpos($content, 'youtube.com') !== false || strpos($content, 'vimeo.com') !== false) {
            $score += 20; // Videos
        }

        // Interactive elements bonus
        if (strpos($content, '- [ ]') !== false) {
            $score += 10;
        } // Checkboxes
        if (strpos($content, '[') !== false && strpos($content, '](') !== false) {
            $score += 5; // Links
        }

        // Age group adjustments
        $ageGroup = $this->getAgeGroup($child);
        switch ($ageGroup) {
            case 'preschool':
                $score += 15; // Extra engagement for younger kids
                break;
            case 'elementary':
                $score += 10;
                break;
            case 'middle':
                $score += 5;
                break;
            case 'high':
                // No bonus - more sophisticated content expected
                break;
        }

        // Independence level adjustments
        $score += $child->independence_level * 5;

        return min(100, max(20, $score));
    }

    /**
     * Calculate difficulty level for content
     */
    public function calculateDifficultyLevel(string $content, Child $child): string
    {
        $factors = [
            'word_count' => str_word_count(strip_tags($content)),
            'sentence_count' => substr_count($content, '.') + substr_count($content, '!') + substr_count($content, '?'),
            'link_count' => substr_count($content, ']('),
            'media_count' => substr_count($content, '![') + substr_count($content, 'youtube.com') + substr_count($content, 'vimeo.com'),
        ];

        $complexity = 0;

        // Word count complexity
        if ($factors['word_count'] > 500) {
            $complexity += 3;
        } elseif ($factors['word_count'] > 200) {
            $complexity += 2;
        } elseif ($factors['word_count'] > 50) {
            $complexity += 1;
        }

        // Structure complexity
        if ($factors['sentence_count'] > 20) {
            $complexity += 2;
        } elseif ($factors['sentence_count'] > 10) {
            $complexity += 1;
        }

        // Interactive complexity
        if ($factors['link_count'] > 5) {
            $complexity += 2;
        }
        if ($factors['media_count'] > 3) {
            $complexity += 1;
        }

        // Age-appropriate adjustments
        $ageGroup = $this->getAgeGroup($child);
        switch ($ageGroup) {
            case 'preschool':
                if ($complexity <= 2) {
                    return 'easy';
                }
                if ($complexity <= 4) {
                    return 'medium';
                }

                return 'hard';

            case 'elementary':
                if ($complexity <= 3) {
                    return 'easy';
                }
                if ($complexity <= 6) {
                    return 'medium';
                }

                return 'hard';

            case 'middle':
                if ($complexity <= 4) {
                    return 'easy';
                }
                if ($complexity <= 7) {
                    return 'medium';
                }

                return 'hard';

            case 'high':
                if ($complexity <= 5) {
                    return 'easy';
                }
                if ($complexity <= 8) {
                    return 'medium';
                }

                return 'hard';
        }

        return 'medium';
    }

    /**
     * Track learning activity and award points
     */
    public function trackActivity(Child $child, string $activity, array $data = []): array
    {
        $cacheKey = "kids_activity_{$child->id}";
        $activities = Cache::get($cacheKey, []);

        $activityData = [
            'type' => $activity,
            'timestamp' => now()->toISOString(),
            'data' => $data,
            'points' => $this->calculateActivityPoints($activity, $data),
        ];

        $activities[] = $activityData;

        // Keep only last 100 activities
        if (count($activities) > 100) {
            $activities = array_slice($activities, -100);
        }

        Cache::put($cacheKey, $activities, self::CACHE_TTL);

        // Check for achievements
        $newAchievements = $this->checkAchievements($child, $activities);

        // Update child's total points
        $this->updateChildPoints($child, $activityData['points']);

        return [
            'points_earned' => $activityData['points'],
            'new_achievements' => $newAchievements,
            'total_points' => $this->getChildTotalPoints($child),
            'current_level' => $this->getChildLevel($child),
        ];
    }

    /**
     * Get current learning streak for child
     */
    public function getStreak(Child $child): array
    {
        $cacheKey = "kids_streak_{$child->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($child) {
            $activities = Cache::get("kids_activity_{$child->id}", []);

            $dailyActivity = [];
            foreach ($activities as $activity) {
                $date = date('Y-m-d', strtotime($activity['timestamp']));
                $dailyActivity[$date] = true;
            }

            $currentStreak = 0;
            $longestStreak = 0;
            $tempStreak = 0;

            $today = date('Y-m-d');
            $checkDate = $today;

            // Count current streak
            for ($i = 0; $i < 365; $i++) {
                if (isset($dailyActivity[$checkDate])) {
                    if ($checkDate === $today || $checkDate === date('Y-m-d', strtotime('-1 day'))) {
                        $currentStreak++;
                    }
                    $tempStreak++;
                } else {
                    if ($tempStreak > $longestStreak) {
                        $longestStreak = $tempStreak;
                    }
                    if ($checkDate === $today) {
                        $currentStreak = 0;
                    }
                    $tempStreak = 0;
                }

                $checkDate = date('Y-m-d', strtotime($checkDate.' -1 day'));
            }

            $longestStreak = max($longestStreak, $tempStreak);

            return [
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'can_extend_today' => ! isset($dailyActivity[$today]),
            ];
        });
    }

    /**
     * Get available achievements for child
     */
    public function getAvailableAchievements(Child $child): array
    {
        $earnedAchievements = $this->getEarnedAchievements($child);
        $earnedKeys = array_column($earnedAchievements, 'key');

        $available = [];
        foreach (self::ACHIEVEMENT_TYPES as $key => $achievement) {
            if (! in_array($key, $earnedKeys)) {
                $available[$key] = $achievement;
                $available[$key]['progress'] = $this->getAchievementProgress($child, $key);
            }
        }

        return $available;
    }

    /**
     * Get earned achievements for child
     */
    public function getEarnedAchievements(Child $child): array
    {
        $cacheKey = "kids_achievements_{$child->id}";

        return Cache::get($cacheKey, []);
    }

    /**
     * Get child's current level and progress
     */
    public function getChildLevel(Child $child): array
    {
        $totalPoints = $this->getChildTotalPoints($child);

        $currentLevel = 1;
        $nextLevel = 2;
        $currentLevelPoints = 0;
        $nextLevelPoints = self::LEVEL_THRESHOLDS[2];

        foreach (self::LEVEL_THRESHOLDS as $level => $threshold) {
            if ($totalPoints >= $threshold) {
                $currentLevel = $level;
                $currentLevelPoints = $threshold;

                if (isset(self::LEVEL_THRESHOLDS[$level + 1])) {
                    $nextLevel = $level + 1;
                    $nextLevelPoints = self::LEVEL_THRESHOLDS[$level + 1];
                }
            }
        }

        $pointsInLevel = $totalPoints - $currentLevelPoints;
        $pointsToNext = $nextLevelPoints - $currentLevelPoints;
        $progressPercent = $pointsToNext > 0 ? ($pointsInLevel / $pointsToNext) * 100 : 100;

        return [
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'total_points' => $totalPoints,
            'points_in_level' => $pointsInLevel,
            'points_to_next' => max(0, $nextLevelPoints - $totalPoints),
            'progress_percent' => min(100, $progressPercent),
            'level_name' => $this->getLevelName($currentLevel),
        ];
    }

    /**
     * Get encouragement messages based on child's progress
     */
    public function getEncouragementMessages(Child $child): array
    {
        $ageGroup = $this->getAgeGroup($child);
        $level = $this->getChildLevel($child);
        $streak = $this->getStreak($child);

        $messages = [
            'start' => $this->getStartMessage($ageGroup, $level, $streak),
            'middle' => $this->getMiddleMessage($ageGroup, $level, $streak),
            'end' => $this->getEndMessage($ageGroup, $level, $streak),
        ];

        return $messages;
    }

    /**
     * Get personalized learning recommendations
     */
    public function getPersonalizedRecommendations(Child $child, Topic $topic): array
    {
        $recommendations = [];

        $level = $this->getChildLevel($child);
        $ageGroup = $this->getAgeGroup($child);

        // Time recommendations
        $estimatedMinutes = $topic->estimated_minutes ?? 15;
        if ($ageGroup === 'preschool' && $estimatedMinutes > 15) {
            $recommendations[] = [
                'type' => 'time',
                'icon' => '⏰',
                'message' => 'This might take a while. Take breaks when you need them!',
            ];
        }

        // Independence recommendations
        if ($child->independence_level <= 2) {
            $recommendations[] = [
                'type' => 'help',
                'icon' => '👥',
                'message' => 'Ask a grown-up to learn with you!',
            ];
        }

        // Level-based recommendations
        if ($level['current_level'] <= 3) {
            $recommendations[] = [
                'type' => 'encouragement',
                'icon' => '🌟',
                'message' => 'You\'re doing great! Keep exploring and learning!',
            ];
        }

        return $recommendations;
    }

    /**
     * Generate learning session summary
     */
    public function generateSessionSummary(Child $child, array $sessionData): array
    {
        $summary = [
            'duration' => $sessionData['time_spent'] ?? 0,
            'reading_progress' => $sessionData['reading_progress'] ?? 0,
            'interactions' => $sessionData['total_interactions'] ?? 0,
            'tasks_completed' => $sessionData['tasks_completed'] ?? 0,
            'points_earned' => 0,
            'achievements' => [],
            'level_progress' => $this->getChildLevel($child),
        ];

        // Calculate points earned
        $summary['points_earned'] += ($sessionData['reading_progress'] ?? 0) >= 100 ? self::POINTS_SYSTEM['complete_topic'] : 0;
        $summary['points_earned'] += ($sessionData['tasks_completed'] ?? 0) * self::POINTS_SYSTEM['complete_task'];
        $summary['points_earned'] += min(10, ($sessionData['total_interactions'] ?? 0)) * self::POINTS_SYSTEM['use_feature'];

        // Time bonuses
        $duration = $sessionData['time_spent'] ?? 0;
        if ($duration >= 900) {
            $summary['points_earned'] += self::POINTS_SYSTEM['focus_15min'];
        }
        if ($duration >= 1800) {
            $summary['points_earned'] += self::POINTS_SYSTEM['focus_30min'];
        }

        return $summary;
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

    private function calculateActivityPoints(string $activity, array $data): int
    {
        $basePoints = self::POINTS_SYSTEM[$activity] ?? 1;

        // Apply multipliers based on data
        $multiplier = 1;

        if (isset($data['accuracy']) && $data['accuracy'] > 90) {
            $multiplier += 0.5;
        }

        if (isset($data['speed']) && $data['speed'] === 'fast') {
            $multiplier += 0.3;
        }

        if (isset($data['quality']) && $data['quality'] === 'high') {
            $multiplier += 0.4;
        }

        return (int) ($basePoints * $multiplier);
    }

    private function checkAchievements(Child $child, array $activities): array
    {
        $earnedAchievements = $this->getEarnedAchievements($child);
        $earnedKeys = array_column($earnedAchievements, 'key');
        $newAchievements = [];

        foreach (self::ACHIEVEMENT_TYPES as $key => $achievement) {
            if (in_array($key, $earnedKeys)) {
                continue;
            }

            if ($this->isAchievementEarned($child, $key, $activities)) {
                $newAchievement = $achievement;
                $newAchievement['key'] = $key;
                $newAchievement['earned_at'] = now()->toISOString();

                $newAchievements[] = $newAchievement;
                $earnedAchievements[] = $newAchievement;
            }
        }

        if (! empty($newAchievements)) {
            Cache::put("kids_achievements_{$child->id}", $earnedAchievements, self::CACHE_TTL);
        }

        return $newAchievements;
    }

    private function isAchievementEarned(Child $child, string $achievementKey, array $activities): bool
    {
        $achievement = self::ACHIEVEMENT_TYPES[$achievementKey];
        $requirements = $achievement['requirements'];

        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'read_complete':
                    $readCount = count(array_filter($activities, fn ($a) => $a['type'] === 'complete_topic'));
                    if ($readCount < $value) {
                        return false;
                    }
                    break;

                case 'interactions':
                    $interactionCount = array_sum(array_map(fn ($a) => $a['data']['interactions'] ?? 0, $activities));
                    if ($interactionCount < $value) {
                        return false;
                    }
                    break;

                case 'tasks_completed':
                    $taskProgress = array_sum(array_map(fn ($a) => $a['data']['tasks_completed'] ?? 0, $activities));
                    if ($taskProgress < $value) {
                        return false;
                    }
                    break;

                case 'highlights':
                    $highlightCount = count(array_filter($activities, fn ($a) => $a['type'] === 'highlight_text'));
                    if ($highlightCount < $value) {
                        return false;
                    }
                    break;

                case 'focus_time':
                    $totalFocus = array_sum(array_map(fn ($a) => $a['data']['time_spent'] ?? 0, $activities));
                    if ($totalFocus < $value) {
                        return false;
                    }
                    break;

                case 'daily_streak':
                    $streak = $this->getStreak($child);
                    if ($streak['current_streak'] < $value) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    private function getAchievementProgress(Child $child, string $achievementKey): array
    {
        $achievement = self::ACHIEVEMENT_TYPES[$achievementKey];
        $requirements = $achievement['requirements'];
        $activities = Cache::get("kids_activity_{$child->id}", []);

        $progress = [];

        foreach ($requirements as $requirement => $target) {
            $current = 0;

            switch ($requirement) {
                case 'read_complete':
                    $current = count(array_filter($activities, fn ($a) => $a['type'] === 'complete_topic'));
                    break;

                case 'interactions':
                    $current = array_sum(array_map(fn ($a) => $a['data']['interactions'] ?? 0, $activities));
                    break;

                case 'tasks_completed':
                    $current = array_sum(array_map(fn ($a) => $a['data']['tasks_completed'] ?? 0, $activities));
                    break;

                case 'highlights':
                    $current = count(array_filter($activities, fn ($a) => $a['type'] === 'highlight_text'));
                    break;

                case 'focus_time':
                    $current = array_sum(array_map(fn ($a) => $a['data']['time_spent'] ?? 0, $activities));
                    break;

                case 'daily_streak':
                    $streak = $this->getStreak($child);
                    $current = $streak['current_streak'];
                    break;
            }

            $progress[$requirement] = [
                'current' => $current,
                'target' => $target,
                'percent' => min(100, ($current / $target) * 100),
            ];
        }

        return $progress;
    }

    private function updateChildPoints(Child $child, int $points): void
    {
        $cacheKey = "kids_points_{$child->id}";
        $currentPoints = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $currentPoints + $points, self::CACHE_TTL);
    }

    private function getChildTotalPoints(Child $child): int
    {
        $cacheKey = "kids_points_{$child->id}";

        return Cache::get($cacheKey, 0);
    }

    private function getLevelName(int $level): string
    {
        $names = [
            1 => 'Beginner',
            2 => 'Explorer',
            3 => 'Learner',
            4 => 'Scholar',
            5 => 'Expert',
            6 => 'Master',
            7 => 'Champion',
            8 => 'Legend',
            9 => 'Hero',
            10 => 'Genius',
        ];

        return $names[$level] ?? 'Learning Star';
    }

    private function getStartMessage(string $ageGroup, array $level, array $streak): string
    {
        $messages = [
            'preschool' => [
                '🌟 Ready for a fun learning adventure!',
                '🎨 Let\'s explore and discover together!',
                '🦋 Time to learn something amazing!',
            ],
            'elementary' => [
                '📚 Ready to explore new knowledge!',
                '🔍 Let\'s discover something cool!',
                '💡 Time for an awesome learning session!',
            ],
            'middle' => [
                '🧠 Ready to expand your mind!',
                '⚡ Let\'s dive into new concepts!',
                '🎯 Time to master new skills!',
            ],
            'high' => [
                '🎓 Ready for academic excellence!',
                '📖 Let\'s explore advanced concepts!',
                '🏆 Time to achieve greatness!',
            ],
        ];

        $ageMessages = $messages[$ageGroup] ?? $messages['elementary'];

        // Add level/streak bonuses
        if ($level['current_level'] >= 5) {
            $ageMessages[] = "🌟 Level {$level['current_level']} superstar ready to learn!";
        }

        if ($streak['current_streak'] >= 3) {
            $ageMessages[] = "🔥 {$streak['current_streak']}-day streak hero!";
        }

        return $ageMessages[array_rand($ageMessages)];
    }

    private function getMiddleMessage(string $ageGroup, array $level, array $streak): string
    {
        $messages = [
            'preschool' => [
                '🎉 You\'re doing amazing!',
                '⭐ Keep up the great work!',
                '🌈 Learning is so much fun!',
            ],
            'elementary' => [
                '💪 You\'re making great progress!',
                '🚀 Keep exploring and learning!',
                '🌟 Fantastic job so far!',
            ],
            'middle' => [
                '⚡ Your brain is growing stronger!',
                '🔥 Excellent progress!',
                '💫 You\'re mastering new concepts!',
            ],
            'high' => [
                '📈 Outstanding academic progress!',
                '🎯 You\'re achieving excellence!',
                '💎 Brilliant work continues!',
            ],
        ];

        return $messages[$ageGroup][array_rand($messages[$ageGroup])];
    }

    private function getEndMessage(string $ageGroup, array $level, array $streak): string
    {
        $messages = [
            'preschool' => [
                '🏆 You did it! Super job!',
                '🎉 Amazing learning adventure!',
                '⭐ You\'re a learning star!',
            ],
            'elementary' => [
                '🎯 Excellent work completed!',
                '🏅 You\'re a learning champion!',
                '✨ Outstanding achievement!',
            ],
            'middle' => [
                '🔥 Exceptional work completed!',
                '🏆 You\'ve mastered new skills!',
                '⚡ Brilliant learning session!',
            ],
            'high' => [
                '🎓 Academic excellence achieved!',
                '🏆 Outstanding scholarly work!',
                '💎 Exceptional mastery demonstrated!',
            ],
        ];

        return $messages[$ageGroup][array_rand($messages[$ageGroup])];
    }
}

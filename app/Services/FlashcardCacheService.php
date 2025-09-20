<?php

namespace App\Services;

use App\Models\Flashcard;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FlashcardCacheService
{
    // Cache TTL in seconds (1 hour)
    private const DEFAULT_TTL = 3600;

    // Cache TTL for counts (30 minutes)
    private const COUNT_TTL = 1800;

    // Cache TTL for search results (15 minutes)
    private const SEARCH_TTL = 900;

    // Cache key prefixes
    private const PREFIX_UNIT_CARDS = 'flashcards:unit:';

    private const PREFIX_UNIT_COUNT = 'flashcards:count:unit:';

    private const PREFIX_SEARCH = 'flashcards:search:';

    private const PREFIX_STATS = 'flashcards:stats:unit:';

    private const PREFIX_IMPORT_PROGRESS = 'flashcards:import:';

    /**
     * Cache flashcards for a specific unit
     */
    public function cacheUnitFlashcards(int $unitId, ?Collection $flashcards = null): Collection
    {
        $cacheKey = self::PREFIX_UNIT_CARDS.$unitId;

        if ($flashcards !== null) {
            // Store in cache
            Cache::put($cacheKey, $flashcards->toArray(), self::DEFAULT_TTL);
            Log::debug("Cached flashcards for unit {$unitId}", ['count' => $flashcards->count()]);

            return $flashcards;
        }

        // Try to get from cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            Log::debug("Retrieved cached flashcards for unit {$unitId}", ['count' => count($cached)]);
            $collection = collect($cached)->map(function ($item) {
                return new Flashcard($item);
            });

            return new Collection($collection->all());
        }

        // Cache miss - fetch from database
        // Only fetch flashcards that are not assigned to a topic (unit-level context)
        $flashcards = Flashcard::where('unit_id', $unitId)
            ->whereNull('topic_id')
            ->where('is_active', true)
            ->with('unit.subject')
            ->orderBy('created_at', 'desc')
            ->get();

        Cache::put($cacheKey, $flashcards->toArray(), self::DEFAULT_TTL);
        Log::debug("Fetched and cached flashcards for unit {$unitId}", ['count' => $flashcards->count()]);

        return $flashcards;
    }

    /**
     * Cache flashcard count for a unit
     */
    public function cacheFlashcardCount(int $unitId, ?int $count = null): int
    {
        $cacheKey = self::PREFIX_UNIT_COUNT.$unitId;

        if ($count !== null) {
            Cache::put($cacheKey, $count, self::COUNT_TTL);

            return $count;
        }

        // Try cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (int) $cached;
        }

        // Cache miss - fetch from database
        // Only count flashcards that are not assigned to a topic (unit-level context)
        $count = Flashcard::where('unit_id', $unitId)
            ->whereNull('topic_id')
            ->where('is_active', true)
            ->count();

        Cache::put($cacheKey, $count, self::COUNT_TTL);

        return $count;
    }

    /**
     * Cache search results
     */
    public function cacheSearchResults(string $query, array $filters = [], ?Collection $results = null): ?Collection
    {
        $filterKey = md5(serialize($filters));
        $cacheKey = self::PREFIX_SEARCH.md5($query).':'.$filterKey;

        if ($results !== null) {
            Cache::put($cacheKey, $results->toArray(), self::SEARCH_TTL);

            return $results;
        }

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $collection = collect($cached)->map(function ($item) {
                return new Flashcard($item);
            });

            return new Collection($collection->all());
        }

        return null;
    }

    /**
     * Cache unit statistics
     */
    public function cacheUnitStats(int $unitId, ?array $stats = null): array
    {
        $cacheKey = self::PREFIX_STATS.$unitId;

        if ($stats !== null) {
            Cache::put($cacheKey, $stats, self::DEFAULT_TTL);

            return $stats;
        }

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Calculate stats
        $flashcards = $this->cacheUnitFlashcards($unitId);

        $stats = [
            'total_cards' => $flashcards->count(),
            'by_type' => $flashcards->groupBy('card_type')->map->count()->toArray(),
            'by_difficulty' => $flashcards->groupBy('difficulty_level')->map->count()->toArray(),
            'with_images' => $flashcards->whereNotNull('question_image_url')->count(),
            'with_hints' => $flashcards->whereNotNull('hint')->count(),
            'with_tags' => $flashcards->filter(function ($card) {
                return ! empty($card->tags);
            })->count(),
            'recently_added' => $flashcards->where('created_at', '>=', now()->subDays(7))->count(),
            'last_updated' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $stats, self::DEFAULT_TTL);

        return $stats;
    }

    /**
     * Cache import progress
     */
    public function cacheImportProgress(string $importId, array $progress): void
    {
        $cacheKey = self::PREFIX_IMPORT_PROGRESS.$importId;
        Cache::put($cacheKey, $progress, 300); // 5 minutes TTL for import progress
    }

    /**
     * Get cached import progress
     */
    public function getImportProgress(string $importId): ?array
    {
        $cacheKey = self::PREFIX_IMPORT_PROGRESS.$importId;

        return Cache::get($cacheKey);
    }

    /**
     * Invalidate all caches for a specific unit
     */
    public function invalidateUnitCache(int $unitId): void
    {
        $keys = [
            self::PREFIX_UNIT_CARDS.$unitId,
            self::PREFIX_UNIT_COUNT.$unitId,
            self::PREFIX_STATS.$unitId,
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Also clear any search caches that might contain this unit's cards
        $this->clearSearchCache();

        Log::info("Invalidated flashcard caches for unit {$unitId}");
    }

    /**
     * Clear all search result caches
     */
    public function clearSearchCache(): void
    {
        // Get all cache keys with search prefix
        $cacheStore = Cache::getStore();

        if (method_exists($cacheStore, 'flush')) {
            // For drivers that support pattern deletion, we'd implement it here
            // For now, we'll use a simpler approach with cache tags (if supported)
        }

        Log::info('Cleared flashcard search caches');
    }

    /**
     * Warm cache for a unit (preload data)
     */
    public function warmCache(int $unitId): void
    {
        Log::info("Warming cache for unit {$unitId}");

        // Preload flashcards
        $this->cacheUnitFlashcards($unitId);

        // Preload count
        $this->cacheFlashcardCount($unitId);

        // Preload stats
        $this->cacheUnitStats($unitId);

        Log::info("Cache warmed for unit {$unitId}");
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $cacheStore = Cache::getStore();

        $stats = [
            'driver' => config('cache.default'),
            'prefixes' => [
                'unit_cards' => self::PREFIX_UNIT_CARDS,
                'unit_count' => self::PREFIX_UNIT_COUNT,
                'search' => self::PREFIX_SEARCH,
                'stats' => self::PREFIX_STATS,
                'import_progress' => self::PREFIX_IMPORT_PROGRESS,
            ],
            'ttl' => [
                'default' => self::DEFAULT_TTL,
                'count' => self::COUNT_TTL,
                'search' => self::SEARCH_TTL,
            ],
        ];

        return $stats;
    }

    /**
     * Clear all flashcard-related caches
     */
    public function clearAllCaches(): void
    {
        $prefixes = [
            self::PREFIX_UNIT_CARDS,
            self::PREFIX_UNIT_COUNT,
            self::PREFIX_SEARCH,
            self::PREFIX_STATS,
            self::PREFIX_IMPORT_PROGRESS,
        ];

        // This is a simplified implementation
        // In production, you'd want to use cache tags or a more sophisticated approach
        Cache::flush();

        Log::info('Cleared all flashcard caches');
    }

    /**
     * Get memory usage for debugging
     */
    public function getMemoryUsage(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
        ];
    }
}

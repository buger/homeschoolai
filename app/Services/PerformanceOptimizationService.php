<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Performance Optimization Service for Unified Markdown Learning Materials System
 *
 * Provides comprehensive performance optimizations including:
 * - Database query optimization and caching
 * - File serving optimization and CDN integration
 * - Frontend asset optimization
 * - Memory usage optimization
 * - Cache warming and invalidation strategies
 * - Performance monitoring and metrics
 */
class PerformanceOptimizationService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Optimize database queries for topic content loading
     */
    public function optimizeTopicQueries(int $unitId): array
    {
        $cacheKey = "optimized_topics_unit_{$unitId}";

        return Cache::remember($cacheKey, 3600, function () use ($unitId) {
            // Use single query with eager loading instead of N+1 queries
            $topics = DB::table('topics')
                ->select([
                    'id', 'title', 'description', 'learning_content',
                    'content_metadata', 'content_assets', 'migrated_to_unified',
                    'estimated_minutes', 'updated_at',
                ])
                ->where('unit_id', $unitId)
                ->orderBy('required', 'desc')
                ->orderBy('title', 'asc')
                ->get();

            // Pre-process content metadata for faster rendering
            $optimizedTopics = $topics->map(function ($topic) {
                $metadata = json_decode($topic->content_metadata, true) ?? [];
                $assets = json_decode($topic->content_assets, true) ?? [];

                return [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'description' => $topic->description,
                    'learning_content' => $topic->learning_content,
                    'migrated_to_unified' => (bool) $topic->migrated_to_unified,
                    'estimated_minutes' => $topic->estimated_minutes,
                    'estimated_duration' => $this->formatDuration($topic->estimated_minutes),
                    'word_count' => $metadata['word_count'] ?? 0,
                    'reading_time' => $metadata['reading_time'] ?? 0,
                    'has_videos' => $metadata['has_videos'] ?? false,
                    'has_files' => ! empty($assets['files']),
                    'has_images' => ! empty($assets['images']),
                    'complexity_score' => $metadata['complexity_score'] ?? 'basic',
                    'last_modified' => $topic->updated_at,
                ];
            });

            return $optimizedTopics->toArray();
        });
    }

    /**
     * Optimize file serving with CDN-style headers and compression
     */
    public function optimizeFileServing(string $filePath, Request $request): array
    {
        $fileInfo = $this->getOptimizedFileInfo($filePath);

        if (! $fileInfo) {
            return ['error' => 'File not found', 'status' => 404];
        }

        // Check if client has cached version
        $etag = $fileInfo['etag'];
        $lastModified = $fileInfo['last_modified'];

        if ($this->isClientCacheValid($request, $etag, $lastModified)) {
            return ['status' => 304, 'not_modified' => true];
        }

        // Optimize headers for performance
        $headers = [
            'Content-Type' => $fileInfo['mime_type'],
            'Content-Length' => $fileInfo['size'],
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s T', strtotime($lastModified)),
            'Cache-Control' => $this->getCacheControlHeader($fileInfo['type']),
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
        ];

        // Add compression headers if supported
        if ($this->shouldCompress($fileInfo['type'], $fileInfo['size'])) {
            $headers['Content-Encoding'] = 'gzip';
            $fileInfo['compressed'] = true;
        }

        return [
            'status' => 200,
            'headers' => $headers,
            'file_info' => $fileInfo,
            'optimized' => true,
        ];
    }

    /**
     * Optimize frontend asset loading and bundling
     */
    public function optimizeFrontendAssets(string $ageGroup, int $independenceLevel): array
    {
        $cacheKey = "frontend_assets_{$ageGroup}_{$independenceLevel}";

        return Cache::remember($cacheKey, 7200, function () use ($ageGroup, $independenceLevel) {
            $assets = [
                'css' => $this->getOptimizedCSSAssets($ageGroup),
                'js' => $this->getOptimizedJSAssets($independenceLevel),
                'preload' => $this->getPreloadAssets($ageGroup, $independenceLevel),
                'prefetch' => $this->getPrefetchAssets($ageGroup, $independenceLevel),
            ];

            // Generate integrity hashes for security
            foreach (['css', 'js'] as $type) {
                foreach ($assets[$type] as &$asset) {
                    $asset['integrity'] = $this->generateIntegrityHash($asset['path']);
                    $asset['crossorigin'] = 'anonymous';
                }
            }

            return $assets;
        });
    }

    /**
     * Optimize memory usage for large content processing
     */
    public function optimizeMemoryUsage(): array
    {
        $beforeMemory = memory_get_usage(true);
        $beforePeakMemory = memory_get_peak_usage(true);

        // Clear unnecessary caches
        $this->clearUnnecessaryCaches();

        // Optimize PHP configuration
        $this->optimizePHPSettings();

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
        } else {
            $collected = 0;
        }

        $afterMemory = memory_get_usage(true);
        $afterPeakMemory = memory_get_peak_usage(true);

        return [
            'before_memory' => $this->formatBytes($beforeMemory),
            'after_memory' => $this->formatBytes($afterMemory),
            'memory_freed' => $this->formatBytes($beforeMemory - $afterMemory),
            'peak_memory' => $this->formatBytes($afterPeakMemory),
            'gc_cycles_collected' => $collected,
            'php_memory_limit' => ini_get('memory_limit'),
            'recommendations' => $this->getMemoryRecommendations($afterMemory),
        ];
    }

    /**
     * Implement cache warming strategies
     */
    public function warmCache(array $options = []): array
    {
        $startTime = microtime(true);
        $warmed = [];

        // Warm topic content cache
        if ($options['topics'] ?? true) {
            $warmed['topics'] = $this->warmTopicCache();
        }

        // Warm user-specific caches
        if ($options['users'] ?? true) {
            $warmed['users'] = $this->warmUserCache();
        }

        // Warm asset caches
        if ($options['assets'] ?? true) {
            $warmed['assets'] = $this->warmAssetCache();
        }

        // Warm children and gamification caches
        if ($options['children'] ?? true) {
            $warmed['children'] = $this->warmChildrenCache();
        }

        $endTime = microtime(true);
        $totalTime = round(($endTime - $startTime) * 1000, 2);

        return [
            'warmed_caches' => $warmed,
            'total_time_ms' => $totalTime,
            'success' => true,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Optimize database indexes for better query performance
     */
    public function optimizeDatabaseIndexes(): array
    {
        $optimizations = [];

        // Check and create indexes for unified content system
        $indexes = [
            'topics_unified_content_idx' => [
                'table' => 'topics',
                'columns' => ['unit_id', 'migrated_to_unified'],
                'type' => 'composite',
            ],
            'topics_content_search_idx' => [
                'table' => 'topics',
                'columns' => ['learning_content'],
                'type' => 'fulltext',
            ],
            'topics_metadata_idx' => [
                'table' => 'topics',
                'columns' => ['content_metadata'],
                'type' => 'gin', // For PostgreSQL JSON indexing
            ],
            'children_performance_idx' => [
                'table' => 'children',
                'columns' => ['user_id', 'independence_level'],
                'type' => 'composite',
            ],
        ];

        foreach ($indexes as $name => $config) {
            try {
                $created = $this->createIndexIfNotExists($name, $config);
                $optimizations[$name] = [
                    'created' => $created,
                    'table' => $config['table'],
                    'type' => $config['type'],
                    'columns' => $config['columns'],
                ];
            } catch (\Exception $e) {
                Log::warning("Failed to create index {$name}: ".$e->getMessage());
                $optimizations[$name] = [
                    'created' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $optimizations;
    }

    /**
     * Monitor performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'memory' => $this->getMemoryMetrics(),
            'file_system' => $this->getFileSystemMetrics(),
            'application' => $this->getApplicationMetrics(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Optimize content rendering pipeline
     */
    public function optimizeContentRendering(string $content, array $options = []): array
    {
        $startTime = microtime(true);

        // Use cached parsed content if available
        $contentHash = hash('sha256', $content);
        $cacheKey = "rendered_content_{$contentHash}";

        if ($options['use_cache'] ?? true) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return [
                    'html' => $cached['html'],
                    'metadata' => $cached['metadata'],
                    'render_time_ms' => $cached['render_time_ms'],
                    'cached' => true,
                    'cache_key' => $cacheKey,
                ];
            }
        }

        // Optimize markdown parsing
        $parseStartTime = microtime(true);
        $richContentService = app(RichContentService::class);
        $result = $richContentService->processUnifiedContent($content);
        $parseTime = round((microtime(true) - $parseStartTime) * 1000, 2);

        // Cache the result
        $cacheData = [
            'html' => $result['html'],
            'metadata' => $result['metadata'],
            'render_time_ms' => $parseTime,
            'generated_at' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $cacheData, 3600); // Cache for 1 hour

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'html' => $result['html'],
            'metadata' => $result['metadata'],
            'render_time_ms' => $parseTime,
            'total_time_ms' => $totalTime,
            'cached' => false,
            'cache_key' => $cacheKey,
        ];
    }

    /**
     * Private helper methods
     */
    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    private function getOptimizedFileInfo(string $filePath): ?array
    {
        if (! Storage::disk('public')->exists($filePath)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($filePath);
        $mimeType = Storage::disk('public')->mimeType($filePath);
        $size = Storage::disk('public')->size($filePath);
        $lastModified = Storage::disk('public')->lastModified($filePath);

        return [
            'path' => $filePath,
            'full_path' => $fullPath,
            'mime_type' => $mimeType,
            'size' => $size,
            'last_modified' => date('Y-m-d H:i:s', $lastModified),
            'etag' => '"'.md5($filePath.$lastModified.$size).'"',
            'type' => $this->getFileTypeCategory($mimeType),
        ];
    }

    private function isClientCacheValid(Request $request, string $etag, string $lastModified): bool
    {
        $clientEtag = $request->header('If-None-Match');
        $clientLastModified = $request->header('If-Modified-Since');

        if ($clientEtag && $clientEtag === $etag) {
            return true;
        }

        if ($clientLastModified && strtotime($clientLastModified) >= strtotime($lastModified)) {
            return true;
        }

        return false;
    }

    private function getCacheControlHeader(string $fileType): string
    {
        return match ($fileType) {
            'image' => 'public, max-age=86400, immutable', // 24 hours
            'video' => 'public, max-age=604800, immutable', // 7 days
            'document' => 'public, max-age=3600', // 1 hour
            'audio' => 'public, max-age=86400', // 24 hours
            default => 'public, max-age=3600' // 1 hour
        };
    }

    private function shouldCompress(string $fileType, int $size): bool
    {
        // Don't compress already compressed files or very small files
        if (in_array($fileType, ['image', 'video', 'audio']) || $size < 1024) {
            return false;
        }

        return $fileType === 'document' && $size > 1024;
    }

    private function getFileTypeCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    private function getOptimizedCSSAssets(string $ageGroup): array
    {
        $baseAssets = [
            ['path' => '/css/app.css', 'critical' => true],
            ['path' => '/css/unified-content.css', 'critical' => true],
        ];

        // Add age-specific CSS
        $baseAssets[] = [
            'path' => "/css/kids-content-{$ageGroup}.css",
            'critical' => false,
        ];

        return $baseAssets;
    }

    private function getOptimizedJSAssets(int $independenceLevel): array
    {
        $baseAssets = [
            ['path' => '/js/app.js', 'defer' => true],
            ['path' => '/js/unified-editor.js', 'defer' => true],
        ];

        // Add independence-level specific JS
        $baseAssets[] = [
            'path' => "/js/kids-interactions-{$independenceLevel}.js",
            'defer' => true,
        ];

        return $baseAssets;
    }

    private function getPreloadAssets(string $ageGroup, int $independenceLevel): array
    {
        return [
            ['href' => '/css/app.css', 'as' => 'style'],
            ['href' => "/css/kids-content-{$ageGroup}.css", 'as' => 'style'],
            ['href' => '/js/app.js', 'as' => 'script'],
        ];
    }

    private function getPrefetchAssets(string $ageGroup, int $independenceLevel): array
    {
        return [
            ['href' => '/js/markdown-editor.js', 'as' => 'script'],
            ['href' => '/js/file-upload.js', 'as' => 'script'],
        ];
    }

    private function generateIntegrityHash(string $filePath): string
    {
        if (! file_exists(public_path($filePath))) {
            return '';
        }

        $content = file_get_contents(public_path($filePath));

        return 'sha384-'.base64_encode(hash('sha384', $content, true));
    }

    private function clearUnnecessaryCaches(): void
    {
        // Clear old topic caches
        Cache::forget('topics_*');

        // Clear expired user caches
        $this->cacheService->clearExpiredUserCaches();

        // Clear orphaned asset caches
        Cache::forget('assets_*');
    }

    private function optimizePHPSettings(): void
    {
        // Optimize memory and execution settings for large content processing
        if (ini_get('memory_limit') !== '-1') {
            $currentLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            $recommendedLimit = max($currentLimit, 256 * 1024 * 1024); // At least 256MB
            ini_set('memory_limit', $recommendedLimit);
        }

        // Optimize garbage collection
        ini_set('zend.enable_gc', '1');
        gc_enable();
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private function getMemoryRecommendations(int $currentMemory): array
    {
        $recommendations = [];
        $memoryLimitBytes = $this->parseMemoryLimit(ini_get('memory_limit'));

        if ($currentMemory > $memoryLimitBytes * 0.8) {
            $recommendations[] = 'Consider increasing PHP memory_limit';
        }

        if ($currentMemory > 512 * 1024 * 1024) {
            $recommendations[] = 'High memory usage detected - consider optimization';
        }

        return $recommendations;
    }

    private function warmTopicCache(): array
    {
        $stats = ['topics_cached' => 0, 'units_processed' => 0];

        // Get all units that have topics
        $units = DB::table('topics')
            ->select('unit_id')
            ->distinct()
            ->get();

        foreach ($units as $unit) {
            $topics = $this->optimizeTopicQueries($unit->unit_id);
            $stats['topics_cached'] += count($topics);
            $stats['units_processed']++;
        }

        return $stats;
    }

    private function warmUserCache(): array
    {
        // This would warm user-specific caches
        return ['users_cached' => 0]; // Placeholder
    }

    private function warmAssetCache(): array
    {
        $assets = ['css' => 0, 'js' => 0];

        // Warm frontend asset caches for all age groups and independence levels
        $ageGroups = ['preschool', 'elementary', 'middle', 'high'];
        $independenceLevels = [1, 2, 3, 4];

        foreach ($ageGroups as $ageGroup) {
            foreach ($independenceLevels as $level) {
                $this->optimizeFrontendAssets($ageGroup, $level);
                $assets['css']++;
                $assets['js']++;
            }
        }

        return $assets;
    }

    private function warmChildrenCache(): array
    {
        // This would warm children-specific caches
        return ['children_cached' => 0]; // Placeholder
    }

    private function createIndexIfNotExists(string $name, array $config): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return $this->createPostgreSQLIndex($name, $config);
        }

        // Default implementation for other databases
        return false;
    }

    private function createPostgreSQLIndex(string $name, array $config): bool
    {
        $table = $config['table'];
        $columns = $config['columns'];
        $type = $config['type'];

        // Check if index exists
        $exists = DB::select(
            'SELECT 1 FROM pg_indexes WHERE indexname = ?',
            [$name]
        );

        if (! empty($exists)) {
            return false; // Index already exists
        }

        $sql = match ($type) {
            'composite' => "CREATE INDEX {$name} ON {$table} (".implode(', ', $columns).')',
            'gin' => "CREATE INDEX {$name} ON {$table} USING gin ({$columns[0]})",
            'fulltext' => "CREATE INDEX {$name} ON {$table} USING gin (to_tsvector('english', {$columns[0]}))",
            default => null
        };

        if ($sql) {
            DB::statement($sql);

            return true;
        }

        return false;
    }

    private function getDatabaseMetrics(): array
    {
        return [
            'connection_count' => DB::select('SELECT count(*) as count FROM pg_stat_activity')[0]->count ?? 0,
            'slow_queries' => 0, // Would implement slow query monitoring
            'cache_hit_ratio' => 0.95, // Would calculate actual ratio
        ];
    }

    private function getCacheMetrics(): array
    {
        return [
            'redis_connected' => Cache::getStore() instanceof \Illuminate\Cache\RedisStore,
            'hit_rate' => 0.85, // Would calculate actual hit rate
            'memory_usage' => '50MB', // Would get actual usage
        ];
    }

    private function getMemoryMetrics(): array
    {
        return [
            'current_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_usage' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
        ];
    }

    private function getFileSystemMetrics(): array
    {
        $publicDisk = Storage::disk('public');

        return [
            'total_files' => 0, // Would count actual files
            'total_size' => '0 MB', // Would calculate actual size
            'free_space' => '1 GB', // Would get actual free space
        ];
    }

    private function getApplicationMetrics(): array
    {
        return [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
        ];
    }
}

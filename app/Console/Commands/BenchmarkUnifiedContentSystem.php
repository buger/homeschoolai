<?php

namespace App\Console\Commands;

use App\Models\Child;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Services\KidsContentRenderer;
use App\Services\PerformanceOptimizationService;
use App\Services\RichContentService;
use App\Services\SecurityService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Performance Benchmarking Command for Unified Markdown Learning Materials System
 *
 * Provides comprehensive performance benchmarking including:
 * - Content processing speed tests
 * - Database query performance analysis
 * - File upload and serving benchmarks
 * - Memory usage optimization tests
 * - Kids view rendering performance
 * - Cache effectiveness measurements
 * - Security validation performance
 */
class BenchmarkUnifiedContentSystem extends Command
{
    protected $signature = 'benchmark:unified-content
                          {--suite=all : Which benchmark suite to run (all|content|database|files|memory|kids|cache|security)}
                          {--iterations=10 : Number of iterations for each test}
                          {--detailed : Show detailed output}
                          {--export= : Export results to file (json|csv)}';

    protected $description = 'Benchmark the unified markdown learning materials system performance';

    protected RichContentService $richContentService;

    protected KidsContentRenderer $kidsRenderer;

    protected PerformanceOptimizationService $performanceService;

    protected SecurityService $securityService;

    protected array $results = [];

    protected int $iterations;

    protected bool $verbose;

    public function handle(): int
    {
        $this->iterations = (int) $this->option('iterations');
        $this->verbose = $this->option('detailed');

        $this->richContentService = app(RichContentService::class);
        $this->kidsRenderer = app(KidsContentRenderer::class);
        $this->performanceService = app(PerformanceOptimizationService::class);
        $this->securityService = app(SecurityService::class);

        $this->info('🚀 Starting Unified Content System Performance Benchmarks');
        $this->info("Iterations per test: {$this->iterations}");
        $this->newLine();

        $suite = $this->option('suite');

        try {
            switch ($suite) {
                case 'content':
                    $this->benchmarkContentProcessing();
                    break;
                case 'database':
                    $this->benchmarkDatabaseOperations();
                    break;
                case 'files':
                    $this->benchmarkFileOperations();
                    break;
                case 'memory':
                    $this->benchmarkMemoryUsage();
                    break;
                case 'kids':
                    $this->benchmarkKidsRendering();
                    break;
                case 'cache':
                    $this->benchmarkCachePerformance();
                    break;
                case 'security':
                    $this->benchmarkSecurityValidation();
                    break;
                case 'all':
                default:
                    $this->benchmarkContentProcessing();
                    $this->benchmarkDatabaseOperations();
                    $this->benchmarkFileOperations();
                    $this->benchmarkMemoryUsage();
                    $this->benchmarkKidsRendering();
                    $this->benchmarkCachePerformance();
                    $this->benchmarkSecurityValidation();
                    break;
            }

            $this->displayResults();

            if ($exportFormat = $this->option('export')) {
                $this->exportResults($exportFormat);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Benchmark failed: '.$e->getMessage());

            return 1;
        }
    }

    protected function benchmarkContentProcessing(): void
    {
        $this->info('📝 Benchmarking Content Processing Performance...');

        $testContents = $this->generateTestContents();

        foreach ($testContents as $name => $content) {
            $this->benchmark("content_processing_{$name}", function () use ($content) {
                return $this->richContentService->processUnifiedContent($content);
            });

            if ($this->verbose) {
                $wordCount = str_word_count(strip_tags($content));
                $this->line("  └─ {$name}: {$wordCount} words");
            }
        }

        // Test markdown to HTML conversion specifically
        $markdownContent = $testContents['complex'];
        $this->benchmark('markdown_to_html', function () use ($markdownContent) {
            return $this->richContentService->markdownToHtml($markdownContent);
        });

        // Test HTML to markdown conversion
        $htmlContent = $this->richContentService->markdownToHtml($markdownContent);
        $this->benchmark('html_to_markdown', function () use ($htmlContent) {
            return $this->richContentService->htmlToMarkdown($htmlContent);
        });

        $this->line('✅ Content processing benchmarks completed');
        $this->newLine();
    }

    protected function benchmarkDatabaseOperations(): void
    {
        $this->info('🗄️ Benchmarking Database Operations...');

        // Setup test data
        $this->setupTestData();

        // Test optimized topic queries
        $unitId = Unit::first()->id;
        $this->benchmark('optimized_topic_queries', function () use ($unitId) {
            return $this->performanceService->optimizeTopicQueries($unitId);
        });

        // Test topic migration
        $topic = Topic::notMigrated()->first();
        if ($topic) {
            $this->benchmark('topic_migration', function () use ($topic) {
                return $topic->migrateToUnified();
            });
        }

        // Test content asset extraction
        $this->benchmark('content_asset_extraction', function () use ($topic) {
            return $topic->extractContentAssets();
        });

        // Test legacy material extraction
        $this->benchmark('legacy_material_extraction', function () use ($topic) {
            return $topic->getLegacyMaterials();
        });

        // Test orphaned asset cleanup
        $this->benchmark('orphaned_asset_cleanup', function () use ($topic) {
            $topic->cleanupOrphanedAssets();

            return true;
        });

        $this->line('✅ Database operation benchmarks completed');
        $this->newLine();
    }

    protected function benchmarkFileOperations(): void
    {
        $this->info('📁 Benchmarking File Operations...');

        Storage::fake('public');

        // Test file upload processing
        $testFiles = $this->generateTestFiles();

        foreach ($testFiles as $name => $fileData) {
            $this->benchmark("file_upload_{$name}", function () use ($fileData) {
                $file = UploadedFile::fake()->create(
                    $fileData['name'],
                    $fileData['size'],
                    $fileData['mime']
                );

                return $this->richContentService->uploadContentImage(1, $file);
            });
        }

        // Test file serving optimization
        $testFile = 'test-file.pdf';
        Storage::disk('public')->put($testFile, 'test content');

        $this->benchmark('file_serving_optimization', function () use ($testFile) {
            $request = request();

            return $this->performanceService->optimizeFileServing($testFile, $request);
        });

        // Test security file validation
        foreach ($testFiles as $name => $fileData) {
            $this->benchmark("security_validation_{$name}", function () use ($fileData) {
                $file = UploadedFile::fake()->create(
                    $fileData['name'],
                    $fileData['size'],
                    $fileData['mime']
                );

                return $this->securityService->validateFileUpload($file);
            });
        }

        $this->line('✅ File operation benchmarks completed');
        $this->newLine();
    }

    protected function benchmarkMemoryUsage(): void
    {
        $this->info('🧠 Benchmarking Memory Usage...');

        $initialMemory = memory_get_usage(true);

        // Test large content processing
        $largeContent = str_repeat("# Large Content Section\n\nThis is a large content section with lots of text to test memory usage. ".str_repeat('Word ', 1000)."\n\n", 50);

        $this->benchmark('large_content_processing', function () use ($largeContent) {
            return $this->richContentService->processUnifiedContent($largeContent);
        });

        $afterLargeContent = memory_get_usage(true);
        $this->results['memory_usage']['large_content_memory_delta'] = $afterLargeContent - $initialMemory;

        // Test memory optimization
        $this->benchmark('memory_optimization', function () {
            return $this->performanceService->optimizeMemoryUsage();
        });

        $finalMemory = memory_get_usage(true);
        $this->results['memory_usage']['optimization_memory_delta'] = $finalMemory - $afterLargeContent;

        // Test multiple topic processing
        $this->benchmark('multiple_topic_processing', function () {
            $topics = Topic::limit(10)->get();
            $results = [];

            foreach ($topics as $topic) {
                $content = $topic->getUnifiedContent();
                if ($content) {
                    $results[] = $this->richContentService->processUnifiedContent($content);
                }
            }

            return $results;
        });

        $multipleTopicsMemory = memory_get_usage(true);
        $this->results['memory_usage']['multiple_topics_memory_delta'] = $multipleTopicsMemory - $finalMemory;

        $this->line('✅ Memory usage benchmarks completed');
        $this->newLine();
    }

    protected function benchmarkKidsRendering(): void
    {
        $this->info('🎨 Benchmarking Kids View Rendering...');

        // Setup test child
        $testChild = $this->createTestChild();
        $testTopic = Topic::first() ?? $this->createTestTopic();

        $testContents = $this->generateTestContents();

        foreach ($testContents as $name => $content) {
            $this->benchmark("kids_rendering_{$name}", function () use ($content, $testChild, $testTopic) {
                return $this->kidsRenderer->renderForKids($content, $testChild, $testTopic);
            });
        }

        // Test different independence levels
        for ($level = 1; $level <= 4; $level++) {
            $child = $this->createTestChild(['independence_level' => $level]);
            $this->benchmark("kids_rendering_independence_{$level}", function () use ($testContents, $child, $testTopic) {
                return $this->kidsRenderer->renderForKids($testContents['medium'], $child, $testTopic);
            });
        }

        // Test different age groups
        $ageGroups = [
            ['grade' => 'PreK', 'name' => 'preschool'],
            ['grade' => '3rd', 'name' => 'elementary'],
            ['grade' => '7th', 'name' => 'middle'],
            ['grade' => '11th', 'name' => 'high'],
        ];

        foreach ($ageGroups as $ageGroup) {
            $child = $this->createTestChild(['grade' => $ageGroup['grade']]);
            $this->benchmark("kids_rendering_age_{$ageGroup['name']}", function () use ($testContents, $child, $testTopic) {
                return $this->kidsRenderer->renderForKids($testContents['medium'], $child, $testTopic);
            });
        }

        $this->line('✅ Kids rendering benchmarks completed');
        $this->newLine();
    }

    protected function benchmarkCachePerformance(): void
    {
        $this->info('⚡ Benchmarking Cache Performance...');

        // Clear caches first
        Cache::flush();

        // Test cache warming
        $this->benchmark('cache_warming', function () {
            return $this->performanceService->warmCache();
        });

        // Test optimized topic queries (should use cache)
        $unitId = Unit::first()->id;
        $this->benchmark('cached_topic_queries', function () use ($unitId) {
            return $this->performanceService->optimizeTopicQueries($unitId);
        });

        // Test content rendering with cache
        $content = $this->generateTestContents()['medium'];
        $this->benchmark('content_rendering_with_cache', function () use ($content) {
            return $this->performanceService->optimizeContentRendering($content, ['use_cache' => true]);
        });

        // Test content rendering without cache
        $contentHash = hash('sha256', $content);
        Cache::forget("rendered_content_{$contentHash}");
        $this->benchmark('content_rendering_without_cache', function () use ($content) {
            return $this->performanceService->optimizeContentRendering($content, ['use_cache' => false]);
        });

        // Test frontend asset optimization
        $this->benchmark('frontend_asset_optimization', function () {
            return $this->performanceService->optimizeFrontendAssets('elementary', 2);
        });

        $this->line('✅ Cache performance benchmarks completed');
        $this->newLine();
    }

    protected function benchmarkSecurityValidation(): void
    {
        $this->info('🔒 Benchmarking Security Validation...');

        // Test content security scanning
        $testContents = [
            'safe_content' => '# Safe Content\n\nThis is completely safe content.',
            'suspicious_content' => '# Content\n\n<script>alert("test")</script>\n\nSome content.',
            'complex_content' => $this->generateTestContents()['complex'],
        ];

        foreach ($testContents as $name => $content) {
            $this->benchmark("security_scan_{$name}", function () use ($content) {
                return $this->securityService->scanContentSecurity($content);
            });
        }

        // Test URL validation
        $testUrls = [
            'safe_youtube' => 'https://www.youtube.com/watch?v=test',
            'suspicious_ip' => 'http://192.168.1.1/video',
            'shortener' => 'https://bit.ly/test',
            'educational' => 'https://www.khanacademy.org/science/physics',
        ];

        foreach ($testUrls as $name => $url) {
            $this->benchmark("url_validation_{$name}", function () use ($url) {
                return $this->securityService->validateUrl($url);
            });
        }

        // Test secure token operations
        $this->benchmark('generate_secure_token', function () {
            return $this->securityService->generateSecureFileToken('/test/file.pdf', 1);
        });

        $token = $this->securityService->generateSecureFileToken('/test/file.pdf', 1);
        $this->benchmark('validate_secure_token', function () use ($token) {
            return $this->securityService->validateSecureFileToken($token, '/test/file.pdf', 1);
        });

        // Test file authorization
        $this->benchmark('file_authorization', function () {
            return $this->securityService->authorizeFileAccess('/test/file.pdf', 1);
        });

        $this->line('✅ Security validation benchmarks completed');
        $this->newLine();
    }

    protected function benchmark(string $testName, callable $callback): void
    {
        $times = [];
        $memoryUsages = [];

        for ($i = 0; $i < $this->iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            try {
                $result = $callback();
                $success = true;
            } catch (\Exception $e) {
                $success = false;
                $error = $e->getMessage();
            }

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $times[] = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsages[] = $endMemory - $startMemory;
        }

        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);
        $avgMemory = array_sum($memoryUsages) / count($memoryUsages);

        $this->results[$testName] = [
            'avg_time_ms' => round($avgTime, 3),
            'min_time_ms' => round($minTime, 3),
            'max_time_ms' => round($maxTime, 3),
            'avg_memory_bytes' => $avgMemory,
            'avg_memory_formatted' => $this->formatBytes($avgMemory),
            'iterations' => $this->iterations,
            'success' => $success ?? true,
        ];

        if (isset($error)) {
            $this->results[$testName]['error'] = $error;
        }

        if ($this->verbose) {
            $status = ($success ?? true) ? '✅' : '❌';
            $this->line("  {$status} {$testName}: {$avgTime}ms avg ({$minTime}-{$maxTime}ms), {$this->formatBytes($avgMemory)} memory");
        }
    }

    protected function generateTestContents(): array
    {
        return [
            'simple' => "# Simple Content\n\nThis is simple markdown content.",

            'medium' => "# Medium Complexity Content\n\n## Introduction\n\nThis content has **bold** and *italic* text.\n\n### Video Resources\n\n[Educational Video](https://www.youtube.com/watch?v=test123)\n\n### Files\n\n[Worksheet](worksheet.pdf)\n\n- Item 1\n- Item 2\n- Item 3",

            'complex' => "# Complex Learning Content\n\n## Overview\n\nThis is a comprehensive topic with multiple media types and interactive elements.\n\n### Video Resources\n\n[Introduction Video](https://www.youtube.com/watch?v=intro123)\n[Advanced Concepts](https://vimeo.com/456789)\n[Khan Academy Lesson](https://www.khanacademy.org/science/physics)\n\n### Interactive Elements\n\n!!! collapse \"Additional Information\"\n\nThis is collapsible content with more details.\n\n!!!\n\n### Study Materials\n\n| Resource | Type | Difficulty |\n|----------|------|------------|\n| Worksheet 1 | PDF | Beginner |\n| Quiz | Interactive | Intermediate |\n| Project | Assignment | Advanced |\n\n### Tasks\n\n- [x] Watch introduction video\n- [ ] Complete worksheet 1\n- [ ] Take practice quiz\n- [ ] Submit final project\n\n### Downloads\n\n[Study Guide](study-guide.pdf)\n[Answer Key](answers.pdf)\n[Project Template](template.docx)\n\n### External Links\n\n- [Educational Website](https://education.com/topic)\n- [Research Paper](https://journal.com/paper)\n- [Interactive Simulation](https://simulation.edu/physics)\n\n### Code Examples\n\n```python\ndef calculate_velocity(distance, time):\n    return distance / time\n\nvelocity = calculate_velocity(100, 10)\nprint(f\"Velocity: {velocity} m/s\")\n```\n\n### Mathematical Formulas\n\nVelocity formula: v = d/t\n\nWhere:\n- v = velocity\n- d = distance\n- t = time\n\n> **Important Note**\n>\n> Always remember to include units in your calculations!\n\n### Summary\n\nThis topic covers fundamental physics concepts including velocity, acceleration, and motion. Students should complete all activities in order for best understanding.",
        ];
    }

    protected function generateTestFiles(): array
    {
        return [
            'small_image' => ['name' => 'small.png', 'size' => 1024, 'mime' => 'image/png'],
            'large_image' => ['name' => 'large.jpg', 'size' => 2 * 1024 * 1024, 'mime' => 'image/jpeg'],
            'document' => ['name' => 'document.pdf', 'size' => 5 * 1024 * 1024, 'mime' => 'application/pdf'],
            'video' => ['name' => 'video.mp4', 'size' => 10 * 1024 * 1024, 'mime' => 'video/mp4'],
        ];
    }

    protected function setupTestData(): void
    {
        // Create test subject if none exists
        if (! Subject::exists()) {
            $subject = Subject::factory()->create(['name' => 'Benchmark Test Subject']);
            $unit = Unit::factory()->create(['subject_id' => $subject->id, 'title' => 'Benchmark Test Unit']);

            for ($i = 1; $i <= 5; $i++) {
                Topic::factory()->create([
                    'unit_id' => $unit->id,
                    'title' => "Benchmark Test Topic {$i}",
                    'migrated_to_unified' => $i <= 2, // Some migrated, some not
                    'learning_materials' => [
                        'videos' => [['title' => 'Test Video', 'url' => 'https://youtube.com/test']],
                        'links' => [['title' => 'Test Link', 'url' => 'https://example.com']],
                    ],
                ]);
            }
        }
    }

    protected function createTestChild(array $attributes = []): Child
    {
        return Child::factory()->create(array_merge([
            'name' => 'Benchmark Test Child',
            'age' => 10,
            'grade' => '5th',
            'independence_level' => 2,
        ], $attributes));
    }

    protected function createTestTopic(array $attributes = []): Topic
    {
        $subject = Subject::first() ?? Subject::factory()->create();
        $unit = Unit::where('subject_id', $subject->id)->first() ?? Unit::factory()->create(['subject_id' => $subject->id]);

        return Topic::factory()->create(array_merge([
            'unit_id' => $unit->id,
            'title' => 'Benchmark Test Topic',
        ], $attributes));
    }

    protected function displayResults(): void
    {
        $this->info('📊 Benchmark Results Summary');
        $this->newLine();

        $totalTests = 0;
        $totalAvgTime = 0;
        $slowestTest = ['name' => '', 'time' => 0];
        $fastestTest = ['name' => '', 'time' => PHP_FLOAT_MAX];

        foreach ($this->results as $testName => $result) {
            if (is_array($result) && isset($result['avg_time_ms'])) {
                $totalTests++;
                $totalAvgTime += $result['avg_time_ms'];

                if ($result['avg_time_ms'] > $slowestTest['time']) {
                    $slowestTest = ['name' => $testName, 'time' => $result['avg_time_ms']];
                }

                if ($result['avg_time_ms'] < $fastestTest['time']) {
                    $fastestTest = ['name' => $testName, 'time' => $result['avg_time_ms']];
                }

                $status = $result['success'] ? '✅' : '❌';
                $this->line("{$status} {$testName}: {$result['avg_time_ms']}ms ({$result['avg_memory_formatted']})");
            }
        }

        $this->newLine();
        $this->info('📈 Performance Summary:');
        $this->line("Total tests: {$totalTests}");
        $this->line('Average time: '.round($totalAvgTime / $totalTests, 3).'ms');
        $this->line("Fastest test: {$fastestTest['name']} ({$fastestTest['time']}ms)");
        $this->line("Slowest test: {$slowestTest['name']} ({$slowestTest['time']}ms)");
        $this->line('Total iterations: '.($totalTests * $this->iterations));
    }

    protected function exportResults(string $format): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "benchmark_results_{$timestamp}.{$format}";

        switch ($format) {
            case 'json':
                $content = json_encode([
                    'timestamp' => now()->toISOString(),
                    'iterations' => $this->iterations,
                    'results' => $this->results,
                ], JSON_PRETTY_PRINT);
                break;

            case 'csv':
                $content = "Test Name,Avg Time (ms),Min Time (ms),Max Time (ms),Memory Usage,Success\n";
                foreach ($this->results as $testName => $result) {
                    if (is_array($result) && isset($result['avg_time_ms'])) {
                        $content .= implode(',', [
                            $testName,
                            $result['avg_time_ms'],
                            $result['min_time_ms'],
                            $result['max_time_ms'],
                            $result['avg_memory_formatted'],
                            $result['success'] ? 'true' : 'false',
                        ])."\n";
                    }
                }
                break;

            default:
                $this->error("Unsupported export format: {$format}");

                return;
        }

        file_put_contents($filename, $content);
        $this->info("Results exported to: {$filename}");
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}

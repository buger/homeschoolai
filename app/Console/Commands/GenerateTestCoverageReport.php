<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Finder\Finder;

/**
 * Generate Comprehensive Test Coverage Report for Unified Markdown Learning Materials System
 *
 * This command provides detailed test coverage analysis including:
 * - PHP unit test coverage (with PHPUnit)
 * - E2E test coverage analysis
 * - Feature coverage mapping
 * - Performance test coverage
 * - Security test coverage
 * - Code quality metrics
 * - Detailed HTML reports
 */
class GenerateTestCoverageReport extends Command
{
    protected $signature = 'test:coverage-report
                          {--type=all : Type of coverage to generate (all|unit|e2e|security|performance)}
                          {--format=html : Output format (html|json|text|clover)}
                          {--output=coverage : Output directory for reports}
                          {--min-coverage=80 : Minimum coverage threshold}
                          {--open : Open HTML report in browser after generation}';

    protected $description = 'Generate comprehensive test coverage reports for the unified content system';

    protected string $outputDir;

    protected string $format;

    protected int $minCoverage;

    public function handle(): int
    {
        $this->outputDir = $this->option('output');
        $this->format = $this->option('format');
        $this->minCoverage = (int) $this->option('min-coverage');

        $this->info('🧪 Generating Comprehensive Test Coverage Report');
        $this->info("Output directory: {$this->outputDir}");
        $this->info("Format: {$this->format}");
        $this->info("Minimum coverage threshold: {$this->minCoverage}%");
        $this->newLine();

        try {
            // Create output directory
            $this->ensureOutputDirectory();

            $type = $this->option('type');

            switch ($type) {
                case 'unit':
                    $this->generateUnitTestCoverage();
                    break;
                case 'e2e':
                    $this->generateE2ETestCoverage();
                    break;
                case 'security':
                    $this->generateSecurityTestCoverage();
                    break;
                case 'performance':
                    $this->generatePerformanceTestCoverage();
                    break;
                case 'all':
                default:
                    $this->generateUnitTestCoverage();
                    $this->generateE2ETestCoverage();
                    $this->generateSecurityTestCoverage();
                    $this->generatePerformanceTestCoverage();
                    $this->generateUnifiedReport();
                    break;
            }

            $this->generateSummaryReport();

            if ($this->option('open') && $this->format === 'html') {
                $this->openHtmlReport();
            }

            $this->info('✅ Test coverage report generation completed successfully');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Test coverage report generation failed: '.$e->getMessage());

            return 1;
        }
    }

    protected function ensureOutputDirectory(): void
    {
        if (! File::exists($this->outputDir)) {
            File::makeDirectory($this->outputDir, 0755, true);
            $this->line("Created output directory: {$this->outputDir}");
        }

        // Create subdirectories
        $subdirs = ['unit', 'e2e', 'security', 'performance', 'unified'];
        foreach ($subdirs as $subdir) {
            $path = "{$this->outputDir}/{$subdir}";
            if (! File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }

    protected function generateUnitTestCoverage(): void
    {
        $this->info('🧪 Generating PHP Unit Test Coverage...');

        // Run PHPUnit with coverage
        $coverageCommand = $this->buildPHPUnitCommand();

        $this->line("Running: {$coverageCommand}");

        $result = Process::run($coverageCommand);

        if ($result->failed()) {
            $this->warn('PHPUnit coverage generation had issues:');
            $this->line($result->errorOutput());
        } else {
            $this->info('✅ PHPUnit coverage generated successfully');
        }

        // Parse coverage results
        $this->parseUnitTestResults();
    }

    protected function generateE2ETestCoverage(): void
    {
        $this->info('🌐 Analyzing E2E Test Coverage...');

        // Analyze E2E test files
        $e2eTestFiles = $this->findE2ETestFiles();
        $coverage = $this->analyzeE2ETestCoverage($e2eTestFiles);

        // Generate E2E coverage report
        $this->generateE2EReport($coverage);

        $this->info('✅ E2E test coverage analysis completed');
    }

    protected function generateSecurityTestCoverage(): void
    {
        $this->info('🔒 Analyzing Security Test Coverage...');

        $securityFeatures = $this->getSecurityFeatures();
        $securityTests = $this->findSecurityTests();
        $coverage = $this->analyzeSecurityCoverage($securityFeatures, $securityTests);

        $this->generateSecurityReport($coverage);

        $this->info('✅ Security test coverage analysis completed');
    }

    protected function generatePerformanceTestCoverage(): void
    {
        $this->info('⚡ Analyzing Performance Test Coverage...');

        $performanceAreas = $this->getPerformanceAreas();
        $performanceTests = $this->findPerformanceTests();
        $coverage = $this->analyzePerformanceCoverage($performanceAreas, $performanceTests);

        $this->generatePerformanceReport($coverage);

        $this->info('✅ Performance test coverage analysis completed');
    }

    protected function generateUnifiedReport(): void
    {
        $this->info('📊 Generating Unified Coverage Report...');

        $unifiedData = $this->compileUnifiedData();
        $this->generateUnifiedReportFile($unifiedData);

        $this->info('✅ Unified coverage report generated');
    }

    protected function buildPHPUnitCommand(): string
    {
        $command = './vendor/bin/phpunit';

        // Add coverage options based on format
        switch ($this->format) {
            case 'html':
                $command .= " --coverage-html {$this->outputDir}/unit/html";
                break;
            case 'clover':
                $command .= " --coverage-clover {$this->outputDir}/unit/clover.xml";
                break;
            case 'json':
                $command .= " --coverage-php {$this->outputDir}/unit/coverage.serialized";
                break;
            case 'text':
                $command .= " --coverage-text={$this->outputDir}/unit/coverage.txt";
                break;
        }

        // Add specific filters for unified content system
        $command .= ' --filter="Services|Models|Controllers" --testdox';

        // Set memory limit for coverage generation
        $command = 'php -d memory_limit=1G '.$command;

        return $command;
    }

    protected function parseUnitTestResults(): void
    {
        $resultsFile = "{$this->outputDir}/unit/coverage-summary.json";

        // Generate summary from PHPUnit output if possible
        $summary = [
            'total_lines' => 0,
            'covered_lines' => 0,
            'percentage' => 0,
            'files_analyzed' => 0,
            'test_files' => 0,
            'assertions' => 0,
            'timestamp' => now()->toISOString(),
        ];

        // Try to extract data from clover report if exists
        $cloverFile = "{$this->outputDir}/unit/clover.xml";
        if (File::exists($cloverFile)) {
            $summary = $this->parseCloverReport($cloverFile);
        }

        // Add detailed analysis
        $summary['detailed_analysis'] = $this->analyzeUnitTestFiles();

        File::put($resultsFile, json_encode($summary, JSON_PRETTY_PRINT));
    }

    protected function parseCloverReport(string $cloverFile): array
    {
        $xml = simplexml_load_file($cloverFile);
        $metrics = $xml->project->metrics;

        return [
            'total_lines' => (int) $metrics['loc'],
            'covered_lines' => (int) $metrics['coveredstatements'],
            'percentage' => round(((int) $metrics['coveredstatements'] / (int) $metrics['statements']) * 100, 2),
            'files_analyzed' => (int) $metrics['files'],
            'classes' => (int) $metrics['classes'],
            'methods' => (int) $metrics['methods'],
            'covered_methods' => (int) $metrics['coveredmethods'],
            'statements' => (int) $metrics['statements'],
            'covered_statements' => (int) $metrics['coveredstatements'],
            'timestamp' => now()->toISOString(),
        ];
    }

    protected function analyzeUnitTestFiles(): array
    {
        $testFiles = [];

        $finder = new Finder;
        $finder->files()->in('tests/Unit')->name('*.php');

        foreach ($finder as $file) {
            $content = $file->getContents();
            $testMethods = $this->extractTestMethods($content);

            $testFiles[] = [
                'file' => $file->getRelativePathname(),
                'path' => $file->getRealPath(),
                'test_methods' => count($testMethods),
                'methods' => $testMethods,
                'lines' => substr_count($content, "\n") + 1,
                'class' => $this->extractClassName($content),
            ];
        }

        return $testFiles;
    }

    protected function extractTestMethods(string $content): array
    {
        preg_match_all('/public function (test\w+)\(/', $content, $matches);

        return $matches[1] ?? [];
    }

    protected function extractClassName(string $content): string
    {
        preg_match('/class (\w+)/', $content, $matches);

        return $matches[1] ?? 'Unknown';
    }

    protected function findE2ETestFiles(): array
    {
        $testFiles = [];

        $finder = new Finder;
        $finder->files()->in('tests/e2e')->name('*.spec.ts');

        foreach ($finder as $file) {
            $content = $file->getContents();
            $tests = $this->extractE2ETests($content);

            $testFiles[] = [
                'file' => $file->getRelativePathname(),
                'path' => $file->getRealPath(),
                'tests' => $tests,
                'test_count' => count($tests),
                'lines' => substr_count($content, "\n") + 1,
                'describes' => $this->extractDescribeBlocks($content),
            ];
        }

        return $testFiles;
    }

    protected function extractE2ETests(string $content): array
    {
        preg_match_all('/test\([\'"]([^\'"]+)[\'"]/', $content, $matches);

        return $matches[1] ?? [];
    }

    protected function extractDescribeBlocks(string $content): array
    {
        preg_match_all('/test\.describe\([\'"]([^\'"]+)[\'"]/', $content, $matches);

        return $matches[1] ?? [];
    }

    protected function analyzeE2ETestCoverage(array $testFiles): array
    {
        $features = $this->getUnifiedContentFeatures();
        $coverage = [];

        foreach ($features as $feature => $details) {
            $coverage[$feature] = [
                'feature' => $feature,
                'description' => $details['description'],
                'priority' => $details['priority'],
                'tested' => false,
                'test_files' => [],
                'test_count' => 0,
            ];

            // Check which test files cover this feature
            foreach ($testFiles as $testFile) {
                $featureCovered = $this->checkFeatureCoverage($feature, $testFile, $details['keywords']);

                if ($featureCovered) {
                    $coverage[$feature]['tested'] = true;
                    $coverage[$feature]['test_files'][] = $testFile['file'];
                    $coverage[$feature]['test_count'] += $testFile['test_count'];
                }
            }
        }

        return $coverage;
    }

    protected function checkFeatureCoverage(string $feature, array $testFile, array $keywords): bool
    {
        $content = File::get($testFile['path']);
        $contentLower = strtolower($content);

        foreach ($keywords as $keyword) {
            if (strpos($contentLower, strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function getUnifiedContentFeatures(): array
    {
        return [
            'markdown_processing' => [
                'description' => 'Markdown to HTML conversion and processing',
                'priority' => 'high',
                'keywords' => ['markdown', 'html', 'processing', 'unified content'],
            ],
            'file_upload' => [
                'description' => 'File upload functionality with drag and drop',
                'priority' => 'high',
                'keywords' => ['file upload', 'drag', 'drop', 'upload'],
            ],
            'kids_view' => [
                'description' => 'Child-friendly content rendering',
                'priority' => 'high',
                'keywords' => ['kids', 'child', 'children', 'age appropriate'],
            ],
            'security_validation' => [
                'description' => 'Security features and content validation',
                'priority' => 'critical',
                'keywords' => ['security', 'validation', 'safe', 'malicious'],
            ],
            'video_embedding' => [
                'description' => 'Video platform integration and embedding',
                'priority' => 'medium',
                'keywords' => ['video', 'youtube', 'vimeo', 'embed'],
            ],
            'content_migration' => [
                'description' => 'Legacy to unified content migration',
                'priority' => 'medium',
                'keywords' => ['migration', 'legacy', 'unified', 'convert'],
            ],
            'performance_optimization' => [
                'description' => 'Performance optimizations and caching',
                'priority' => 'medium',
                'keywords' => ['performance', 'cache', 'optimization', 'speed'],
            ],
            'file_management' => [
                'description' => 'File management and asset handling',
                'priority' => 'medium',
                'keywords' => ['file management', 'assets', 'cleanup', 'orphaned'],
            ],
        ];
    }

    protected function getSecurityFeatures(): array
    {
        return [
            'file_upload_security' => 'File upload security validation',
            'content_scanning' => 'Malicious content scanning',
            'url_validation' => 'URL security validation',
            'access_control' => 'File access control',
            'token_validation' => 'Secure token operations',
            'xss_prevention' => 'XSS attack prevention',
            'injection_prevention' => 'SQL/Command injection prevention',
            'file_type_validation' => 'File type and signature validation',
        ];
    }

    protected function findSecurityTests(): array
    {
        $securityTests = [];

        // Find unit tests for SecurityService
        $securityTestFile = 'tests/Unit/Services/SecurityServiceTest.php';
        if (File::exists($securityTestFile)) {
            $content = File::get($securityTestFile);
            $methods = $this->extractTestMethods($content);
            $securityTests['unit'] = $methods;
        }

        // Find E2E security tests
        $finder = new Finder;
        $finder->files()->in('tests/e2e')->name('*.spec.ts');

        foreach ($finder as $file) {
            $content = $file->getContents();
            if (strpos($content, 'security') !== false || strpos($content, 'Security') !== false) {
                $tests = $this->extractE2ETests($content);
                $securityTests['e2e'][$file->getRelativePathname()] = $tests;
            }
        }

        return $securityTests;
    }

    protected function analyzeSecurityCoverage(array $features, array $tests): array
    {
        $coverage = [];

        foreach ($features as $feature => $description) {
            $coverage[$feature] = [
                'feature' => $feature,
                'description' => $description,
                'unit_tests' => 0,
                'e2e_tests' => 0,
                'covered' => false,
            ];

            // Check unit tests
            if (isset($tests['unit'])) {
                foreach ($tests['unit'] as $test) {
                    if (strpos($test, $feature) !== false || $this->isSecurityTestRelated($test, $feature)) {
                        $coverage[$feature]['unit_tests']++;
                        $coverage[$feature]['covered'] = true;
                    }
                }
            }

            // Check E2E tests
            if (isset($tests['e2e'])) {
                foreach ($tests['e2e'] as $file => $fileTests) {
                    foreach ($fileTests as $test) {
                        if (strpos($test, $feature) !== false || $this->isSecurityTestRelated($test, $feature)) {
                            $coverage[$feature]['e2e_tests']++;
                            $coverage[$feature]['covered'] = true;
                        }
                    }
                }
            }
        }

        return $coverage;
    }

    protected function isSecurityTestRelated(string $testName, string $feature): bool
    {
        $testNameLower = strtolower($testName);
        $featureLower = strtolower($feature);

        $keywords = [
            'file_upload_security' => ['upload', 'file', 'security'],
            'content_scanning' => ['scan', 'malicious', 'content'],
            'url_validation' => ['url', 'validate'],
            'access_control' => ['access', 'auth'],
            'token_validation' => ['token'],
            'xss_prevention' => ['xss', 'script'],
            'injection_prevention' => ['injection', 'sql'],
            'file_type_validation' => ['file_type', 'extension', 'mime'],
        ];

        if (isset($keywords[$feature])) {
            foreach ($keywords[$feature] as $keyword) {
                if (strpos($testNameLower, $keyword) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getPerformanceAreas(): array
    {
        return [
            'content_processing' => 'Markdown content processing speed',
            'database_queries' => 'Database query optimization',
            'file_operations' => 'File upload and serving performance',
            'memory_usage' => 'Memory usage optimization',
            'cache_performance' => 'Caching effectiveness',
            'kids_rendering' => 'Kids view rendering performance',
            'security_validation' => 'Security validation performance',
        ];
    }

    protected function findPerformanceTests(): array
    {
        $performanceTests = [];

        // Check for benchmark command
        $benchmarkFile = 'app/Console/Commands/BenchmarkUnifiedContentSystem.php';
        if (File::exists($benchmarkFile)) {
            $content = File::get($benchmarkFile);
            preg_match_all('/protected function benchmark(\w+)\(/', $content, $matches);
            $performanceTests['benchmark'] = $matches[1] ?? [];
        }

        // Check for performance-related E2E tests
        $finder = new Finder;
        $finder->files()->in('tests/e2e')->name('*.spec.ts');

        foreach ($finder as $file) {
            $content = $file->getContents();
            if (strpos($content, 'performance') !== false || strpos($content, 'Performance') !== false) {
                $tests = $this->extractE2ETests($content);
                $performanceTests['e2e'][$file->getRelativePathname()] = $tests;
            }
        }

        return $performanceTests;
    }

    protected function analyzePerformanceCoverage(array $areas, array $tests): array
    {
        $coverage = [];

        foreach ($areas as $area => $description) {
            $coverage[$area] = [
                'area' => $area,
                'description' => $description,
                'benchmark_tests' => 0,
                'e2e_tests' => 0,
                'covered' => false,
            ];

            // Check benchmark tests
            if (isset($tests['benchmark'])) {
                foreach ($tests['benchmark'] as $test) {
                    if (strpos($test, $area) !== false || $this->isPerformanceTestRelated($test, $area)) {
                        $coverage[$area]['benchmark_tests']++;
                        $coverage[$area]['covered'] = true;
                    }
                }
            }

            // Check E2E tests
            if (isset($tests['e2e'])) {
                foreach ($tests['e2e'] as $file => $fileTests) {
                    foreach ($fileTests as $test) {
                        if (strpos($test, $area) !== false || $this->isPerformanceTestRelated($test, $area)) {
                            $coverage[$area]['e2e_tests']++;
                            $coverage[$area]['covered'] = true;
                        }
                    }
                }
            }
        }

        return $coverage;
    }

    protected function isPerformanceTestRelated(string $testName, string $area): bool
    {
        $testNameLower = strtolower($testName);

        $keywords = [
            'content_processing' => ['content', 'processing', 'markdown'],
            'database_queries' => ['database', 'query', 'topic'],
            'file_operations' => ['file', 'upload', 'serving'],
            'memory_usage' => ['memory', 'usage'],
            'cache_performance' => ['cache', 'caching'],
            'kids_rendering' => ['kids', 'rendering'],
            'security_validation' => ['security', 'validation'],
        ];

        if (isset($keywords[$area])) {
            foreach ($keywords[$area] as $keyword) {
                if (strpos($testNameLower, $keyword) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function generateE2EReport(array $coverage): void
    {
        $reportFile = "{$this->outputDir}/e2e/coverage-report.json";

        $summary = [
            'total_features' => count($coverage),
            'covered_features' => count(array_filter($coverage, fn ($c) => $c['tested'])),
            'coverage_percentage' => 0,
            'features' => $coverage,
            'timestamp' => now()->toISOString(),
        ];

        $summary['coverage_percentage'] = round(($summary['covered_features'] / $summary['total_features']) * 100, 2);

        File::put($reportFile, json_encode($summary, JSON_PRETTY_PRINT));

        if ($this->format === 'html') {
            $this->generateE2EHtmlReport($summary);
        }
    }

    protected function generateE2EHtmlReport(array $summary): void
    {
        $html = $this->generateHtmlTemplate('E2E Test Coverage Report', $this->renderE2EHtml($summary));
        File::put("{$this->outputDir}/e2e/coverage-report.html", $html);
    }

    protected function renderE2EHtml(array $summary): string
    {
        $html = "<h1>E2E Test Coverage Report</h1>\n";
        $html .= "<div class='summary'>\n";
        $html .= "<p><strong>Coverage:</strong> {$summary['covered_features']}/{$summary['total_features']} features ({$summary['coverage_percentage']}%)</p>\n";
        $html .= "</div>\n";

        $html .= "<table class='coverage-table'>\n";
        $html .= "<thead><tr><th>Feature</th><th>Description</th><th>Priority</th><th>Tested</th><th>Test Count</th></tr></thead>\n";
        $html .= "<tbody>\n";

        foreach ($summary['features'] as $feature) {
            $status = $feature['tested'] ? '✅' : '❌';
            $priority = $feature['priority'];
            $html .= "<tr class='priority-{$priority}'>\n";
            $html .= "<td>{$feature['feature']}</td>\n";
            $html .= "<td>{$feature['description']}</td>\n";
            $html .= "<td>{$priority}</td>\n";
            $html .= "<td>{$status}</td>\n";
            $html .= "<td>{$feature['test_count']}</td>\n";
            $html .= "</tr>\n";
        }

        $html .= "</tbody></table>\n";

        return $html;
    }

    protected function generateSecurityReport(array $coverage): void
    {
        $reportFile = "{$this->outputDir}/security/coverage-report.json";

        $summary = [
            'total_features' => count($coverage),
            'covered_features' => count(array_filter($coverage, fn ($c) => $c['covered'])),
            'coverage_percentage' => 0,
            'features' => $coverage,
            'timestamp' => now()->toISOString(),
        ];

        $summary['coverage_percentage'] = round(($summary['covered_features'] / $summary['total_features']) * 100, 2);

        File::put($reportFile, json_encode($summary, JSON_PRETTY_PRINT));

        if ($this->format === 'html') {
            $this->generateSecurityHtmlReport($summary);
        }
    }

    protected function generateSecurityHtmlReport(array $summary): void
    {
        $html = $this->generateHtmlTemplate('Security Test Coverage Report', $this->renderSecurityHtml($summary));
        File::put("{$this->outputDir}/security/coverage-report.html", $html);
    }

    protected function renderSecurityHtml(array $summary): string
    {
        $html = "<h1>Security Test Coverage Report</h1>\n";
        $html .= "<div class='summary'>\n";
        $html .= "<p><strong>Coverage:</strong> {$summary['covered_features']}/{$summary['total_features']} features ({$summary['coverage_percentage']}%)</p>\n";
        $html .= "</div>\n";

        $html .= "<table class='coverage-table'>\n";
        $html .= "<thead><tr><th>Feature</th><th>Description</th><th>Unit Tests</th><th>E2E Tests</th><th>Covered</th></tr></thead>\n";
        $html .= "<tbody>\n";

        foreach ($summary['features'] as $feature) {
            $status = $feature['covered'] ? '✅' : '❌';
            $html .= "<tr>\n";
            $html .= "<td>{$feature['feature']}</td>\n";
            $html .= "<td>{$feature['description']}</td>\n";
            $html .= "<td>{$feature['unit_tests']}</td>\n";
            $html .= "<td>{$feature['e2e_tests']}</td>\n";
            $html .= "<td>{$status}</td>\n";
            $html .= "</tr>\n";
        }

        $html .= "</tbody></table>\n";

        return $html;
    }

    protected function generatePerformanceReport(array $coverage): void
    {
        $reportFile = "{$this->outputDir}/performance/coverage-report.json";

        $summary = [
            'total_areas' => count($coverage),
            'covered_areas' => count(array_filter($coverage, fn ($c) => $c['covered'])),
            'coverage_percentage' => 0,
            'areas' => $coverage,
            'timestamp' => now()->toISOString(),
        ];

        $summary['coverage_percentage'] = round(($summary['covered_areas'] / $summary['total_areas']) * 100, 2);

        File::put($reportFile, json_encode($summary, JSON_PRETTY_PRINT));

        if ($this->format === 'html') {
            $this->generatePerformanceHtmlReport($summary);
        }
    }

    protected function generatePerformanceHtmlReport(array $summary): void
    {
        $html = $this->generateHtmlTemplate('Performance Test Coverage Report', $this->renderPerformanceHtml($summary));
        File::put("{$this->outputDir}/performance/coverage-report.html", $html);
    }

    protected function renderPerformanceHtml(array $summary): string
    {
        $html = "<h1>Performance Test Coverage Report</h1>\n";
        $html .= "<div class='summary'>\n";
        $html .= "<p><strong>Coverage:</strong> {$summary['covered_areas']}/{$summary['total_areas']} areas ({$summary['coverage_percentage']}%)</p>\n";
        $html .= "</div>\n";

        $html .= "<table class='coverage-table'>\n";
        $html .= "<thead><tr><th>Area</th><th>Description</th><th>Benchmark Tests</th><th>E2E Tests</th><th>Covered</th></tr></thead>\n";
        $html .= "<tbody>\n";

        foreach ($summary['areas'] as $area) {
            $status = $area['covered'] ? '✅' : '❌';
            $html .= "<tr>\n";
            $html .= "<td>{$area['area']}</td>\n";
            $html .= "<td>{$area['description']}</td>\n";
            $html .= "<td>{$area['benchmark_tests']}</td>\n";
            $html .= "<td>{$area['e2e_tests']}</td>\n";
            $html .= "<td>{$status}</td>\n";
            $html .= "</tr>\n";
        }

        $html .= "</tbody></table>\n";

        return $html;
    }

    protected function compileUnifiedData(): array
    {
        $data = [
            'generated_at' => now()->toISOString(),
            'unified_content_system_coverage' => [],
        ];

        // Load individual reports
        $reports = ['unit', 'e2e', 'security', 'performance'];

        foreach ($reports as $report) {
            $reportFile = "{$this->outputDir}/{$report}/coverage-report.json";
            if (File::exists($reportFile)) {
                $data['unified_content_system_coverage'][$report] = json_decode(File::get($reportFile), true);
            }
        }

        return $data;
    }

    protected function generateUnifiedReportFile(array $data): void
    {
        File::put("{$this->outputDir}/unified/unified-coverage-report.json", json_encode($data, JSON_PRETTY_PRINT));

        if ($this->format === 'html') {
            $this->generateUnifiedHtmlReport($data);
        }
    }

    protected function generateUnifiedHtmlReport(array $data): void
    {
        $html = $this->generateHtmlTemplate('Unified Content System - Complete Test Coverage Report', $this->renderUnifiedHtml($data));
        File::put("{$this->outputDir}/unified/unified-coverage-report.html", $html);
    }

    protected function renderUnifiedHtml(array $data): string
    {
        $html = "<h1>Unified Markdown Learning Materials System - Complete Test Coverage Report</h1>\n";
        $html .= "<p class='subtitle'>Generated on {$data['generated_at']}</p>\n";

        foreach ($data['unified_content_system_coverage'] as $type => $report) {
            $html .= "<div class='report-section'>\n";
            $html .= '<h2>'.ucfirst($type)." Test Coverage</h2>\n";

            if (isset($report['coverage_percentage'])) {
                $percentage = $report['coverage_percentage'];
                $statusClass = $percentage >= $this->minCoverage ? 'success' : 'warning';
                $html .= "<div class='coverage-badge {$statusClass}'>{$percentage}%</div>\n";
            }

            $html .= "<div class='report-details'>\n";
            $html .= $this->renderReportDetails($type, $report);
            $html .= "</div>\n";
            $html .= "</div>\n";
        }

        return $html;
    }

    protected function renderReportDetails(string $type, array $report): string
    {
        switch ($type) {
            case 'unit':
                return $this->renderUnitDetails($report);
            case 'e2e':
                return $this->renderE2EDetails($report);
            case 'security':
                return $this->renderSecurityDetails($report);
            case 'performance':
                return $this->renderPerformanceDetails($report);
            default:
                return "<p>No details available for {$type}</p>";
        }
    }

    protected function renderUnitDetails(array $report): string
    {
        $html = "<h3>PHP Unit Test Coverage</h3>\n";
        if (isset($report['statements'])) {
            $html .= "<p><strong>Statements:</strong> {$report['covered_statements']}/{$report['statements']}</p>\n";
            $html .= "<p><strong>Methods:</strong> {$report['covered_methods']}/{$report['methods']}</p>\n";
            $html .= "<p><strong>Files:</strong> {$report['files_analyzed']}</p>\n";
        }

        return $html;
    }

    protected function renderE2EDetails(array $report): string
    {
        $html = "<h3>End-to-End Test Coverage</h3>\n";
        $html .= "<p><strong>Features Tested:</strong> {$report['covered_features']}/{$report['total_features']}</p>\n";

        return $html;
    }

    protected function renderSecurityDetails(array $report): string
    {
        $html = "<h3>Security Test Coverage</h3>\n";
        $html .= "<p><strong>Security Features Tested:</strong> {$report['covered_features']}/{$report['total_features']}</p>\n";

        return $html;
    }

    protected function renderPerformanceDetails(array $report): string
    {
        $html = "<h3>Performance Test Coverage</h3>\n";
        $html .= "<p><strong>Performance Areas Tested:</strong> {$report['covered_areas']}/{$report['total_areas']}</p>\n";

        return $html;
    }

    protected function generateHtmlTemplate(string $title, string $content): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        h2 { color: #374151; margin-top: 30px; }
        h3 { color: #6b7280; }
        .subtitle { color: #6b7280; font-style: italic; margin-bottom: 30px; }
        .summary { background: #f3f4f6; padding: 20px; border-radius: 6px; margin: 20px 0; }
        .coverage-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 10px 0; }
        .coverage-badge.success { background: #10b981; color: white; }
        .coverage-badge.warning { background: #f59e0b; color: white; }
        .coverage-badge.error { background: #ef4444; color: white; }
        .coverage-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .coverage-table th, .coverage-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .coverage-table th { background: #f9fafb; font-weight: 600; }
        .coverage-table tr:hover { background: #f9fafb; }
        .priority-high { background: #fef2f2; }
        .priority-critical { background: #fee2e2; }
        .report-section { margin: 30px 0; padding: 20px; border: 1px solid #e5e7eb; border-radius: 6px; }
        .report-details { margin-top: 15px; }
        .timestamp { font-size: 0.9em; color: #6b7280; margin-top: 30px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        {$content}
        <div class="timestamp">Generated at {time()}</div>
    </div>
</body>
</html>
HTML;
    }

    protected function generateSummaryReport(): void
    {
        $this->info('📋 Generating Summary Report...');

        $summaryData = $this->compileSummaryData();
        $summaryFile = "{$this->outputDir}/summary-report.json";

        File::put($summaryFile, json_encode($summaryData, JSON_PRETTY_PRINT));

        $this->displaySummary($summaryData);
    }

    protected function compileSummaryData(): array
    {
        $summary = [
            'timestamp' => now()->toISOString(),
            'unified_content_system_coverage' => [],
            'overall_assessment' => 'pending',
        ];

        $totalCoverage = 0;
        $reportCount = 0;

        $reports = ['unit', 'e2e', 'security', 'performance'];

        foreach ($reports as $report) {
            $reportFile = "{$this->outputDir}/{$report}/coverage-report.json";
            if (File::exists($reportFile)) {
                $data = json_decode(File::get($reportFile), true);
                if (isset($data['coverage_percentage'])) {
                    $summary['unified_content_system_coverage'][$report] = $data['coverage_percentage'];
                    $totalCoverage += $data['coverage_percentage'];
                    $reportCount++;
                }
            }
        }

        if ($reportCount > 0) {
            $avgCoverage = $totalCoverage / $reportCount;
            $summary['average_coverage'] = round($avgCoverage, 2);

            if ($avgCoverage >= 90) {
                $summary['overall_assessment'] = 'excellent';
            } elseif ($avgCoverage >= $this->minCoverage) {
                $summary['overall_assessment'] = 'good';
            } elseif ($avgCoverage >= 60) {
                $summary['overall_assessment'] = 'needs_improvement';
            } else {
                $summary['overall_assessment'] = 'poor';
            }
        }

        return $summary;
    }

    protected function displaySummary(array $summary): void
    {
        $this->newLine();
        $this->info('📊 Test Coverage Summary');
        $this->line('==========================================');

        foreach ($summary['unified_content_system_coverage'] as $type => $percentage) {
            $status = $percentage >= $this->minCoverage ? '✅' : '⚠️';
            $this->line("{$status} ".ucfirst($type)." Coverage: {$percentage}%");
        }

        if (isset($summary['average_coverage'])) {
            $this->newLine();
            $avgStatus = $summary['average_coverage'] >= $this->minCoverage ? '✅' : '⚠️';
            $this->line("{$avgStatus} Average Coverage: {$summary['average_coverage']}%");

            $assessment = match ($summary['overall_assessment']) {
                'excellent' => '🎉 Excellent test coverage!',
                'good' => '👍 Good test coverage',
                'needs_improvement' => '⚠️ Test coverage needs improvement',
                'poor' => '❌ Poor test coverage - needs immediate attention',
                default => '❓ Assessment pending'
            };

            $this->line("Overall Assessment: {$assessment}");
        }

        $this->newLine();
        $this->line("Reports generated in: {$this->outputDir}");
    }

    protected function openHtmlReport(): void
    {
        $reportFile = "{$this->outputDir}/unified/unified-coverage-report.html";

        if (File::exists($reportFile)) {
            if (PHP_OS_FAMILY === 'Darwin') {
                exec("open {$reportFile}");
            } elseif (PHP_OS_FAMILY === 'Windows') {
                exec("start {$reportFile}");
            } elseif (PHP_OS_FAMILY === 'Linux') {
                exec("xdg-open {$reportFile}");
            }

            $this->info("Opened HTML report: {$reportFile}");
        }
    }
}

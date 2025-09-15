<?php

namespace App\Console\Commands;

use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MigrateTopicsToUnified extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'topics:migrate-to-unified
                            {--dry-run : Run migration without saving changes}
                            {--batch-size=50 : Number of topics to process per batch}
                            {--force : Force migration even if already migrated}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate topics from legacy learning_materials format to unified markdown content';

    /**
     * Migration statistics
     */
    protected $stats = [
        'total' => 0,
        'processed' => 0,
        'migrated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'empty_content' => 0,
        'has_materials' => 0,
        'has_description' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting topic migration to unified content system...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        try {
            // Get topics to migrate
            $query = Topic::query();

            if (! $force) {
                $query->where('migrated_to_unified', false);
            }

            $this->stats['total'] = $query->count();

            if ($this->stats['total'] === 0) {
                $this->info('✅ No topics need migration');

                return Command::SUCCESS;
            }

            $this->info("📊 Found {$this->stats['total']} topics to process");
            $this->newLine();

            if (! $dryRun && ! $this->confirm('Do you want to continue with the migration?')) {
                $this->info('Migration cancelled');

                return Command::SUCCESS;
            }

            // Process in batches
            $bar = $this->output->createProgressBar($this->stats['total']);
            $bar->setFormat('debug');

            $query->chunk($batchSize, function ($topics) use ($dryRun, $bar) {
                foreach ($topics as $topic) {
                    $this->processTopic($topic, $dryRun);
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine(2);

            $this->displayResults($dryRun);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: '.$e->getMessage());
            Log::error('Topic migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Process a single topic
     */
    protected function processTopic(Topic $topic, bool $dryRun): void
    {
        $this->stats['processed']++;

        try {
            // Analyze topic content
            $hasDescription = ! empty($topic->description);
            $hasMaterials = ! empty($topic->learning_materials);

            if ($hasDescription) {
                $this->stats['has_description']++;
            }

            if ($hasMaterials) {
                $this->stats['has_materials']++;
            }

            // Skip if already migrated and not forcing
            if ($topic->migrated_to_unified && ! $this->option('force')) {
                $this->stats['skipped']++;

                return;
            }

            // Skip if no content to migrate
            if (! $hasDescription && ! $hasMaterials) {
                $this->stats['empty_content']++;

                return;
            }

            if ($dryRun) {
                // Just validate conversion
                $unifiedContent = $topic->convertToUnifiedMarkdown();
                $contentAssets = $topic->extractContentAssets();

                $this->validateMigration($topic, $unifiedContent, $contentAssets);
                $this->stats['migrated']++;
            } else {
                // Perform actual migration
                $success = DB::transaction(function () use ($topic) {
                    return $topic->migrateToUnified();
                });

                if ($success) {
                    $this->stats['migrated']++;
                } else {
                    $this->stats['errors']++;
                    $this->warn("Failed to migrate topic: {$topic->title} (ID: {$topic->id})");
                }
            }

        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->error("Error processing topic {$topic->id}: ".$e->getMessage());
            Log::error('Topic migration error', [
                'topic_id' => $topic->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate migration conversion
     */
    protected function validateMigration(Topic $topic, string $unifiedContent, array $contentAssets): void
    {
        // Check that content was properly converted
        if (empty($unifiedContent) && (! empty($topic->description) || ! empty($topic->learning_materials))) {
            throw new \Exception('Content conversion failed - unified content is empty but source has content');
        }

        // Check that video links are preserved
        if (! empty($topic->learning_materials['videos'])) {
            $videoCount = count($topic->learning_materials['videos']);
            $videoLinksInContent = substr_count($unifiedContent, '](http');

            if ($videoLinksInContent < $videoCount) {
                $this->warn("Potential video link loss in topic {$topic->id}");
            }
        }

        // Check that files are tracked in assets
        if (! empty($topic->learning_materials['files'])) {
            $originalFileCount = count($topic->learning_materials['files']);
            $trackedFileCount = count($contentAssets['files']);

            if ($trackedFileCount !== $originalFileCount) {
                $this->warn("File tracking mismatch in topic {$topic->id}: {$originalFileCount} original, {$trackedFileCount} tracked");
            }
        }
    }

    /**
     * Display migration results
     */
    protected function displayResults(bool $dryRun): void
    {
        $mode = $dryRun ? 'DRY RUN' : 'ACTUAL';

        $this->info("📈 Migration Results ({$mode}):");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Topics', $this->stats['total']],
                ['Processed', $this->stats['processed']],
                ['Successfully Migrated', $this->stats['migrated']],
                ['Skipped (Already Migrated)', $this->stats['skipped']],
                ['Empty Content (Skipped)', $this->stats['empty_content']],
                ['Errors', $this->stats['errors']],
                ['Had Description', $this->stats['has_description']],
                ['Had Learning Materials', $this->stats['has_materials']],
            ]
        );

        if ($this->stats['migrated'] > 0) {
            $this->info("✅ Successfully processed {$this->stats['migrated']} topics");
        }

        if ($this->stats['errors'] > 0) {
            $this->error("⚠️  {$this->stats['errors']} topics had errors");
        }

        if ($dryRun && $this->stats['migrated'] > 0) {
            $this->newLine();
            $this->info('To perform the actual migration, run:');
            $this->line('php artisan topics:migrate-to-unified');
        }

        $this->newLine();
        $this->info('🎉 Migration analysis complete!');
    }

    /**
     * Show sample conversions for review
     */
    protected function showSampleConversions(int $limit = 3): void
    {
        $this->info('📋 Sample Conversions:');
        $this->newLine();

        $topics = Topic::where('migrated_to_unified', false)
            ->where(function ($query) {
                $query->whereNotNull('description')
                    ->orWhereNotNull('learning_materials');
            })
            ->take($limit)
            ->get();

        foreach ($topics as $topic) {
            $this->line("🔸 Topic: {$topic->title}");
            $this->line('   Original Description: '.Str::limit($topic->description ?? 'None', 50));
            $this->line('   Original Materials: '.(empty($topic->learning_materials) ? 'None' : json_encode($topic->learning_materials)));

            $unified = $topic->convertToUnifiedMarkdown();
            $this->line('   Unified Content Preview:');
            $this->line('   '.Str::limit($unified, 100));
            $this->newLine();
        }
    }
}

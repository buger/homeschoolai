<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $unit_id
 * @property string $title
 * @property string|null $description
 * @property array|null $learning_materials
 * @property string|null $learning_content
 * @property array|null $content_assets
 * @property bool $migrated_to_unified
 * @property int $estimated_minutes
 * @property array|null $prerequisites
 * @property bool $required
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Unit $unit
 * @property-read \App\Models\Subject $subject
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model> $sessions
 *
 * @method static \Illuminate\Database\Eloquent\Builder notMigrated()
 * @method static \Illuminate\Database\Eloquent\Builder migrated()
 */
class Topic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'unit_id',
        'title',
        'description',
        'content_format',
        'content_metadata',
        'embedded_images',
        'learning_materials',
        'learning_content',
        'content_assets',
        'migrated_to_unified',
        'estimated_minutes',
        'prerequisites',
        'required',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'unit_id' => 'integer',
        'estimated_minutes' => 'integer',
        'learning_materials' => 'array', // JSON array handling for materials
        'content_metadata' => 'array', // JSON array for rich content metadata
        'embedded_images' => 'array', // JSON array for embedded images
        'content_assets' => 'array', // JSON array for file asset tracking
        'prerequisites' => 'array', // JSON array handling
        'required' => 'boolean',
        'migrated_to_unified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'estimated_minutes' => 30,
        'content_format' => 'plain',
        'learning_materials' => null,
        'content_metadata' => null,
        'embedded_images' => null,
        'learning_content' => null,
        'content_assets' => null,
        'migrated_to_unified' => false,
        'prerequisites' => '[]',
        'required' => true,
    ];

    /**
     * Get the unit that owns the topic.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the sessions for this topic.
     * Note: Session is still using Supabase pattern, so this relationship is not functional yet
     */
    public function sessions(): HasMany
    {
        // Cannot use hasMany with non-Eloquent Session model
        throw new \BadMethodCallException('Session relationship not yet available - Session model is still using Supabase pattern');
    }

    /**
     * Scope to get topics for a specific unit
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId)
            ->orderBy('required', 'desc')
            ->orderBy('title', 'asc');
    }

    /**
     * Compatibility methods for existing controllers
     */
    public static function forUnit(int $unitId, $supabase = null): Collection
    {
        return self::where('unit_id', $unitId)
            ->orderBy('required', 'desc')
            ->orderBy('title', 'asc')
            ->get();
    }

    // Override find to support string IDs for compatibility
    public static function find($id, $columns = ['*'])
    {
        return static::query()->find((int) $id, $columns);
    }

    // The save() and delete() methods are now handled by Eloquent automatically

    /**
     * Get the subject this topic belongs to (through unit)
     */
    public function subject()
    {
        return $this->hasOneThrough(Subject::class, Unit::class, 'id', 'id', 'unit_id', 'subject_id');
    }

    /**
     * Accessor method for backward compatibility
     */
    public function getSubjectAttribute(): ?Subject
    {
        return $this->unit->subject ?? null;
    }

    /**
     * Compatibility method for controllers
     */
    public function subject_compat($supabase = null): ?Subject
    {
        return $this->subject();
    }

    /**
     * Get prerequisite topics
     */
    public function getPrerequisiteTopics($supabase = null): Collection
    {
        if (empty($this->prerequisites)) {
            return collect([]);
        }

        return self::whereIn('id', $this->prerequisites)->get();
    }

    /**
     * Check if all prerequisites are met
     * Note: This is a simplified check - in a real app you'd track completion status
     */
    public function hasPrerequisitesMet($supabase = null): bool
    {
        // For now, return true - in a real app you'd check completion status
        return true;
    }

    /**
     * Get estimated duration in human readable format
     */
    public function getEstimatedDuration(): string
    {
        if ($this->estimated_minutes < 60) {
            return "{$this->estimated_minutes} min";
        }

        $hours = floor($this->estimated_minutes / 60);
        $minutes = $this->estimated_minutes % 60;

        if ($minutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$minutes}m";
    }

    /**
     * Get learning materials by type
     */
    public function getMaterialsByType(string $type): array
    {
        if (empty($this->learning_materials)) {
            return [];
        }

        return $this->learning_materials[$type] ?? [];
    }

    /**
     * Get all videos
     */
    public function getVideos(): array
    {
        return $this->getMaterialsByType('videos');
    }

    /**
     * Get all links
     */
    public function getLinks(): array
    {
        return $this->getMaterialsByType('links');
    }

    /**
     * Get all files
     */
    public function getFiles(): array
    {
        return $this->getMaterialsByType('files');
    }

    /**
     * Add a material to the topic
     */
    public function addMaterial(string $type, array $material): bool
    {
        $materials = $this->learning_materials ?? [];

        if (! isset($materials[$type])) {
            $materials[$type] = [];
        }

        $materials[$type][] = $material;
        $this->learning_materials = $materials;

        return $this->save();
    }

    /**
     * Remove a material from the topic
     */
    public function removeMaterial(string $type, int $index): bool
    {
        $materials = $this->learning_materials ?? [];

        if (! isset($materials[$type]) || ! isset($materials[$type][$index])) {
            return false;
        }

        unset($materials[$type][$index]);
        $materials[$type] = array_values($materials[$type]); // Reindex array

        // Remove empty type arrays
        if (empty($materials[$type])) {
            unset($materials[$type]);
        }

        $this->learning_materials = $materials;

        return $this->save();
    }

    /**
     * Check if topic has any learning materials
     */
    public function hasLearningMaterials(): bool
    {
        if (empty($this->learning_materials)) {
            return false;
        }

        foreach ($this->learning_materials as $materials) {
            if (! empty($materials)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total count of learning materials
     */
    public function getLearningMaterialsCount(): int
    {
        if (empty($this->learning_materials)) {
            return 0;
        }

        $count = 0;
        foreach ($this->learning_materials as $materials) {
            $count += count($materials);
        }

        return $count;
    }

    /**
     * Extract video ID and type from URL
     */
    public static function parseVideoUrl(string $url): ?array
    {
        // YouTube patterns
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return [
                'type' => 'youtube',
                'id' => $matches[1],
                'thumbnail' => "https://img.youtube.com/vi/{$matches[1]}/maxresdefault.jpg",
            ];
        }

        // Vimeo patterns
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
            return [
                'type' => 'vimeo',
                'id' => $matches[1],
                'thumbnail' => null, // Vimeo thumbnails require API call
            ];
        }

        // Khan Academy patterns
        if (preg_match('/khanacademy\.org\/.*\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return [
                'type' => 'khan_academy',
                'id' => $matches[1],
                'thumbnail' => null,
            ];
        }

        return null;
    }

    /**
     * Validate estimated_minutes is within reasonable bounds
     */
    public static function validateEstimatedMinutes(int $minutes): bool
    {
        return $minutes > 0 && $minutes <= 480; // 8 hours max
    }

    /**
     * Check if this topic has rich content
     */
    public function hasRichContent(): bool
    {
        return $this->content_format !== 'plain' && ! empty($this->description);
    }

    /**
     * Get content metadata with defaults
     */
    public function getContentMetadata(): array
    {
        return $this->content_metadata ?? [
            'word_count' => 0,
            'reading_time' => 0,
            'character_count' => 0,
            'format' => $this->content_format ?? 'plain',
            'last_updated' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get embedded images with defaults
     */
    public function getEmbeddedImages(): array
    {
        return $this->embedded_images ?? [];
    }

    /**
     * Check if topic has embedded images
     */
    public function hasEmbeddedImages(): bool
    {
        return ! empty($this->embedded_images);
    }

    /**
     * Get reading time in human readable format
     */
    public function getReadingTime(): string
    {
        $metadata = $this->getContentMetadata();
        $minutes = $metadata['reading_time'] ?? 0;

        if ($minutes < 1) {
            return 'Less than 1 minute';
        } elseif ($minutes === 1) {
            return '1 minute';
        } else {
            return "{$minutes} minutes";
        }
    }

    /**
     * Get word count
     */
    public function getWordCount(): int
    {
        $metadata = $this->getContentMetadata();

        return $metadata['word_count'] ?? 0;
    }

    /**
     * Update content metadata
     */
    public function updateContentMetadata(array $metadata): bool
    {
        $this->content_metadata = array_merge($this->getContentMetadata(), $metadata);

        return $this->save();
    }

    /**
     * ==========================================
     * UNIFIED CONTENT SYSTEM METHODS
     * ==========================================
     */

    /**
     * Convert old format to unified markdown
     */
    public function migrateToUnified(): bool
    {
        if ($this->migrated_to_unified) {
            return true; // Already migrated
        }

        try {
            $unifiedContent = $this->convertToUnifiedMarkdown();
            $contentAssets = $this->extractContentAssets();

            $this->learning_content = $unifiedContent;
            $this->content_assets = $contentAssets;
            $this->migrated_to_unified = true;

            return $this->save();
        } catch (\Exception $e) {
            \Log::error("Failed to migrate topic {$this->id} to unified format: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Convert existing content to unified markdown format
     */
    public function convertToUnifiedMarkdown(): string
    {
        $content = [];

        // Add description as main content
        if (! empty($this->description)) {
            $content[] = trim($this->description);
        }

        // Process learning materials
        if (! empty($this->learning_materials)) {
            $materials = $this->learning_materials;

            // Add videos section
            if (! empty($materials['videos'])) {
                $content[] = "\n## Video Resources\n";
                foreach ($materials['videos'] as $video) {
                    $title = $video['title'] ?? 'Video';
                    $url = $video['url'] ?? '';

                    if (! empty($url)) {
                        // Always use simple link format for better parsing compatibility
                        $content[] = "[{$title}]({$url})";
                    }
                }
            }

            // Add links section
            if (! empty($materials['links'])) {
                $content[] = "\n## Additional Resources\n";
                foreach ($materials['links'] as $link) {
                    $title = $link['title'] ?? 'Resource';
                    $url = $link['url'] ?? '';
                    $description = $link['description'] ?? '';

                    if (! empty($url)) {
                        if (! empty($description)) {
                            $content[] = "- [{$title}]({$url}) - {$description}";
                        } else {
                            $content[] = "- [{$title}]({$url})";
                        }
                    }
                }
            }

            // Add files section
            if (! empty($materials['files'])) {
                $content[] = "\n## Downloads\n";
                foreach ($materials['files'] as $file) {
                    $title = $file['title'] ?? $file['name'] ?? 'File';
                    $path = $file['path'] ?? '';
                    $description = $file['description'] ?? '';

                    if (! empty($path)) {
                        if (! empty($description)) {
                            $content[] = "- [{$title}]({$path}) - {$description}";
                        } else {
                            $content[] = "- [{$title}]({$path})";
                        }
                    }
                }
            }
        }

        return implode("\n", $content);
    }

    /**
     * Extract assets from content for tracking
     */
    public function extractContentAssets(): array
    {
        $assets = [
            'images' => [],
            'files' => [],
        ];

        // Extract from learning_materials if present
        if (! empty($this->learning_materials) && isset($this->learning_materials['files'])) {
            foreach ($this->learning_materials['files'] as $file) {
                if (! empty($file['path'])) {
                    $assets['files'][] = [
                        'filename' => basename($file['path']),
                        'original_name' => $file['name'] ?? basename($file['path']),
                        'path' => $file['path'],
                        'size' => $file['size'] ?? null,
                        'type' => $file['type'] ?? null,
                        'uploaded_at' => $this->updated_at->toISOString(),
                        'referenced_in_content' => true,
                    ];
                }
            }
        }

        // Extract from embedded_images if present
        if (! empty($this->embedded_images)) {
            foreach ($this->embedded_images as $image) {
                if (! empty($image['path'])) {
                    $assets['images'][] = [
                        'filename' => basename($image['path']),
                        'path' => $image['path'],
                        'size' => $image['size'] ?? null,
                        'uploaded_at' => $this->updated_at->toISOString(),
                        'referenced_in_content' => true,
                    ];
                }
            }
        }

        return $assets;
    }

    /**
     * Clean up orphaned files
     */
    public function cleanupOrphanedAssets(): void
    {
        if (empty($this->content_assets)) {
            return;
        }

        $referencedFiles = [];

        // Parse learning_content to find referenced files
        if (! empty($this->learning_content)) {
            preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $this->learning_content, $matches);
            if (! empty($matches[2])) {
                $referencedFiles = array_merge($referencedFiles, $matches[2]);
            }
        }

        $updatedAssets = $this->content_assets;

        // Check files
        if (! empty($updatedAssets['files'])) {
            foreach ($updatedAssets['files'] as $index => $file) {
                $isReferenced = in_array($file['path'], $referencedFiles) ||
                               in_array($file['filename'], $referencedFiles);
                $updatedAssets['files'][$index]['referenced_in_content'] = $isReferenced;

                // Mark for potential cleanup if not referenced
                if (! $isReferenced) {
                    $updatedAssets['files'][$index]['orphaned'] = true;
                }
            }
        }

        // Check images
        if (! empty($updatedAssets['images'])) {
            foreach ($updatedAssets['images'] as $index => $image) {
                $isReferenced = in_array($image['path'], $referencedFiles) ||
                               in_array($image['filename'], $referencedFiles);
                $updatedAssets['images'][$index]['referenced_in_content'] = $isReferenced;

                // Mark for potential cleanup if not referenced
                if (! $isReferenced) {
                    $updatedAssets['images'][$index]['orphaned'] = true;
                }
            }
        }

        $this->content_assets = $updatedAssets;
        $this->save();
    }

    /**
     * Get content in unified format (with fallback to legacy)
     */
    public function getUnifiedContent(): string
    {
        if ($this->migrated_to_unified && ! empty($this->learning_content)) {
            return $this->learning_content;
        }

        // Fallback to converting on-the-fly without saving
        return $this->convertToUnifiedMarkdown();
    }

    /**
     * Get legacy materials for backward compatibility
     */
    public function getLegacyMaterials(): array
    {
        if (! $this->migrated_to_unified) {
            return $this->learning_materials ?? [];
        }

        // If migrated, try to extract from unified content for compatibility
        return $this->extractLegacyMaterials();
    }

    /**
     * Extract legacy materials format from unified content
     */
    protected function extractLegacyMaterials(): array
    {
        if (empty($this->learning_content)) {
            return [];
        }

        $materials = [
            'videos' => [],
            'links' => [],
            'files' => [],
        ];

        // Extract links from markdown
        preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $this->learning_content, $matches);

        if (! empty($matches[1]) && ! empty($matches[2])) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $title = $matches[1][$i];
                $url = $matches[2][$i];

                // Determine type based on URL
                $videoInfo = self::parseVideoUrl($url);
                if ($videoInfo) {
                    $materials['videos'][] = [
                        'title' => $title,
                        'url' => $url,
                        'type' => $videoInfo['type'],
                    ];
                } elseif (preg_match('/\.(pdf|doc|docx|xls|xlsx|zip|rar)$/i', $url)) {
                    $materials['files'][] = [
                        'title' => $title,
                        'name' => basename($url),
                        'path' => $url,
                    ];
                } else {
                    $materials['links'][] = [
                        'title' => $title,
                        'url' => $url,
                    ];
                }
            }
        }

        return $materials;
    }

    /**
     * Check if topic is using unified content system
     */
    public function isUnified(): bool
    {
        return $this->migrated_to_unified;
    }

    /**
     * Get content assets with defaults
     */
    public function getContentAssets(): array
    {
        return $this->content_assets ?? [
            'images' => [],
            'files' => [],
        ];
    }

    /**
     * Check if topic has content assets
     */
    public function hasContentAssets(): bool
    {
        $assets = $this->getContentAssets();

        return ! empty($assets['images']) || ! empty($assets['files']);
    }

    /**
     * Scope to get non-migrated topics
     */
    public function scopeNotMigrated($query)
    {
        return $query->where('migrated_to_unified', false);
    }

    /**
     * Scope to get migrated topics
     */
    public function scopeMigrated($query)
    {
        return $query->where('migrated_to_unified', true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unit_id' => $this->unit_id,
            'title' => $this->title,
            'description' => $this->description,
            'content_format' => $this->content_format,
            'content_metadata' => $this->getContentMetadata(),
            'embedded_images' => $this->getEmbeddedImages(),
            'learning_materials' => $this->getLegacyMaterials(), // Use compatibility method
            'learning_content' => $this->getUnifiedContent(),
            'content_assets' => $this->getContentAssets(),
            'migrated_to_unified' => $this->migrated_to_unified,
            'is_unified' => $this->isUnified(),
            'estimated_minutes' => $this->estimated_minutes,
            'estimated_duration' => $this->getEstimatedDuration(),
            'prerequisites' => $this->prerequisites,
            'required' => $this->required,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'has_prerequisites_met' => true, // Simplified for now
            'has_learning_materials' => $this->hasLearningMaterials(),
            'learning_materials_count' => $this->getLearningMaterialsCount(),
            'has_content_assets' => $this->hasContentAssets(),
            'has_rich_content' => $this->hasRichContent(),
            'has_embedded_images' => $this->hasEmbeddedImages(),
            'reading_time' => $this->getReadingTime(),
            'word_count' => $this->getWordCount(),
        ];
    }
}

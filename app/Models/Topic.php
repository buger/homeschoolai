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
 * @property int $estimated_minutes
 * @property array|null $prerequisites
 * @property bool $required
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Unit $unit
 * @property-read \App\Models\Subject $subject
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model> $sessions
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
        'learning_materials',
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
        'prerequisites' => 'array', // JSON array handling
        'required' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'estimated_minutes' => 30,
        'learning_materials' => null,
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unit_id' => $this->unit_id,
            'title' => $this->title,
            'description' => $this->description,
            'learning_materials' => $this->learning_materials,
            'estimated_minutes' => $this->estimated_minutes,
            'estimated_duration' => $this->getEstimatedDuration(),
            'prerequisites' => $this->prerequisites,
            'required' => $this->required,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'has_prerequisites_met' => true, // Simplified for now
            'has_learning_materials' => $this->hasLearningMaterials(),
            'learning_materials_count' => $this->getLearningMaterialsCount(),
        ];
    }
}

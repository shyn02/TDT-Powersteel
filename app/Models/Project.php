<?php

namespace App\Models;

use App\Support\MediaUploadRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tag', 'image', 'location', 'title', 'info',
        'is_published', 'sort_order', 'migration_key',
        // Retained for Tasks 1-3 legacy-shape tests (pre-conversion rows).
        // Post-conversion these columns no longer exist; they are never set.
        'date_added', 'contractor', 'project_name', 'value',
        'status', 'is_priority', 'encoded_by', 'created_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
        // Legacy-shape casts (Tasks 1-3 tests run pre-conversion).
        'date_added' => 'date',
        'value' => 'decimal:2',
        'is_priority' => 'boolean',
    ];

    /**
     * Published, non-trashed projects in homepage order.
     *
     * SoftDeletes global scope already excludes trashed rows.
     *
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Timestamps are enabled for the public shape, but Tasks 1-3 tests
     * rebuild the legacy `projects` table without an `updated_at` column.
     * Disable automatic timestamp writes only while that column is absent
     * so the same model serves both shapes locally.
     */
    public function usesTimestamps(): bool
    {
        if (! $this->timestamps) {
            return false;
        }

        try {
            return \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), $this->getUpdatedAtColumn());
        } catch (\Throwable) {
            return $this->timestamps;
        }
    }

    /**
     * Public URL for the project image.
     *
     * - null/blank → null
     * - `/static/images/...` → passthrough (imported legacy cards)
     * - otherwise → Storage public-disk URL
     */
    public function getImageUrlAttribute(): ?string
    {
        return MediaUploadRules::url($this->image);
    }
}

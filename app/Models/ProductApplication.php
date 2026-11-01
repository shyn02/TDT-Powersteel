<?php

namespace App\Models;

use App\Support\MapPosition;
use App\Support\MediaUploadRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One clickable circle on the Product Applications map, paired with the
 * Application video that plays when a visitor opens it.
 */
class ProductApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'migration_key', 'label', 'image', 'video_file', 'description',
        'x', 'y', 'anchor_x', 'anchor_y', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'x' => 'float',
        'y' => 'float',
        'anchor_x' => 'float',
        'anchor_y' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Map order: explicit sort_order, then creation order as a tie-break. */
    public function scopeInMapOrder(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Thumbnail URL, resolved through the shared media contract. */
    public function thumbnailUrl(): ?string
    {
        return MediaUploadRules::url($this->image);
    }

    /** Application video URL, resolved through the shared media contract. */
    public function videoUrl(): ?string
    {
        return MediaUploadRules::url($this->video_file);
    }

    /**
     * A Product Application with no video still renders its circle, but is
     * not interactive: there is nothing to play.
     */
    public function isPlayable(): bool
    {
        return $this->videoUrl() !== null;
    }

    /** The circle centre on the map artwork. */
    public function position(): MapPosition
    {
        return MapPosition::from($this->x, $this->y);
    }

    /** Whether this circle draws a leader line to the artwork. */
    public function hasAnchor(): bool
    {
        return $this->anchor_x !== null && $this->anchor_y !== null;
    }

    /** The point the leader line ends on, or null when there is no line. */
    public function anchorPosition(): ?MapPosition
    {
        return $this->hasAnchor()
            ? MapPosition::from($this->anchor_x, $this->anchor_y)
            : null;
    }

    /** Map labels sit on the outer side of the circle. */
    public function labelSide(): string
    {
        return $this->position()->x() < 50 ? 'left' : 'right';
    }
}

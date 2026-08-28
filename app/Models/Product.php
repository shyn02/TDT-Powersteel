<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'specs', 'sizes',
        'description', 'image', 'is_active', 'calculator_type',
    ];

    /**
     * Formula keys admin can pick from — must stay in sync with
     * TYPE_CONFIG in public/static/calculator.js. Null/omitted means
     * "auto-detect by product name or category" (the original behavior).
     */
    public const CALCULATOR_TYPES = [
        'round_bar' => 'Round Bar',
        'flat_bar' => 'Flat Bar',
        'square_bar' => 'Square Bar',
        'angle_bar' => 'Angle Bar',
        'plate' => 'Plate',
        'sheet' => 'Sheet',
        'pipe' => 'Pipe',
        'tube' => 'Tube',
        'beam' => 'Beam (Channel / Wide Flange)',
        'i_beam' => 'I-Beam',
        't_bar' => 'T-Bar',
        'z_bar' => 'Z-Bar',
        'purlin' => 'Purlin (C/Z)',
        'sheet_pile' => 'Sheet Pile',
        'wire_mesh' => 'Wire Mesh',
        'roofing' => 'Roofing / Panels',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** Specs split into individual lines, ignoring blank lines — for bullet points. */
    public function specsList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->specs))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
    }

    /** Sizes split into individual lines — populates the quote modal dropdown. */
    public function sizesList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->sizes))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'specs', 'sizes',
        'description', 'image', 'is_active',
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

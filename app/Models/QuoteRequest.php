<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    public $timestamps = false; // only created_at, no updated_at (matches Django model)

    protected $fillable = [
        'category_id', 'product_id', 'full_name', 'company_name', 'email',
        'phone', 'address', 'how_heard', 'estimated_qty', 'status',
        'is_seen', 'source', 'created_at',
    ];

    protected $casts = [
        'is_seen' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Full name plus company in parentheses, e.g. for admin list titles / Excel export rows. */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name
            ? "{$this->full_name} ({$this->company_name})"
            : $this->full_name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'title', 'slug', 'tag', 'excerpt', 'cover_image', 'body',
        'is_featured', 'is_active', 'published_date',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'published_date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug'; // matches Django's /blog/<slug>/ URL pattern
    }
}

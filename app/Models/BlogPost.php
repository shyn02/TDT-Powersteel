<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
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

    /**
     * `body` is rendered on the public blog with Blade's raw {!! !!}, so
     * it's sanitized here on write rather than trusting every caller to
     * escape/clean it on read. See App\Support\HtmlSanitizer for why.
     */
    public function setBodyAttribute(?string $value): void
    {
        $this->attributes['body'] = HtmlSanitizer::clean($value);
    }
}

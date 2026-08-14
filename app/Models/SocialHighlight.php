<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialHighlight extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform', 'tag_label', 'badge_label', 'title', 'description',
        'link_url', 'embed_permalink', 'handle', 'video_file', 'is_active', 'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

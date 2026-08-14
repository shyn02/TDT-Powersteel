<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_highlights', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', [
                'instagram_embed', 'facebook_embed', 'instagram_profile',
                'tiktok_profile', 'youtube_profile',
            ])->default('instagram_embed');
            $table->string('tag_label', 40)->default('Instagram');
            $table->string('badge_label', 40)->default('Featured Post');
            $table->string('title', 200);
            $table->string('description', 300);
            $table->string('link_url');
            $table->string('embed_permalink')->nullable();
            $table->string('handle', 100)->nullable();
            $table->string('video_file')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_highlights');
    }
};

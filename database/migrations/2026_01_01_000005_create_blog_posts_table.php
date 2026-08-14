<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->enum('tag', ['Guides', 'Logistics', 'Sustainability', 'Company News', 'Announcement'])
                ->default('Guides');
            $table->string('excerpt', 300);
            $table->string('cover_image')->nullable();
            $table->longText('body'); // WYSIWYG HTML content
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('published_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};

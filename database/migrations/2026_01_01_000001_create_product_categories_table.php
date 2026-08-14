<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique(); // used in URL e.g. 'steel-bars'
            $table->string('image')->nullable();          // small square icon (Products grid tile)
            $table->string('banner_image')->nullable();    // wide banner behind category page title
            $table->text('banner_desc')->nullable();
            $table->text('intro_desc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};

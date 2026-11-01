<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_applications', function (Blueprint $table) {
            $table->id();
            // Immutable handle for the seeded rows: lets the deploy seeder
            // repair an edited label without matching on editable text.
            $table->string('migration_key', 50)->nullable()->unique();
            $table->string('label', 100);
            $table->string('image')->nullable();       // circle thumbnail, clipped round on the map
            $table->string('video_file')->nullable();  // application video played in the modal
            $table->text('description')->nullable();   // optional, shown in the modal when set
            $table->decimal('x', 5, 2)->default(50);   // circle centre, % of artwork width
            $table->decimal('y', 5, 2)->default(50);   // circle centre, % of artwork height
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_applications');
    }
};

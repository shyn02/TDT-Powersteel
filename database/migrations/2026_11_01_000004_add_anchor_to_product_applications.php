<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_applications', function (Blueprint $table) {
            // Where the leader line points on the artwork, as a percentage.
            // Null means no line is drawn for this circle.
            $table->decimal('anchor_x', 5, 2)->nullable()->after('y');
            $table->decimal('anchor_y', 5, 2)->nullable()->after('anchor_x');
        });
    }

    public function down(): void
    {
        Schema::table('product_applications', function (Blueprint $table) {
            $table->dropColumn(['anchor_x', 'anchor_y']);
        });
    }
};

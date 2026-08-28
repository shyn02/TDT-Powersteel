<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets admin explicitly assign which weight-calculator formula a
     * product uses (round_bar, plate, pipe, etc. — matching the
     * TYPE_CONFIG keys in public/static/calculator.js).
     *
     * Nullable and defaults to null on purpose: null means "auto-detect",
     * preserving the existing name/category matching behavior in
     * calculator.js for every product that doesn't set this explicitly.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('calculator_type', 32)->nullable()->after('sizes');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('calculator_type');
        });
    }
};

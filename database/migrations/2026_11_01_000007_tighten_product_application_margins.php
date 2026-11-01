<?php

use Database\Seeders\ProductApplicationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Pull the four outermost circles in a little.
     *
     * At desktop widths their Map labels extended past the edge of the
     * artwork, which made the map's horizontal scroll wrapper show a
     * scrollbar. The labels now have room at every width where they are
     * visible.
     */
    public function up(): void
    {
        (new ProductApplicationSeeder)->run();
    }

    public function down(): void
    {
        // Positions are content; there is nothing to undo.
    }
};

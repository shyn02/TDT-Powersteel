<?php

use Database\Seeders\ProductApplicationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Spread the seeded circles out into the margin.
     *
     * The first import kept the circle centres close to the artwork, which
     * left neighbouring circles and their labels crowding each other. The
     * seeder now carries the wider positions; re-running it updates the
     * twelve seeded rows by `migration_key` and touches nothing else.
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

<?php

use Database\Seeders\ProductApplicationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Populate the leader-line anchors on rows imported before the anchor
     * columns existed. The seeder upserts by `migration_key`, so this only
     * touches the twelve seeded rows and never a row marketing created.
     */
    public function up(): void
    {
        (new ProductApplicationSeeder)->run();
    }

    public function down(): void
    {
        // Nothing to undo: the anchor columns are dropped by the schema
        // migration and admin-set anchors are ordinary content.
    }
};

<?php

use Database\Seeders\ProductApplicationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Move the seeded Product Application thumbnails onto the public disk.
     *
     * The first import pointed `image` at the committed static artwork, which
     * the admin file uploader cannot preview or replace (it resolves paths
     * against the public disk). This re-runs the seeder, which copies each
     * thumbnail into the managed directory and upserts the same twelve rows
     * by their immutable `migration_key`. Safe to run on a database that has
     * already imported them, and a no-op for rows added by marketing.
     */
    public function up(): void
    {
        (new ProductApplicationSeeder)->run();
    }

    public function down(): void
    {
        // Nothing to undo: the previous migration's rollback removes the rows,
        // and the copied files are ordinary managed media.
    }
};

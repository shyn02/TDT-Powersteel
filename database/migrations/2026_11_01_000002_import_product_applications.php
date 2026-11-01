<?php

use Database\Seeders\ProductApplicationSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deployment rollout for the twelve approved Product Applications.
     *
     * The page is only useful with its circles in place, and the positions
     * were measured from the approved artwork, so they ship with the schema
     * rather than waiting on data entry. The seeder upserts by
     * `migration_key`, so a retried deploy repairs the rows instead of
     * duplicating them.
     */
    public function up(): void
    {
        if (! Schema::hasTable('product_applications')) {
            throw new RuntimeException(
                'Product Application import failed: [product_applications] is missing; run the create-table migration first.'
            );
        }

        (new ProductApplicationSeeder)->run();
    }

    public function down(): void
    {
        DB::table('product_applications')
            ->whereIn('migration_key', ProductApplicationSeeder::keys())
            ->delete();
    }
};

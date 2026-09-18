<?php

use Database\Seeders\HomepageProjectSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Post-conversion homepage import (Ruling R2, spec §4.5).
     *
     * Runs after 000005 converted `projects` to the public content shape
     * and invokes the idempotent `HomepageProjectSeeder`, which upserts the
     * ten verbatim first-set cards by `migration_key`. This dedicated data
     * migration is the deployment rollout — never general destructive
     * reseeding via `DatabaseSeeder`. Re-running only refreshes the ten
     * keyed rows and never touches the read-only
     * `legacy_projects_20260918` archive or unkeyed rows.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'migration_key')) {
            throw new RuntimeException(
                'Homepage project import failed: public [projects.migration_key] is missing; run the conversion migration first.'
            );
        }

        (new HomepageProjectSeeder)->run();
    }

    /**
     * Remove only the ten seeded homepage keys.
     *
     * Leaves unkeyed (legacy-converted or admin-created) rows and the
     * read-only legacy archive untouched.
     */
    public function down(): void
    {
        DB::table('projects')
            ->whereIn('migration_key', [
                'homepage-legacy-01',
                'homepage-legacy-02',
                'homepage-legacy-03',
                'homepage-legacy-04',
                'homepage-legacy-05',
                'homepage-legacy-06',
                'homepage-legacy-07',
                'homepage-legacy-08',
                'homepage-legacy-09',
                'homepage-legacy-10',
            ])
            ->delete();
    }
};

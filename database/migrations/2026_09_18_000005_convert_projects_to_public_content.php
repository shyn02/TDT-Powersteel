<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert `projects` from the legacy pipeline shape to the public
     * homepage content shape (spec §4.5).
     *
     * Ordered, MySQL-safe DDL:
     * 1. Re-run the Task 1 archive copy's reconciliation gate (000004).
     *    Any count/ID/checksum mismatch throws before any column is
     *    touched, so no legacy column can disappear silently. MySQL DDL
     *    commits implicitly, hence the separate gate migration plus this
     *    re-check in the same deployment procedure.
     * 2. ADD public columns first (`tag, image, location, title, info,
     *    is_published, sort_order, migration_key, updated_at`). Image/title
     *    stay nullable at the DB layer so existing legacy rows convert
     *    without a backfill; required-ness is enforced at validation/UI
     *    (Task 5). `is_published` defaults false and `sort_order` defaults
     *    0 so converted rows are safely unpublished/ordered.
     * 3. Add UNIQUE on `migration_key` (nullable: many NULLs allowed, one
     *    use per non-null key) and the named composite public-read index
     *    `(is_published, deleted_at, sort_order, id)`.
     * 4. DROP legacy pipeline columns only afterward
     *    (`date_added, contractor, project_name, value, status,
     *    is_priority, encoded_by`). `created_at` and the existing
     *    `deleted_at` soft-delete column are preserved untouched.
     *
     * SQLite compatibility: adds happen before drops; the `encoded_by`
     * foreign key is dropped first (ignored on drivers without it); legacy
     * columns drop one per ALTER so SQLite's table-rebuild path stays
     * safe. The `legacy_projects_20260918` archive is never dropped here.
     */
    public function up(): void
    {
        // 1. Hard gate: throws on any count/ID/checksum mismatch.
        $gate = require database_path('migrations/2026_09_18_000004_reconcile_legacy_projects.php');
        $gate->up();

        // Already converted (e.g. re-run after a green deploy): keep the
        // gate's protection but skip DDL so reruns stay idempotent.
        if ($this->isConverted()) {
            $this->ensureIndexes();
            return;
        }

        // 2. ADD public columns first.
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'tag')) {
                $table->string('tag')->nullable();
            }
            if (! Schema::hasColumn('projects', 'image')) {
                // Nullable at DB layer; Task 5 validation requires it.
                $table->string('image', 500)->nullable();
            }
            if (! Schema::hasColumn('projects', 'location')) {
                $table->string('location')->nullable();
            }
            if (! Schema::hasColumn('projects', 'title')) {
                // Nullable at DB layer so legacy rows convert; UI requires it.
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('projects', 'info')) {
                $table->text('info')->nullable();
            }
            if (! Schema::hasColumn('projects', 'is_published')) {
                $table->boolean('is_published')->default(false);
            }
            if (! Schema::hasColumn('projects', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (! Schema::hasColumn('projects', 'migration_key')) {
                $table->string('migration_key')->nullable();
            }
            if (! Schema::hasColumn('projects', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        // 3. UNIQUE + named composite index after the columns exist.
        $this->ensureIndexes();

        // 4. DROP legacy columns only after the gate passed and the new
        // columns/indexes exist.
        if (Schema::hasColumn('projects', 'encoded_by')) {
            try {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropForeign(['encoded_by']);
                });
            } catch (\Throwable $e) {
                // SQLite / already-dropped FK: dropping the column below
                // rebuilds the table without it. Never hide gate errors —
                // this catch is FK-drop only, after the gate already passed.
            }
        }

        foreach (['date_added', 'contractor', 'project_name', 'value', 'status', 'is_priority', 'encoded_by'] as $column) {
            if (Schema::hasColumn('projects', $column)) {
                Schema::table('projects', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Intentionally irreversible during stabilization.
     *
     * Rolling back would need to resurrect dropped pipeline columns whose
     * only surviving copy is the read-only `legacy_projects_20260918`
     * archive. Coordinated backup restore — not DDL reversal — is the
     * supported rollback (spec §9). If homepage rendering fails, omit the
     * section; do not run this down migration.
     *
     * @throws \RuntimeException always.
     */
    public function down(): void
    {
        throw new RuntimeException(
            'Irreversible stabilization migration: projects conversion cannot be rolled back; '
            .'restore from the pre-conversion backup with the retained legacy_projects_20260918 archive.'
        );
    }

    private function isConverted(): bool
    {
        if (! Schema::hasColumn('projects', 'migration_key')) {
            return false;
        }

        foreach (['date_added', 'contractor', 'project_name', 'value', 'status', 'is_priority', 'encoded_by'] as $column) {
            if (Schema::hasColumn('projects', $column)) {
                return false;
            }
        }

        return true;
    }

    private function ensureIndexes(): void
    {
        if (! self::indexExists('projects', 'projects_migration_key_unique')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unique('migration_key', 'projects_migration_key_unique');
            });
        }

        if (! self::indexExists('projects', 'projects_public_read_index')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(
                    ['is_published', 'deleted_at', 'sort_order', 'id'],
                    'projects_public_read_index'
                );
            });
        }
    }

    private static function indexExists(string $table, string $name): bool
    {
        try {
            foreach (Schema::getIndexes($table) as $index) {
                if (($index['name'] ?? null) === $name) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            // Fall back to a driver-native check (e.g. older introspection).
            try {
                if (DB::getDriverName() === 'sqlite') {
                    foreach (DB::select("PRAGMA index_list('{$table}')") as $row) {
                        $row = (array) $row;
                        if (($row['name'] ?? null) === $name) {
                            return true;
                        }
                    }
                }
            } catch (\Throwable $ignored) {
                // Introspection failed: let the caller attempt creation and
                // surface any real duplicate-index error.
            }

            return false;
        }
    }
};

<?php

use App\Models\Project;
use App\Support\LegacyProjectChecksum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Archive every legacy `projects` row before any destructive schema
     * conversion (spec §4.5).
     *
     * - `original_project_id` is the unique upsert key: re-running the copy
     *   updates a changed payload/checksum and can never duplicate a row.
     * - All rows are copied, including soft-deleted rows (`withTrashed`).
     * - Every legacy scalar is retained (`deleted_at` included) plus
     *   `archived_at` and the canonical 64-char `source_checksum`.
     * - All payload columns are nullable so no legacy row is ever rejected;
     *   malformed numerics throw loudly via LegacyProjectChecksum instead of
     *   being silently archived.
     * - No foreign key to `users`: this is a read-only snapshot and the
     *   encoder row may already be gone.
     * - No Eloquent model, Filament resource, or writer may target this
     *   table; it is read-only retention until a separately approved
     *   retention decision after stabilization.
     *
     * The table-creation step is guarded so re-invoking this migration
     * (tests, or a re-run after an interrupted copy) only re-runs the
     * idempotent copy.
     */
    public function up(): void
    {
        if (! Schema::hasTable('legacy_projects_20260918')) {
            Schema::create('legacy_projects_20260918', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('original_project_id')->unique();
                $table->date('date_added')->nullable();
                $table->string('contractor', 200)->nullable();
                $table->text('project_name')->nullable();
                $table->decimal('value', 16, 2)->nullable();
                $table->string('status', 50)->nullable();
                $table->boolean('is_priority')->nullable();
                $table->unsignedBigInteger('encoded_by')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->char('source_checksum', 64);
            });
        }

        $this->copyLegacyProjects();
    }

    /**
     * Reverse the archive-table creation only. Never run against a database
     * whose archive is the sole surviving copy of legacy rows.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_projects_20260918');
    }

    /**
     * Copy all source rows (including trashed) into the archive via an
     * idempotent upsert keyed by `original_project_id`.
     */
    public function copyLegacyProjects(): void
    {
        Project::withTrashed()->orderBy('id')->chunkById(500, function ($projects) {
            $archivedAt = now()->setTimezone('UTC')->format('Y-m-d H:i:s');

            $rows = [];
            foreach ($projects as $project) {
                $payload = LegacyProjectChecksum::payload($project);

                $rows[] = [
                    'original_project_id' => $project->getKey(),
                    'date_added' => $payload['date_added'],
                    'contractor' => $payload['contractor'],
                    'project_name' => $payload['project_name'],
                    'value' => $payload['value'],
                    'status' => $payload['status'],
                    'is_priority' => $payload['is_priority'],
                    'encoded_by' => $payload['encoded_by'],
                    // Canonical payload timestamps are UTC ISO-8601; store
                    // them as UTC datetimes. Reads parse naive strings as
                    // UTC, so recomputed checksums round-trip exactly.
                    'created_at' => self::dbTimestamp($payload['created_at']),
                    'deleted_at' => self::dbTimestamp($payload['deleted_at']),
                    'archived_at' => $archivedAt,
                    'source_checksum' => LegacyProjectChecksum::checksum($payload),
                ];
            }

            if ($rows !== []) {
                DB::table('legacy_projects_20260918')->upsert($rows, ['original_project_id'], [
                    'date_added',
                    'contractor',
                    'project_name',
                    'value',
                    'status',
                    'is_priority',
                    'encoded_by',
                    'created_at',
                    'deleted_at',
                    'archived_at',
                    'source_checksum',
                ]);
            }
        });
    }

    private static function dbTimestamp(?string $iso): ?string
    {
        if ($iso === null) {
            return null;
        }

        return (new DateTimeImmutable($iso))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
};

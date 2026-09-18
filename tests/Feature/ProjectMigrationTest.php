<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Support\LegacyProjectChecksum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 1 — canonicalize and archive legacy project rows.
 *
 * Covers spec §4.5: every legacy scalar is copied into the read-only
 * `legacy_projects_20260918` table (including soft-deleted rows) with a
 * canonical SHA-256 checksum, and re-running the copy updates a changed
 * payload without duplicating rows.
 *
 * Task 2 will extend this suite with reconciliation-gate cases (missing
 * IDs, count mismatch, payload/checksum mismatch, unique-key rejection
 * per Ruling R1). Those cases must preserve the archive coverage below.
 */
class ProjectMigrationTest extends TestCase
{
    private const ARCHIVE_TABLE = 'legacy_projects_20260918';

    private const PAYLOAD_ORDER = [
        'date_added',
        'contractor',
        'project_name',
        'value',
        'status',
        'is_priority',
        'encoded_by',
        'created_at',
        'deleted_at',
    ];

    private function runArchiveMigration(): void
    {
        $migration = require database_path('migrations/2026_09_18_000003_archive_legacy_projects.php');
        $migration->up();
    }

    private function makeProject(array $overrides = []): Project
    {
        return Project::create(array_merge([
            'date_added' => '2026-09-10',
            'contractor' => 'Acme Builders',
            'project_name' => 'Warehouse retrofit',
            'value' => 1500.50,
            'status' => 'ongoing',
            'is_priority' => true,
            'encoded_by' => null,
            'created_at' => Carbon::parse('2026-09-10 20:00:00', 'Asia/Manila'),
        ], $overrides));
    }

    public function test_checksum_payload_uses_exact_field_order_and_canonical_forms(): void
    {
        $payload = LegacyProjectChecksum::payload([
            'date_added' => Carbon::parse('2026-09-10 00:00:00', 'Asia/Manila'),
            'contractor' => 'Acme Builders',
            'project_name' => 'Warehouse retrofit',
            'value' => 1500.5,
            'status' => 'ongoing',
            'is_priority' => 1,
            'encoded_by' => '7',
            // 20:00 +08:00 is 12:00 UTC.
            'created_at' => Carbon::parse('2026-09-10 20:00:00', 'Asia/Manila'),
            'deleted_at' => null,
        ]);

        $this->assertSame(self::PAYLOAD_ORDER, array_keys($payload), 'Payload field order must match the spec contract.');
        $this->assertSame('2026-09-10', $payload['date_added'], 'Date must canonicalize to Y-m-d.');
        $this->assertSame('1500.50', $payload['value'], 'Value must be two-decimal fixed-point text.');
        $this->assertTrue($payload['is_priority'], 'is_priority must be a JSON boolean.');
        $this->assertSame(7, $payload['encoded_by'], 'encoded_by must be an integer.');
        $this->assertSame('2026-09-10T12:00:00Z', $payload['created_at'], 'Timestamps must be UTC ISO-8601.');
        $this->assertNull($payload['deleted_at'], 'Nulls must stay JSON nulls.');

        $checksum = LegacyProjectChecksum::checksum($payload);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $checksum, 'Checksum must be lowercase SHA-256 hex.');
        $this->assertSame(
            hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            $checksum,
            'Checksum must be SHA-256 over the unescaped UTF-8 JSON array.'
        );
    }

    public function test_checksum_preserves_nulls_unicode_and_slashes_unescaped(): void
    {
        $payload = LegacyProjectChecksum::payload([
            'date_added' => null,
            'contractor' => 'Müller-Bau / Straße — 日本語テスト 🏗️',
            'project_name' => 'TDT “Powersteel” — phase/2 — façade/mañana — 中文',
            'value' => null,
            'status' => null,
            'is_priority' => null,
            'encoded_by' => null,
            'created_at' => null,
            'deleted_at' => null,
        ]);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('日本語テスト', $json, 'UTF-8 must not be \\u-escaped.');
        $this->assertStringContainsString('phase/2', $json, 'Slashes must not be escaped.');
        $this->assertStringNotContainsString('\\/', $json, 'Escaped slashes must not appear.');
        $this->assertStringNotContainsString('\\u', strtolower($json), 'Unicode escapes must not appear.');
        $this->assertNull($payload['encoded_by']);
        $this->assertNull($payload['deleted_at']);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', LegacyProjectChecksum::checksum($payload));
    }

    public function test_archive_migration_copies_all_rows_including_soft_deleted(): void
    {
        $normal = $this->makeProject();
        $nulls = $this->makeProject([
            'contractor' => 'Solo contractor',
            'project_name' => 'Nullables check',
            'value' => 0,
            'status' => 'for_bidding',
            'is_priority' => false,
            'encoded_by' => null,
        ]);
        $unicode = $this->makeProject([
            'contractor' => 'Müller-Bau / Straße — 日本語テスト 🏗️',
            'project_name' => 'TDT “Powersteel” — phase/2 — 中文',
            'value' => 9876543.21,
            'status' => 'won',
        ]);
        $trashed = $this->makeProject(['project_name' => 'Archived pipeline job']);
        $trashed->delete();

        $this->runArchiveMigration();

        $this->assertTrue(Schema::hasTable(self::ARCHIVE_TABLE), 'Archive table must exist.');

        foreach (['original_project_id', 'date_added', 'contractor', 'project_name', 'value', 'status', 'is_priority', 'encoded_by', 'created_at', 'deleted_at', 'archived_at', 'source_checksum'] as $column) {
            $this->assertTrue(Schema::hasColumn(self::ARCHIVE_TABLE, $column), "Archive must retain [{$column}].");
        }

        $sourceCount = Project::withTrashed()->count();
        $this->assertSame($sourceCount, DB::table(self::ARCHIVE_TABLE)->count(), 'Every source row, including trashed, must be archived.');

        foreach ([$normal, $nulls, $unicode, $trashed] as $source) {
            $archived = DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $source->getKey())->first();

            $this->assertNotNull($archived, "Source id [{$source->getKey()}] must occur in the archive.");
            $this->assertNotNull($archived->archived_at, 'Archive copy must stamp archived_at.');

            // Recompute from a trashed-aware fresh copy so deleted_at is exact.
            $fresh = Project::withTrashed()->find($source->getKey());
            $expected = LegacyProjectChecksum::payload($fresh);

            $this->assertSame($expected['date_added'], $archived->date_added);
            $this->assertSame($expected['contractor'], $archived->contractor);
            $this->assertSame($expected['project_name'], $archived->project_name);
            $this->assertSame($expected['value'], number_format((float) $archived->value, 2, '.', ''));
            $this->assertSame($expected['status'], $archived->status);
            $this->assertSame($expected['is_priority'], (bool) $archived->is_priority);
            $this->assertSame($expected['encoded_by'], $archived->encoded_by === null ? null : (int) $archived->encoded_by);
            $this->assertSame($expected['created_at'], LegacyProjectChecksum::payload(['created_at' => $archived->created_at] + array_fill_keys(self::PAYLOAD_ORDER, null))['created_at']);
            $this->assertSame(LegacyProjectChecksum::checksum($expected), $archived->source_checksum, 'Stored checksum must match the recomputed canonical checksum.');
        }

        $trashedArchived = DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $trashed->getKey())->first();
        $this->assertNotNull($trashedArchived->deleted_at, 'Soft-deleted rows must retain deleted_at in the archive.');
    }

    public function test_archive_rerun_updates_changed_payload_without_duplicates(): void
    {
        $project = $this->makeProject(['contractor' => 'First name', 'value' => 100]);

        $this->runArchiveMigration();
        $first = DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $project->getKey())->first();
        $this->assertNotNull($first);

        // Idempotent rerun with no changes: same checksum, no extra rows.
        $this->runArchiveMigration();
        $repeat = DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $project->getKey())->first();
        $this->assertSame(1, DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $project->getKey())->count());
        $this->assertSame($first->source_checksum, $repeat->source_checksum);

        // Changed source payload: rerun updates the archive row in place.
        $project->update(['contractor' => 'Renamed contractor', 'value' => 250.75]);
        $this->runArchiveMigration();

        $updated = DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $project->getKey())->first();
        $this->assertSame(1, DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $project->getKey())->count(), 'Rerun must upsert, never duplicate.');
        $this->assertSame(1, DB::table(self::ARCHIVE_TABLE)->count(), 'Archive must hold exactly one row per source id.');
        $this->assertSame('Renamed contractor', $updated->contractor);
        $this->assertNotSame($first->source_checksum, $updated->source_checksum, 'Changed payload must change the checksum.');

        $expected = LegacyProjectChecksum::payload(Project::withTrashed()->find($project->getKey()));
        $this->assertSame(LegacyProjectChecksum::checksum($expected), $updated->source_checksum);
    }
}

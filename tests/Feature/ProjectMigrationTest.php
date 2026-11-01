<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Support\LegacyProjectChecksum;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tasks 1–3 — archive, reconcile, convert, and import homepage cards.
 *
 * Task 1 (preserved): every legacy scalar is copied into the read-only
 * `legacy_projects_20260918` table (including soft-deleted rows) with a
 * canonical SHA-256 checksum, and re-running the copy updates a changed
 * payload without duplicating rows.
 *
 * Task 2 (Ruling R1): reconciliation succeeds only when source/archive
 * counts match, every source ID occurs once, and every recomputed checksum
 * matches. Duplicate-ID evidence is proven at the uniqueness boundary
 * (unique `original_project_id` rejects a second archive row); missing IDs,
 * count mismatch, and payload/checksum mismatch are proven as hard gates
 * that throw before any legacy column is dropped. Conversion re-runs the
 * gate, then moves `projects` to the public fields while retaining the
 * archive; its `down()` is intentionally irreversible.
 *
 * Task 3 (Ruling R2): the dedicated post-conversion
 * `HomepageProjectSeeder` upserts exactly the ten verbatim first-set
 * homepage cards (`homepage-legacy-01`–`10`, sort 1–10, published, static
 * image paths retained) by `migration_key`; re-running corrects an edited
 * title without adding an eleventh row. Rollout is the dedicated
 * post-conversion data migration invoking the seeder, never general
 * destructive reseeding.
 */
class ProjectMigrationTest extends TestCase
{
    private const ARCHIVE_TABLE = 'legacy_projects_20260918';

    private const RECONCILE_MIGRATION = '2026_09_18_000004_reconcile_legacy_projects.php';

    private const CONVERT_MIGRATION = '2026_09_18_000005_convert_projects_to_public_content.php';

    private const COMPOSITE_INDEX = 'projects_public_read_index';

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

    private const LEGACY_COLUMNS = [
        'date_added',
        'contractor',
        'project_name',
        'value',
        'status',
        'is_priority',
        'encoded_by',
    ];

    private const PUBLIC_COLUMNS = [
        'tag',
        'image',
        'location',
        'title',
        'info',
        'is_published',
        'sort_order',
        'migration_key',
        'updated_at',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase migrates to latest (including the Task 2
        // conversion once it exists). Legacy-dependent cases need the
        // pre-conversion shape, so rebuild it deterministically here.
        // Post-conversion assertions run after explicitly invoking the
        // conversion migration within the test itself.
        $this->resetProjectsToLegacySchema();
    }

    private function resetProjectsToLegacySchema(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('projects');

            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->date('date_added');
                $table->string('contractor', 200);
                $table->text('project_name');
                $table->decimal('value', 16, 2)->default(0);
                $table->string('status', 50)->default('for_bidding');
                $table->boolean('is_priority')->default(false);
                $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->softDeletes();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        if (Schema::hasTable(self::ARCHIVE_TABLE)) {
            DB::table(self::ARCHIVE_TABLE)->delete();
        }
    }

    private function runArchiveMigration(): void
    {
        $migration = require database_path('migrations/2026_09_18_000003_archive_legacy_projects.php');
        $migration->up();
    }

    private function runReconcileMigration(): void
    {
        $migration = require database_path('migrations/'.self::RECONCILE_MIGRATION);
        $migration->up();
    }

    private function runConvertMigration(): void
    {
        $migration = require database_path('migrations/'.self::CONVERT_MIGRATION);
        $migration->up();
    }

    private function runHomepageSeeder(): void
    {
        (new \Database\Seeders\HomepageProjectSeeder)->run();
    }

    /**
     * Verbatim first-set homepage fixture from
     * resources/views/home.blade.php:240-364 (second marquee set ignored).
     *
     * @return array<string, array{tag: string, image: string, location: string, title: string, info: string, sort_order: int}>
     */
    private static function expectedHomepageCards(): array
    {
        return [
            'homepage-legacy-01' => [
                'tag' => 'RENEWABLE ENERGY INFRASTRUCTURE',
                'image' => '/static/images/steel1.jpg',
                'location' => 'TIWI, ALBAY & BATANGAS–LAGUNA',
                'title' => 'Tiwi & Mak-ban Geothermal Powerplant',
                'info' => 'Supplied robust steel products that supported the construction and operational requirements of these major geothermal facilities, which harness the Earth’s natural heat to generate reliable and renewable electricity for the Luzon grid.',
                'sort_order' => 1,
            ],
            'homepage-legacy-02' => [
                'tag' => 'Commercial Building',
                'image' => '/static/images/steel3.jpg',
                'location' => 'Pasay City, Metro Manila',
                'title' => 'SM Mall of Asia',
                'info' => 'Supplied quality steel materials to support the structural and construction requirements of one of the Philippines’ major shopping, leisure, and entertainment destinations.',
                'sort_order' => 2,
            ],
            'homepage-legacy-03' => [
                'tag' => 'HOTEL & INTEGRATED RESORT',
                'image' => '/static/images/steel2.jpg',
                'location' => 'PARAÑAQUE CITY, METRO MANILA',
                'title' => 'OKADA MANILA',
                'info' => 'Supplied quality steel materials to support the structural and construction requirements of this 30-hectare luxury integrated resort, featuring hotels, dining, retail, entertainment, and event facilities.',
                'sort_order' => 3,
            ],
            'homepage-legacy-04' => [
                'tag' => 'CONVENTION & EXHIBITION CENTER',
                'image' => '/static/images/steel4.jpg',
                'location' => 'PASAY CITY, METRO MANILA',
                'title' => 'SMX CONVENTION CENTER MANILA',
                'info' => 'Supplied quality steel materials to support the structural and construction requirements of one of the Philippines’ leading venues for conventions, exhibitions, corporate functions, and large-scale events.',
                'sort_order' => 4,
            ],
            'homepage-legacy-05' => [
                'tag' => 'TRANSPORTATION INFRASTRUCTURE',
                'image' => '/static/images/steel6.jpg',
                'location' => 'METRO MANILA TO BACOOR, CAVITE',
                'title' => 'LRT-1 CAVITE EXTENSION',
                'info' => 'Contributed quality steel materials to the development of this major railway expansion, designed to improve public transportation and provide faster, more convenient travel between Metro Manila and Cavite.',
                'sort_order' => 5,
            ],
            'homepage-legacy-06' => [
                'tag' => 'HOTEL & HOSPITALITY',
                'image' => '/static/images/Manila-hotel.jpg',
                'location' => 'ERMITA, MANILA',
                'title' => 'THE MANILA HOTEL',
                'info' => 'Provided dependable steel materials for the construction and improvement requirements of this historic luxury hotel, helping preserve its distinguished character while supporting modern hospitality operations.',
                'sort_order' => 6,
            ],
            'homepage-legacy-07' => [
                'tag' => 'INTEGRATED RESORT & ENTERTAINMENT COMPLEX',
                'image' => '/static/images/steel5.jpg',
                'location' => 'PASAY CITY, METRO MANILA',
                'title' => 'NEWPORT WORLD RESORTS',
                'info' => 'Delivered reliable steel materials that contributed to the development of this landmark destination, bringing together luxury hotels, dining establishments, retail spaces, entertainment venues, and event facilities within one integrated complex.',
                'sort_order' => 7,
            ],
            'homepage-legacy-08' => [
                'tag' => 'COMMERCIAL & RETAIL DEVELOPMENT',
                'image' => '/static/images/SM-SAN LAZARO.jpg',
                'location' => 'SANTA CRUZ, MANILA',
                'title' => 'SM CITY SAN LAZARO',
                'info' => 'Supported the development of this major urban shopping destination through the supply of dependable steel materials for its structural and construction needs.',
                'sort_order' => 8,
            ],
            'homepage-legacy-09' => [
                'tag' => 'POWER & ENERGY INFRASTRUCTURE',
                'image' => '/static/images/GN-POWERPLANT.jpg',
                'location' => 'MARIVELES, BATAAN',
                'title' => 'GN POWER MARIVELES ENERGY CENTER',
                'info' => 'Furnished durable steel products that supported the development of this large-scale energy facility, built to strengthen power generation capacity and help meet the growing electricity requirements of the Luzon grid.',
                'sort_order' => 9,
            ],
            'homepage-legacy-10' => [
                'tag' => 'ROAD & TRANSPORTATION INFRASTRUCTURE',
                'image' => '/static/images/NLEX-.jpg',
                'location' => 'METRO MANILA TO CENTRAL AND NORTHERN LUZON',
                'title' => 'NORTH LUZON EXPRESSWAY',
                'info' => 'Contributed durable steel products for the construction and improvement of this major expressway, helping support safer roadways, smoother mobility, and more efficient movement of people and goods between Metro Manila and Northern Luzon.',
                'sort_order' => 10,
            ],
        ];
    }

    private function assertLegacyColumnsPresent(): void
    {
        foreach (array_merge(self::LEGACY_COLUMNS, ['created_at', 'deleted_at']) as $column) {
            $this->assertTrue(
                Schema::hasColumn('projects', $column),
                "Legacy column [{$column}] must still be present when the gate halts."
            );
        }
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

    public function test_reconciliation_passes_when_archive_matches_source(): void
    {
        $this->makeProject(['contractor' => 'Gate pass one']);
        $trashed = $this->makeProject(['project_name' => 'Gate pass trashed']);
        $trashed->delete();

        $this->runArchiveMigration();

        $this->runReconcileMigration();

        $this->assertSame(
            Project::withTrashed()->count(),
            DB::table(self::ARCHIVE_TABLE)->count(),
            'Matching source/archive counts must pass the gate.'
        );
        $this->assertLegacyColumnsPresent();
    }

    public function test_reconciliation_rejects_tampered_checksum_without_exposing_payload(): void
    {
        $sensitive = 'SensitiveContractorXYZ123';
        $project = $this->makeProject(['contractor' => $sensitive]);

        $this->runArchiveMigration();

        DB::table(self::ARCHIVE_TABLE)
            ->where('original_project_id', $project->getKey())
            ->update(['contractor' => 'Tampered value', 'source_checksum' => str_repeat('0', 64)]);

        try {
            $this->runReconcileMigration();
            $this->fail('Tampered archive payload/checksum must throw before conversion.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString((string) $project->getKey(), $e->getMessage(), 'Mismatch error must name the offending ID.');
            $this->assertStringNotContainsString($sensitive, $e->getMessage(), 'Gate error must not leak sensitive payload text.');
            $this->assertStringNotContainsString('Tampered value', $e->getMessage(), 'Gate error must not leak tampered payload text.');
        }

        $this->assertLegacyColumnsPresent();
        $this->assertTrue(Schema::hasTable(self::ARCHIVE_TABLE), 'Archive must be retained after a halted gate.');
    }

    public function test_reconciliation_rejects_missing_archive_id_and_preserves_legacy_columns(): void
    {
        $kept = $this->makeProject(['contractor' => 'Kept row']);
        $removed = $this->makeProject(['contractor' => 'Removed row']);

        $this->runArchiveMigration();

        DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $removed->getKey())->delete();

        try {
            $this->runReconcileMigration();
            $this->fail('Missing archive ID must throw before conversion.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString((string) $removed->getKey(), $e->getMessage(), 'Missing-ID error must name the absent ID.');
            $this->assertStringNotContainsString('Removed row', $e->getMessage(), 'Gate error must not leak payload text.');
        }

        $this->assertLegacyColumnsPresent();
        $this->assertNotNull(
            DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $kept->getKey())->first(),
            'Unrelated archive rows must remain after a halted gate.'
        );
    }

    public function test_reconciliation_rejects_extra_source_row_count_mismatch(): void
    {
        $this->makeProject(['contractor' => 'Archived row']);
        $this->runArchiveMigration();

        $extra = $this->makeProject(['contractor' => 'Unarchived row']);

        try {
            $this->runReconcileMigration();
            $this->fail('Source/archive count mismatch must throw before conversion.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString((string) $extra->getKey(), $e->getMessage(), 'Count-mismatch error must name the unarchived ID.');
        }

        $this->assertLegacyColumnsPresent();
    }

    public function test_archive_rejects_duplicate_original_project_id(): void
    {
        $project = $this->makeProject();

        $this->runArchiveMigration();

        $existing = DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $project->getKey())->first();
        $this->assertNotNull($existing);

        $duplicate = (array) $existing;
        unset($duplicate['id']);

        try {
            DB::table(self::ARCHIVE_TABLE)->insert($duplicate);
            $this->fail('Duplicate original_project_id must be rejected by the unique key (R1).');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertMatchesRegularExpression('/unique|duplicate/i', $e->getMessage(), 'Rejection must come from the unique constraint.');
        }

        $this->assertSame(
            1,
            DB::table(self::ARCHIVE_TABLE)->where('original_project_id', $project->getKey())->count(),
            'Archive must still hold exactly one row per source ID.'
        );
    }

    public function test_conversion_succeeds_with_public_schema_unique_key_and_named_index(): void
    {
        $live = $this->makeProject(['project_name' => 'Live legacy row']);
        $trashed = $this->makeProject(['project_name' => 'Trashed legacy row']);
        $trashed->delete();
        $trashedId = $trashed->getKey();
        $liveId = $live->getKey();

        $this->runArchiveMigration();
        $archiveCount = DB::table(self::ARCHIVE_TABLE)->count();

        $this->runConvertMigration();

        foreach (self::PUBLIC_COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('projects', $column), "Converted projects must have [{$column}].");
        }

        foreach (self::LEGACY_COLUMNS as $column) {
            $this->assertFalse(Schema::hasColumn('projects', $column), "Legacy column [{$column}] must be dropped after a passing gate.");
        }

        $this->assertTrue(Schema::hasColumn('projects', 'created_at'), 'created_at must be preserved.');
        $this->assertTrue(Schema::hasColumn('projects', 'deleted_at'), 'deleted_at soft-delete column must be preserved.');

        $this->assertTrue(Schema::hasTable(self::ARCHIVE_TABLE), 'Archive table must be retained after conversion.');
        $this->assertSame($archiveCount, DB::table(self::ARCHIVE_TABLE)->count(), 'Archive rows must survive conversion.');
        $this->assertSame($archiveCount, DB::table('projects')->count(), 'No live row may disappear during conversion.');

        $trashedRow = DB::table('projects')->where('id', $trashedId)->first();
        $this->assertNotNull($trashedRow, 'Soft-deleted rows must survive conversion.');
        $this->assertNotNull($trashedRow->deleted_at, 'Soft-deleted rows must retain deleted_at after conversion.');

        $liveRow = DB::table('projects')->where('id', $liveId)->first();
        $this->assertNotNull($liveRow);
        $this->assertFalse((bool) $liveRow->is_published, 'Converted rows default to unpublished.');
        $this->assertSame(0, (int) $liveRow->sort_order, 'Converted rows default to sort_order 0.');
        $this->assertNull($liveRow->migration_key, 'migration_key starts nullable for legacy rows.');

        // Unique migration_key boundary: duplicates rejected, multiple NULLs allowed.
        DB::table('projects')->where('id', $liveId)->update(['migration_key' => 'homepage-legacy-01']);

        try {
            DB::table('projects')->insert([
                'tag' => 'dup',
                'image' => '/static/images/dup.jpg',
                'location' => null,
                'title' => 'Duplicate key probe',
                'info' => null,
                'is_published' => false,
                'sort_order' => 0,
                'migration_key' => 'homepage-legacy-01',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Duplicate migration_key must be rejected by its unique constraint.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertMatchesRegularExpression('/unique|duplicate/i', $e->getMessage());
        }

        $this->assertNamedCompositeIndexExists();
    }

    public function test_conversion_halts_on_checksum_mismatch_and_preserves_legacy_columns(): void
    {
        $project = $this->makeProject(['contractor' => 'Pre-conversion gate']);
        $this->runArchiveMigration();

        DB::table(self::ARCHIVE_TABLE)
            ->where('original_project_id', $project->getKey())
            ->update(['source_checksum' => str_repeat('f', 64)]);

        try {
            $this->runConvertMigration();
            $this->fail('Conversion must re-run reconciliation and halt on mismatch.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString((string) $project->getKey(), $e->getMessage());
        }

        $this->assertLegacyColumnsPresent();
        $this->assertFalse(Schema::hasColumn('projects', 'migration_key'), 'Public columns must not appear when the gate halts.');
        $this->assertTrue(Schema::hasTable(self::ARCHIVE_TABLE), 'Archive must be retained after halted conversion.');
    }

    public function test_conversion_down_throws_irreversible_stabilization_exception(): void
    {
        $this->makeProject();
        $this->runArchiveMigration();
        $this->runConvertMigration();

        $migration = require database_path('migrations/'.self::CONVERT_MIGRATION);

        try {
            $migration->down();
            $this->fail('Conversion down() must throw an irreversible-stabilization exception.');
        } catch (\RuntimeException $e) {
            $this->assertMatchesRegularExpression('/irreversible/i', $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable(self::ARCHIVE_TABLE), 'Archive must survive the down() refusal.');
    }

    public function test_homepage_seeder_imports_exact_ten_cards_idempotently(): void
    {
        // Empty legacy start keeps the exact-ten assertion deterministic:
        // archive + convert run on zero rows, so the seeder alone owns all
        // ten projects rows below.
        $this->runArchiveMigration();
        $this->runConvertMigration();

        $expected = self::expectedHomepageCards();

        $this->runHomepageSeeder();
        $this->runHomepageSeeder();

        $this->assertTrue(Schema::hasTable(self::ARCHIVE_TABLE), 'Archive must be retained after the homepage import.');

        $rows = DB::table('projects')->orderBy('sort_order')->orderBy('id')->get();

        $this->assertCount(10, $rows, 'Seeder must produce exactly ten projects rows from an empty conversion.');
        $this->assertSame(10, DB::table('projects')->whereNotNull('migration_key')->count(), 'All ten rows must carry a migration key.');

        $keys = $rows->pluck('migration_key')->all();
        $this->assertSame(array_keys($expected), $keys, 'Keys must match homepage-legacy-01..10 in order.');

        foreach ($rows as $row) {
            $fixture = $expected[$row->migration_key];

            $this->assertSame($fixture['tag'], $row->tag, "Tag mismatch for [{$row->migration_key}].");
            $this->assertSame($fixture['image'], $row->image, "Image mismatch for [{$row->migration_key}].");
            $this->assertSame($fixture['location'], $row->location, "Location mismatch for [{$row->migration_key}].");
            $this->assertSame($fixture['title'], $row->title, "Title mismatch for [{$row->migration_key}].");
            $this->assertSame($fixture['info'], $row->info, "Info mismatch for [{$row->migration_key}].");
            $this->assertSame($fixture['sort_order'], (int) $row->sort_order, "Sort order mismatch for [{$row->migration_key}].");
            $this->assertTrue((bool) $row->is_published, "Row [{$row->migration_key}] must be published.");
            $this->assertStringStartsWith('/static/images/', (string) $row->image, "Row [{$row->migration_key}] must retain its static image path.");
        }

        // Editable text is key-addressed: corrupting a title is repaired by
        // key on the next run without adding an eleventh row.
        DB::table('projects')->where('migration_key', 'homepage-legacy-02')->update(['title' => 'Edited title']);

        $this->runHomepageSeeder();

        $this->assertSame(10, DB::table('projects')->count(), 'Rerun must upsert by key, never add an eleventh row.');
        $this->assertSame(
            $expected['homepage-legacy-02']['title'],
            DB::table('projects')->where('migration_key', 'homepage-legacy-02')->value('title'),
            'Rerun must restore the verbatim title by migration key.'
        );
    }

    private function assertNamedCompositeIndexExists(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $list = DB::select("PRAGMA index_list('projects')");
            $names = array_map(fn ($row) => is_array($row) ? ($row['name'] ?? null) : ($row->name ?? null), $list);

            $this->assertContains(
                self::COMPOSITE_INDEX,
                $names,
                'Named composite public-read index must exist.'
            );

            // PRAGMA index_info already returns the indexed column names
            // (seqno, cid, name); no cid->name remap is needed.
            $info = DB::select("PRAGMA index_info('".self::COMPOSITE_INDEX."')");
            $ordered = [];
            foreach ($info as $row) {
                $row = (array) $row;
                $ordered[(int) $row['seqno']] = $row['name'];
            }
            ksort($ordered);

            $indexed = array_values($ordered);

            $this->assertSame(
                ['is_published', 'deleted_at', 'sort_order', 'id'],
                $indexed,
                'Composite index must cover (is_published, deleted_at, sort_order, id) in order.'
            );

            return;
        }

        // MySQL / other drivers: fall back to Laravel's index introspection.
        $indexes = Schema::getIndexes('projects');
        $found = null;
        foreach ($indexes as $index) {
            $name = $index['name'] ?? null;
            if ($name === self::COMPOSITE_INDEX) {
                $found = $index;
                break;
            }
        }

        $this->assertNotNull($found, 'Named composite public-read index must exist.');
        $this->assertSame(
            ['is_published', 'deleted_at', 'sort_order', 'id'],
            $found['columns'] ?? [],
            'Composite index must cover (is_published, deleted_at, sort_order, id) in order.'
        );
    }
}

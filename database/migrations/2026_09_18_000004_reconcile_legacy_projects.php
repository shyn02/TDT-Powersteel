<?php

use App\Models\Project;
use App\Support\LegacyProjectChecksum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hard reconciliation gate before any destructive project conversion
     * (spec §4.5).
     *
     * Succeeds only when:
     * - the archive table exists,
     * - source count (including trashed) equals archive count,
     * - every source ID occurs once in the archive (no missing/extra IDs),
     * - every recomputed canonical checksum equals its stored checksum.
     *
     * Any mismatch throws a RuntimeException and stops the deployment
     * before an old column is dropped. Error details name only IDs, counts,
     * and checksum state — never legacy payload text (contractor,
     * project_name, etc.) — so logs cannot leak sensitive content.
     *
     * This migration changes no schema; it is safe to re-run. The
     * conversion migration re-invokes this gate first.
     */
    public function up(): void
    {
        $this->reconcile();
    }

    /**
     * Verification has no schema change to reverse.
     */
    public function down(): void
    {
        // No-op: reconciliation never mutates schema or data.
    }

    /**
     * Run the count/ID/checksum gate. Public so the conversion migration
     * can re-run the exact same check before destructive DDL.
     *
     * @throws \RuntimeException on any mismatch.
     */
    public function reconcile(): void
    {
        $archive = 'legacy_projects_20260918';

        if (! Schema::hasTable($archive)) {
            throw new RuntimeException(
                'Legacy project reconciliation failed: archive table [legacy_projects_20260918] is missing; run the archive migration first.'
            );
        }

        $sourceCount = Project::withTrashed()->count();
        $archiveCount = DB::table($archive)->count();

        if ($sourceCount !== $archiveCount) {
            throw new RuntimeException(sprintf(
                'Legacy project reconciliation failed: source/archive count mismatch (source=%d, archive=%d); refusing conversion.',
                $sourceCount,
                $archiveCount
            ));
        }

        $sourceIds = Project::withTrashed()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $archivedRows = DB::table($archive)->orderBy('original_project_id')->get(['original_project_id', 'source_checksum']);

        $archiveIds = [];
        $checksums = [];
        foreach ($archivedRows as $row) {
            $id = (int) $row->original_project_id;
            $archiveIds[] = $id;
            // Unique key guarantees one row per ID; keep first checksum so a
            // hypothetical duplicate state still reports a mismatch instead
            // of silently passing.
            if (! array_key_exists($id, $checksums)) {
                $checksums[$id] = (string) $row->source_checksum;
            }
        }

        $missing = array_values(array_diff($sourceIds, $archiveIds));
        $extra = array_values(array_diff($archiveIds, $sourceIds));

        if ($missing !== [] || $extra !== []) {
            throw new RuntimeException(
                'Legacy project reconciliation failed: '.$this->summarizeIds('missing archive IDs', $missing).'; '
                .$this->summarizeIds('extra archive IDs', $extra).'; refusing conversion.'
            );
        }

        $mismatched = [];

        Project::withTrashed()->orderBy('id')->chunkById(500, function ($projects) use ($checksums, &$mismatched) {
            foreach ($projects as $project) {
                $id = (int) $project->getKey();
                $expected = LegacyProjectChecksum::checksum(LegacyProjectChecksum::payload($project));

                if (! hash_equals((string) ($checksums[$id] ?? ''), $expected)) {
                    $mismatched[] = $id;
                }
            }
        });

        if ($mismatched !== []) {
            sort($mismatched);

            throw new RuntimeException(
                'Legacy project reconciliation failed: checksum mismatch for '.$this->summarizeIds('original_project_id(s)', $mismatched).'; refusing conversion.'
            );
        }
    }

    /**
     * Format an ID list without payload. Caps at 20 IDs so a large table
     * cannot flood logs; the count always shows the full scope.
     */
    private function summarizeIds(string $label, array $ids): string
    {
        $ids = array_map('intval', $ids);
        sort($ids);

        $shown = array_slice($ids, 0, 20);
        $text = $shown === [] ? 'none' : implode(', ', $shown);

        if (count($ids) > count($shown)) {
            $text .= sprintf(' (+%d more)', count($ids) - count($shown));
        }

        return sprintf('%s [%s] (count=%d)', $label, $text, count($ids));
    }
};

# Projects Homepage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reconcile and archive every legacy project row, repurpose Projects as the sole homepage source, import the ten static cards idempotently, and provide secure admin publishing.

**Architecture:** Three ordered migrations archive/copy, reconcile, then convert because MySQL DDL is not transactionally reversible. The public model supplies an ordered published read model; the admin resource manages it, and one Blade loop is rendered twice for the marquee while a media resolver supports imported static paths and managed public-disk images.

**Tech Stack:** Laravel 13, Eloquent, MySQL/SQLite migrations, Filament 5.7, PHPUnit 12, Blade, shared `MediaUploadRules`

**Spec:** `docs/superpowers/specs/2026-09-18-site-stabilization-design.md`

## Global Constraints

- SQLite test bootstrap precedes tests. Archive/content work may start independently; upload field integration depends on Upload Plan Task 4.
- Archive includes soft-deleted rows and every listed legacy scalar; reconciliation must stop before any old column drops.
- Do not claim transaction rollback protects MySQL DDL; do not drop `legacy_projects_20260918` or reverse conversion during stabilization.
- Import exactly immutable keys `homepage-legacy-01` through `-10`, order 1-10, verbatim first-set card content; rerun updates, never duplicates.
- Public query is published, non-trashed, `sort_order`, then ID. Escape DB text, meaningful current-image alt, duplicate `aria-hidden=true` and empty alt; omit section when empty.
- Preserve existing `AdminOnlyResource` authorization; uploads only JPG/PNG/WebP <=5 MB and require upload-plan security approval.

## File Map

| Path | Responsibility |
|---|---|
| `app/Support/LegacyProjectChecksum.php` | canonical array/JSON/SHA-256 contract. |
| `database/migrations/2026_09_18_000003_archive_legacy_projects.php` | archive table/upsert all rows. |
| `...000004_reconcile_legacy_projects.php` | count/ID/checksum hard gate. |
| `...000005_convert_projects_to_public_content.php` | post-gate schema conversion/index. |
| `database/seeders/HomepageProjectSeeder.php` | exact ten immutable records. |
| `app/Models/Project.php` | public fields/casts/scope/image URL. |
| Projects Filament resource/form/table/pages | create/edit/preview/search/sort/publish/archive. |
| `app/Http/Controllers/PageController.php` | homepage read model. |
| `resources/views/home.blade.php` | database marquee and empty omission. |
| `app/Console/Commands/PruneArchivedData.php` | exclude Projects from automatic purge. |
| `tests/Feature/ProjectMigrationTest.php`, `ProjectHomepageTest.php`, `ProjectAdminTest.php` | archive/import/read/UI coverage. |

---

### Task 1: Canonicalize and archive legacy rows

**Specialist:** database engineer

**Depends on:** Upload Plan Task 1

**Interfaces:** `LegacyProjectChecksum::payload(array|object $row): array`; `checksum(...): string`, exact order `date_added, contractor, project_name, value, status, is_priority, encoded_by, created_at, deleted_at`.

**Files:**
- Create: `app/Support/LegacyProjectChecksum.php`
- Create: `database/migrations/2026_09_18_000003_archive_legacy_projects.php`
- Create: `tests/Feature/ProjectMigrationTest.php`

- [ ] Write red tests with normal/null/Unicode/soft-deleted rows. Assert date `Y-m-d`, value `number_format(...,2,'.','')`, UTC ISO-8601 timestamps, JSON booleans/nulls, unescaped UTF-8/slashes, lowercase SHA-256; rerun updates changed payload/checksum by unique `original_project_id` without duplicates.
- [ ] Run `php artisan test --filter=ProjectMigrationTest` — expected FAIL missing archive/checksum.
- [ ] Implement support class and archive table columns: unique original ID, all legacy scalars including `deleted_at`, `archived_at`, 64-char checksum. Copy via `Project::withTrashed()->orderBy('id')->chunkById()` and `upsert`; archive remains read-only/no model/resource.
- [ ] Run focused test — expected PASS archive copy/idempotency.
- [ ] Database review and proposed commit:
```powershell
git add app/Support/LegacyProjectChecksum.php database/migrations/2026_09_18_000003_archive_legacy_projects.php tests/Feature/ProjectMigrationTest.php
```

### Task 2: Enforce reconciliation and convert schema

**Specialist:** database/security engineer

**Depends on:** Task 1

**Interfaces:** reconciliation succeeds only when source/archive counts match, every source ID occurs once, and every recomputed checksum matches.

**Files:**
- Create: `database/migrations/2026_09_18_000004_reconcile_legacy_projects.php`
- Create: `database/migrations/2026_09_18_000005_convert_projects_to_public_content.php`
- Modify: `tests/Feature/ProjectMigrationTest.php`

- [ ] Add red tests that mutate archive payload/checksum, delete archive ID, and add duplicate/mismatched evidence; each must throw before conversion and leave every old live column present.
- [ ] Run `php artisan test --filter=ProjectMigrationTest` — expected FAIL missing gate.
- [ ] Implement separate reconciliation migration that throws detailed ID/count mismatch without sensitive payload. Conversion migration first re-runs reconciliation, then creates/adds `tag,image,location,title,info,is_published,sort_order,migration_key,updated_at`, preserves existing `deleted_at`, drops legacy columns only afterward, makes migration key unique, and adds named composite `(is_published,deleted_at,sort_order,id)`. Use SQLite table rebuild where required and MySQL-safe ordered DDL. `down()` throws an intentional irreversible-stabilization exception.
- [ ] Run focused test — expected PASS success and forced-mismatch halt.
- [ ] Review actual MySQL dry-run/backup procedure; proposed commit:
```powershell
git add database/migrations/2026_09_18_000004_reconcile_legacy_projects.php database/migrations/2026_09_18_000005_convert_projects_to_public_content.php tests/Feature/ProjectMigrationTest.php
```

### Task 3: Import the ten exact homepage cards

**Specialist:** backend/content migration engineer

**Depends on:** Task 2

**Interfaces:** `HomepageProjectSeeder::run()` upserts only by `migration_key`; all ten values come verbatim from `resources/views/home.blade.php:240-364`.

**Files:**
- Create: `database/seeders/HomepageProjectSeeder.php`
- Modify: `tests/Feature/ProjectMigrationTest.php`

- [ ] Encode a red expected-fixture test containing each current first-set tag/image/location/title/info and keys/order; run seeder twice and assert exactly ten matching keys, published, static paths retained, editable title change corrected by key without row 11.
- [ ] Run `php artisan test --filter=ProjectMigrationTest` — expected FAIL missing seeder.
- [ ] Implement explicit ten-row array and `upsert(...,['migration_key'],['tag','image','location','title','info','is_published','sort_order','updated_at'])`; call from deployment seeding or a data migration after conversion, not general destructive reseeding.
- [ ] Run focused test — expected PASS and exact verbatim fixture.
- [ ] Content review against first card set; proposed commit:
```powershell
git add database/seeders/HomepageProjectSeeder.php tests/Feature/ProjectMigrationTest.php
```

### Task 4: Implement public model/read/render behavior

**Specialist:** backend/frontend/accessibility engineer

**Depends on:** Tasks 2-3; image resolver consumes Upload Plan Task 4

**Interfaces:** `Project::scopePublished(Builder $query): Builder`; accessor `image_url`; controller passes `$projects` ordered.

**Files:**
- Modify: `app/Models/Project.php`
- Modify: `app/Http/Controllers/PageController.php`
- Modify: `resources/views/home.blade.php:229-490`
- Create: `tests/Feature/ProjectHomepageTest.php`

- [ ] Write red `ProjectHomepageTest`: mixed publish/trashed/order ties, zero/one/many rows, malicious HTML in all text, static and disk paths. Assert section absent at zero; each row appears twice; second track/group has `aria-hidden="true"`, duplicate images `alt=""`; current alt derives safely from title; no hardcoded project titles remain.
- [ ] Run `php artisan test --filter=ProjectHomepageTest` — expected FAIL hardcoded output.
- [ ] Update model to normal timestamps, fillable public fields, bool/int/datetime casts, published scope ordering, image URL via `MediaUploadRules::url`. Update controller `Project::published()->get()`. Replace lines 229-490 with `@if($projects->isNotEmpty())`, a reusable Blade loop rendered once normally and once inside an aria-hidden duplicate; use `{{ }}` for all fields/URLs and conditional nullable tag/location/info.
- [ ] Run focused test — expected PASS escaping/order/accessibility/empty behavior.
- [ ] Accessibility/security review and proposed commit:
```powershell
git add app/Models/Project.php app/Http/Controllers/PageController.php resources/views/home.blade.php tests/Feature/ProjectHomepageTest.php
```

### Task 5: Repurpose the authorized admin resource

**Specialist:** Filament frontend/backend engineer

**Depends on:** Task 4 and Upload Plan Tasks 4-5 patterns

**Interfaces:** image directory `projects`; immutable hidden `migration_key` never mass-editable; `AdminOnlyResource` remains authority.

**Files:**
- Modify: `app/Filament/Admin/Resources/Projects/ProjectResource.php`
- Modify: `app/Filament/Admin/Resources/Projects/Schemas/ProjectForm.php`
- Modify: `app/Filament/Admin/Resources/Projects/Tables/ProjectsTable.php`
- Modify: `app/Filament/Admin/Resources/Projects/Pages/CreateProject.php`
- Modify: `app/Filament/Admin/Resources/Projects/Pages/EditProject.php`
- Modify: `app/Console/Commands/PruneArchivedData.php`
- Create: `tests/Feature/ProjectAdminTest.php`

- [ ] Write red `ProjectAdminTest` for admin/non-admin access; required title/image; nullable tag/location/info; sort integer; publish toggle/filter; image preview; search title/location/tag; soft-delete/restore/force-delete; accepted/rejected uploads; Create disabled during upload and server guard. Assert migration key absent from forms.
- [ ] Run `php artisan test --filter=ProjectAdminTest` — expected FAIL old pipeline fields.
- [ ] Update resource title attribute to `title`; form fields and image upload use shared rules/public `projects`, preview, required completed path; table thumbnail/title/tag/location/sort/published with sortable/searchable columns and ternary publication filter; retain archive/restore/force-delete actions and authorization. Use safe after-commit old-image deletion pattern. Remove Projects from `PruneArchivedData` so legacy/public stabilization rows are not automatically force-deleted; explicit admin permanent delete remains authorized.
- [ ] Run focused test — expected PASS.
- [ ] Security gate verifies untrusted text escaping, uploads, immutable key, authorization, deletion lifecycle. Proposed commit:
```powershell
git add app/Filament/Admin/Resources/Projects app/Console/Commands/PruneArchivedData.php tests/Feature/ProjectAdminTest.php
```

### Task 6: Migration and feature review checkpoint

**Specialist:** QA/database reviewer

**Depends on:** Tasks 1-5

**Interfaces:** Consumes archive/schema/import/admin/homepage deliverables and produces signed reconciliation and feature evidence for integrated rollout.

**Files:**
- Verify: `tests/Feature/ProjectMigrationTest.php`
- Verify: `tests/Feature/ProjectHomepageTest.php`
- Verify: `tests/Feature/ProjectAdminTest.php`
- Record results in: `docs/operations/stabilization-release-checklist.md` (created by the integrated plan)

- [ ] On a production clone, back up DB/media; record source count including trashed; run archive migration only; persist count/ID/checksum report; inject a mismatch and prove conversion stops; restore clone; run reconcile/conversion/import twice.
- [ ] Run `php artisan test --filter='ProjectMigrationTest|ProjectHomepageTest|ProjectAdminTest'; php artisan test` — expected PASS.
- [ ] Verify index via SQLite `PRAGMA index_list('projects')` and MySQL `SHOW INDEX FROM projects`; verify exactly ten keys and archive table retention.
- [ ] Proposed evidence commit: `git commit -m "test: verify projects migration and homepage"` with only runbook/evidence files staged.

## Acceptance Criteria

- Every legacy row including trashed is archived idempotently with exact canonical checksum before destructive DDL; forced mismatch stops conversion.
- Legacy archive is retained read-only and Projects are excluded from automatic pruning.
- Exactly ten verbatim cards import by immutable key and order.
- Admin securely supports all required fields, preview/search/sort/publication/archive/restore/permanent delete and safe uploads.
- Homepage uses only published/non-trashed DB rows in deterministic order, escapes text, duplicates marquee accessibly, resolves both image path forms, and omits empty section.

## Cross-Plan Handoff

- Project upload work cannot start before `2026-09-18-upload-infrastructure.md` Tasks 2-4.
- Integrated migration, homepage, upload, and Hostinger checks proceed in `2026-09-18-integrated-verification-hostinger.md` only after this plan passes.

# Upload Infrastructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Repair SQLite test bootstrap, diagnose the production Livewire upload failure from captured evidence, and provide secure Hostinger-compatible category/blog/project image storage.

**Architecture:** Laravel remains the upload boundary: Livewire owns temporary uploads and Filament forms own permanent writes to the `public` disk. A reviewed evidence report gates every upload configuration change; after diagnosis, one narrowly supported correction is applied, shared upload rules enforce JPG/PNG/WebP at 5 MB, and a controlled media route is available when `public/storage` cannot be linked.

**Tech Stack:** PHP 8.3+, Laravel 13, Livewire 4/Filament 5.7, PHPUnit 12, SQLite tests, MySQL production, local public filesystem, Hostinger PHP-FPM/shared hosting

**Spec:** `docs/superpowers/specs/2026-09-18-site-stabilization-design.md`

## Global Constraints

- Do not change upload middleware, disks, paths, session/CSRF settings, PHP settings, proxy rules, deployment caches, or the upload throttle before Task 2 evidence is captured and reviewed.
- Record exact status, headers, content type, complete body, redirect chain, request inputs, authentication/CSRF state, file metadata, and timestamp-correlated Laravel/PHP-FPM/web-server/Hostinger logs.
- Preserve authentication, authorization, CSRF, signed URL, MIME, size, payload-safety, and password protections.
- Permanent media accepts only JPG/JPEG, PNG, and WebP and is at most 5 MB at both UI and server boundaries.
- Never delete an old permanent image until the replacement record transaction commits.
- Do not require a queue worker.
- This plan precedes project upload integration and final category/blog/project upload verification.
- PowerShell commands run from repository root; stop immediately on a non-zero exit.

## File Map

| Path | Responsibility |
|---|---|
| `tests/TestCase.php` | Apply `RefreshDatabase` to every database-touching feature test. |
| `tests/Feature/DatabaseMigrationBootstrapTest.php` | Prove a fresh SQLite schema has required foreign keys and soft-delete columns. |
| `tests/Feature/ExampleTest.php`, `tests/Feature/PreviewTest.php` | Use migrated, self-contained fixtures rather than an existing database. |
| `docs/operations/upload-investigation-2026-09-18.md` | Production evidence, producing-layer diagnosis, reviewer sign-off, and selected correction. |
| `config/livewire.php` | Evidence-supported temporary disk/path/rules and final no-throttle middleware. |
| `config/filesystems.php` | Public media URL plus controlled fallback configuration. |
| `.env.example` | `PUBLIC_MEDIA_MODE` and Livewire/public disk deployment inputs. |
| `app/Support/MediaUploadRules.php` | Single constants/rules/messages contract for all permanent feature images. |
| `app/Http/Controllers/PublicMediaController.php` | Symlink-free allowlisted public-media response. |
| `routes/web.php` | Named fallback route `media.public`. |
| `app/Filament/Admin/Resources/ProductCategories/Schemas/ProductCategoryForm.php` | Category upload UI rules/directories. |
| `app/Filament/Admin/Resources/BlogPosts/Schemas/BlogPostForm.php` | Blog upload UI rules/directory. |
| `app/Filament/Admin/Resources/ProductCategories/Pages/CreateProductCategory.php` | Completed-upload server guard. |
| `app/Filament/Admin/Resources/BlogPosts/Pages/CreateBlogPost.php` | Completed-upload server guard. |
| `tests/Feature/UploadInfrastructureTest.php` | Endpoint, fallback route, MIME/size/path, guard, and old-file lifecycle tests. |
| `docs/operations/hostinger-media.md` | Writable paths, symlink/fallback setup, diagnostics, and rollback. |

---

### Task 1: Restore the SQLite migration test baseline

**Specialist:** QA/database engineer

**Depends on:** None

**Interfaces:**
- Consumes: `phpunit.xml` (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
- Produces: all `Tests\TestCase` subclasses run the full migration set via `RefreshDatabase`; subsequent plans may rely on a fresh schema.

**Files:**
- Modify: `tests/TestCase.php`
- Create: `tests/Feature/DatabaseMigrationBootstrapTest.php`
- Modify: `tests/Feature/ExampleTest.php`
- Modify: `tests/Feature/PreviewTest.php`

- [ ] **Step 1: Capture the known red baseline**

Run: `php artisan test`

Expected: FAIL includes `no such table: product_categories`, `users`, or `blog_posts`; do not treat this as a feature failure.

- [ ] **Step 2: Write the migration-bootstrap test**

Create a feature test extending `Tests\TestCase` that asserts `Schema::hasTable()` for `users`, `product_categories`, `quote_requests`, `chat_sessions`, `chat_messages`, and `projects`; asserts `deleted_at` on every model using `SoftDeletes`; and uses `PRAGMA foreign_key_list(chat_messages)` to assert the `session_id -> chat_sessions.id` relation. Add `use RefreshDatabase;` to the shared base class so each feature test receives migrations.

- [ ] **Step 3: Make existing tests deterministic**

In `PreviewTest`, create its user/blog fixtures in the test instead of calling `first()`, use `Storage::fake('public')`, and remove diagnostic `echo`. Keep `ExampleTest` as an HTTP 200 assertion, now backed by migrations.

- [ ] **Step 4: Verify green SQLite bootstrap**

Run: `php artisan test --filter=DatabaseMigrationBootstrapTest`

Expected: PASS with all required tables, foreign key, and soft-delete assertions.

Run: `php artisan test`

Expected: PASS; if a migration itself fails under SQLite, fix that migration with a driver-specific schema operation while preserving equivalent MySQL constraints, then rerun from a fresh in-memory database.

- [ ] **Step 5: Review checkpoint and proposed commit**

Reviewer checks that no test skips migrations and no production database is addressed. Proposed commit:

```powershell
git add tests/TestCase.php tests/Feature/DatabaseMigrationBootstrapTest.php tests/Feature/ExampleTest.php tests/Feature/PreviewTest.php
git commit -m "test: restore sqlite migration bootstrap"
```

### Task 2: Capture and review the production upload root cause

**Specialist:** bug-debugger (required evidence/root-cause owner), with Hostinger operations reviewer

**Depends on:** None; must finish before Tasks 3-6

**Interfaces:**
- Consumes: current deployed `POST /livewire-d988e63b/upload-file` behavior with no configuration changes.
- Produces: signed `docs/operations/upload-investigation-2026-09-18.md` containing one supported producing layer and correction branch.

**Files:**
- Create: `docs/operations/upload-investigation-2026-09-18.md`

- [ ] **Step 1: Freeze the pre-change configuration**

Record release SHA, `php -v`, `php artisan about`, `php artisan route:list --path=livewire`, redacted `.env` values for `APP_URL`, session/cache/filesystem/Livewire disks, `php -i` values for `upload_max_filesize`, `post_max_size`, `max_file_uploads`, and `sys_temp_dir`, and whether `public/storage` exists and resolves inside `storage/app/public`.

- [ ] **Step 2: Reproduce with browser developer tools**

From one authenticated admin action and exact field, upload a small valid JPEG. Export the HAR and copy the complete request/response: method, final URL, redirects, status, every response header, content type, unabridged response body, CSRF header/cookie presence (values redacted), signed inputs, session state, filename, browser-reported MIME, and byte size. Record UTC timestamp and a generated correlation label such as `upload-investigation-20260918T103015Z`.

- [ ] **Step 3: Correlate all logs before retrying**

Collect Laravel `storage/logs/laravel.log`, Hostinger/PHP-FPM error log, web-server/proxy access/error logs for ±60 seconds around the timestamp. State whether Laravel, Livewire, PHP-FPM, proxy/web server, or Hostinger generated the HTML by matching status/body/signature and log entries. Never include the raw session cookie, CSRF token, signed token, or credentials.

- [ ] **Step 4: State one falsifiable conclusion**

The report must select exactly one evidenced branch: `(A) filesystem permission/path`, `(B) session/CSRF`, `(C) signed URL/APP_URL/proxy`, `(D) rewrite/proxy routing`, `(E) PHP version/request limits/temp directory`, or `(F) stale release/config/route cache`. Include evidence that rejects the other branches and the exact configuration/code/deployment delta authorized for Task 3.

- [ ] **Step 5: Gate review**

Bug-debugger and security/operations reviewer sign and date the artifact. Expected: exact HTTP status/content type/body/redirect and matching logs are present. If any field is absent, the gate remains closed and no later upload task starts.

- [ ] **Step 6: Proposed evidence commit**

```powershell
git add docs/operations/upload-investigation-2026-09-18.md
git commit -m "docs: record livewire upload root cause"
```

### Task 3: Apply only the evidence-supported endpoint correction

**Specialist:** backend/infrastructure engineer

**Depends on:** Task 2 signed evidence

**Interfaces:**
- Consumes: the single authorized correction delta in the investigation report.
- Produces: valid authenticated uploads reach Livewire JSON handling; `config('livewire.temporary_file_upload.middleware') === null` in final target configuration.

**Files:**
- Modify only evidence-authorized files among: `config/livewire.php`, `config/filesystems.php`, `.env.example`, `bootstrap/app.php`, Hostinger rewrite/PHP settings documented in `docs/operations/hostinger-media.md`
- Create/modify: `tests/Feature/UploadInfrastructureTest.php`

- [ ] **Step 1: Write a regression test matching the producing layer**

Encode the diagnosed contract rather than the old HTML body: for Laravel/Livewire branches, assert the named `livewire.upload-file` route resolves and an authenticated malformed upload receives Livewire JSON/validation rather than an HTML host page; for host-only branches, add an executable PowerShell `Invoke-WebRequest -SkipHttpErrorCheck` smoke procedure to the evidence report with exact expected status and `application/json` response.

- [ ] **Step 2: Verify the regression is red before correction**

Run: `php artisan test --filter=UploadInfrastructureTest`

Expected: FAIL on the diagnosed path/config assertion.

- [ ] **Step 3: Apply the one authorized correction**

Use the report's exact branch only: fix ownership/path for A; same-origin secure session/CSRF settings for B; `APP_URL`/trusted scheme without trusting forwarded host for C; document-root rewrite to `public/index.php` for D; PHP 8.3+ and both request limits above 5 MB with writable temp path for E; or `php artisan optimize:clear` plus matched release assets/routes for F. Do not broaden MIME types, make storage world-writable, disable CSRF globally, trust arbitrary proxies, or combine speculative branches.

- [ ] **Step 4: Remove the upload feature throttle after evidence preservation**

Keep `temporary_file_upload.middleware` explicitly `null`; add a route/config assertion that no `ThrottleRequests` middleware is attached to `livewire.upload-file`. Preserve auth and signed preview behavior.

- [ ] **Step 5: Verify and review**

Run: `php artisan optimize:clear; php artisan test --filter=UploadInfrastructureTest`

Expected: PASS. Security reviewer confirms the patch equals the signed correction and that auth/CSRF/signing remain effective.

- [ ] **Step 6: Proposed commit**

```powershell
git add config/livewire.php config/filesystems.php .env.example bootstrap/app.php tests/Feature/UploadInfrastructureTest.php docs/operations/hostinger-media.md
git commit -m "fix: correct diagnosed livewire upload failure"
```

### Task 4: Centralize secure permanent-media rules and fallback delivery

**Specialist:** backend/security engineer

**Depends on:** Tasks 1 and 3

**Interfaces:**
- Produces: `MediaUploadRules::MIMES`, `MediaUploadRules::MAX_KILOBYTES`, `MediaUploadRules::rules(bool $required): array`, `MediaUploadRules::url(?string $path): ?string`; named route `media.public` accepts only `categories/...`, `blog/...`, or `projects/...` paths.

**Files:**
- Create: `app/Support/MediaUploadRules.php`
- Create: `app/Http/Controllers/PublicMediaController.php`
- Modify: `config/filesystems.php`, `.env.example`, `routes/web.php`
- Modify: `tests/Feature/UploadInfrastructureTest.php`

- [ ] **Step 1: Write red media-contract tests**

Assert rules accept `UploadedFile::fake()->image('ok.jpg')`, PNG, and a real minimal WebP fixture; reject GIF/SVG/PHP renamed as JPG and files above 5120 KB; assert traversal, dotfiles, non-allowlisted roots, and non-image files return 404 from fallback. Assert responses set detected image content type, `X-Content-Type-Options: nosniff`, bounded public cache, and never expose `storage/app/private`.

- [ ] **Step 2: Verify red**

Run: `php artisan test --filter=UploadInfrastructureTest`

Expected: FAIL because the support class/controller/route do not exist.

- [ ] **Step 3: Implement the exact shared contract**

Define MIME allowlist `['image/jpeg','image/png','image/webp']`, extensions `['jpg','jpeg','png','webp']`, max `5120`, and message `Image must be a JPG, PNG, or WebP file no larger than 5 MB.` `url()` returns `/static/images/...` unchanged, otherwise `Storage::disk('public')->url($path)` in symlink mode or `route('media.public', ['path' => $path])` in fallback mode.

- [ ] **Step 4: Implement controlled fallback**

Constrain `GET /media/{path}` with `where('path', '(categories|blog|projects)/[A-Za-z0-9_./-]+')`. Controller normalizes/rejects `..`, checks allowlisted prefix and extension, checks `Storage::disk('public')->exists`, detects MIME and rejects a MIME outside the allowlist, then returns a streamed response. Add `PUBLIC_MEDIA_MODE=symlink` to `.env.example`; only `fallback` selects the route URL.

- [ ] **Step 5: Green and security gate**

Run: `php artisan test --filter=UploadInfrastructureTest`

Expected: PASS. Security reviewer signs off traversal, executable-content, MIME-confusion, private-path, and authorization boundaries.

- [ ] **Step 6: Proposed commit**

```powershell
git add app/Support/MediaUploadRules.php app/Http/Controllers/PublicMediaController.php config/filesystems.php .env.example routes/web.php tests/Feature/UploadInfrastructureTest.php
git commit -m "feat: add secure public media delivery"
```

### Task 5: Harden category and blog create flows

**Specialist:** Filament frontend/backend engineer

**Depends on:** Task 4

**Interfaces:**
- Consumes: `MediaUploadRules` and public directories `categories`, `categories/banners`, `blog`.
- Produces: UI and action guards reject incomplete/invalid uploads and retain prior files until commit.

**Files:**
- Modify: `app/Filament/Admin/Resources/ProductCategories/Schemas/ProductCategoryForm.php`
- Modify: `app/Filament/Admin/Resources/BlogPosts/Schemas/BlogPostForm.php`
- Modify: `app/Filament/Admin/Resources/ProductCategories/Pages/CreateProductCategory.php`
- Modify: `app/Filament/Admin/Resources/BlogPosts/Pages/CreateBlogPost.php`
- Modify: corresponding edit pages if they currently delete replaced files eagerly
- Modify: `tests/Feature/UploadInfrastructureTest.php`

- [ ] **Step 1: Write Livewire/Filament red tests**

Test category tile, category banner, and blog cover with valid types, invalid GIF/SVG, 5121 KB input, and temporary/incomplete state. Assert Create is disabled while `isUploading` and direct action submission without a completed required image produces the shared field message and creates no record. Assert an edit transaction failure leaves the old path present.

- [ ] **Step 2: Verify red**

Run: `php artisan test --filter=UploadInfrastructureTest`

Expected: FAIL on action guard/upload-state assertions.

- [ ] **Step 3: Wire shared validation and safe lifecycle**

Configure every `FileUpload` with public disk, fixed directory, `acceptedFileTypes(MediaUploadRules::MIMES)`, `maxSize(5120)`, no user-controlled filenames, and shared validation text. Use Filament upload-state callbacks to disable Create during upload. In `mutateFormDataBeforeCreate`, require a completed category tile `image` and blog `cover_image` (category `banner_image` remains optional), validate each stored-path prefix, and schedule deletion of a replaced old image only in a `DB::afterCommit` callback.

- [ ] **Step 4: Green and focused review**

Run: `php artisan test --filter=UploadInfrastructureTest`

Expected: PASS. Reviewer verifies no record can reference a temporary path and no rollback deletes the old file.

- [ ] **Step 5: Proposed commit**

```powershell
git add app/Filament/Admin/Resources/ProductCategories app/Filament/Admin/Resources/BlogPosts tests/Feature/UploadInfrastructureTest.php
git commit -m "fix: guard category and blog image uploads"
```

### Task 6: Document and verify Hostinger media operation

**Specialist:** documentation/QA/operations engineer

**Depends on:** Tasks 3-5; project image checks wait for `2026-09-18-projects-homepage.md`

**Interfaces:**
- Produces: repeatable symlink and fallback runbooks consumed by the integrated rollout plan.

**Files:**
- Create/modify: `docs/operations/hostinger-media.md`

- [ ] **Step 1: Write exact preflight commands**

Document `php -v`, `php artisan about`, `php artisan route:list --path=livewire`, `php artisan optimize:clear`, writable checks for `storage/app/public`, `storage/framework/{cache,sessions,views}`, `bootstrap/cache`, and media directories, plus PHP upload/request limits. Require 0755 directories/0644 files or host-equivalent ownership; forbid 0777.

- [ ] **Step 2: Document both URL modes**

Primary: `php artisan storage:link` and verify `public/storage` target. Fallback: set `PUBLIC_MEDIA_MODE=fallback`, clear config cache, confirm only `/media/categories/...`, `/media/blog/...`, and `/media/projects/...` resolve. Include rollback: disable Create, preserve files/evidence, and revert only configuration; never widen permissions/MIME/public roots.

- [ ] **Step 3: Run automated verification**

Run: `php artisan test --filter='DatabaseMigrationBootstrapTest|UploadInfrastructureTest'; php artisan test`

Expected: PASS and zero queued-worker dependency.

- [ ] **Step 4: Manual checkpoint**

On Hostinger-equivalent staging, upload JPG, PNG, WebP, GIF, SVG, exactly 5 MB, and over 5 MB to category/blog fields; confirm disabled Create, server guard, actionable field message, final URL, old-file preservation, correlation log on infrastructure failure, and both symlink/fallback modes.

- [ ] **Step 5: Proposed commit**

```powershell
git add docs/operations/hostinger-media.md
git commit -m "docs: add hostinger media runbook"
```

## Acceptance Criteria

- Fresh SQLite runs all migrations and the complete test suite no longer fails for missing tables.
- The pre-fix upload response and correlated logs identify one producing layer before any correction or throttle removal.
- Final Livewire uploads have no application throttle but retain authentication, CSRF/session, signed preview, MIME, and size controls.
- Category/blog/project media accepts only JPG/JPEG, PNG, WebP through 5 MB, uses fixed non-executable directories, and refuses incomplete uploads.
- Create is disabled during upload; direct server submission cannot create a record without a completed required image.
- Replacement rollback retains the old file.
- Hostinger works with a storage symlink or allowlisted fallback without exposing private storage.

## Cross-Plan Handoff

- Task 1 unblocks feature tests in the quote/export, chat, and projects plans.
- Task 4 supplies `MediaUploadRules::url()` and the `projects` directory to the projects plan.
- Task 6's project verification remains pending until the projects plan is complete.
- All tasks feed `2026-09-18-integrated-verification-hostinger.md`.

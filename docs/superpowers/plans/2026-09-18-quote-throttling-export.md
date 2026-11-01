# Quote, Throttling, and Synchronous Export Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Persist unlimited-length quote remarks, remove only approved feature limiters/duplicate guards, and download authorized quote/contact/referral CSV exports synchronously without a worker.

**Architecture:** Keep existing request models and routes, widening only `quote_requests.estimated_qty` to text and removing approved anti-repeat mechanisms while retaining payload, validation, honeypot, authentication, and reset controls. A reusable synchronous CSV service writes an authorized query to a private temporary file in 500-row primary-key chunks before returning a delete-after-send response.

**Tech Stack:** PHP 8.3+, Laravel 13, Filament 5.7, Eloquent, PHPUnit 12, SQLite/MySQL, Symfony BinaryFileResponse/PHP `fputcsv`

**Spec:** `docs/superpowers/specs/2026-09-18-site-stabilization-design.md`

## Global Constraints

- Depends on Upload Plan Task 1 (working SQLite migrations); quote/export work may run in parallel with upload diagnosis afterward.
- Keep the existing 10 KB quote payload guard, validation, honeypot, authentication, authorization, CSRF posture, and file/payload safety controls.
- Preserve admin login limiting exactly: normalized email plus IP, settings defaults 5 attempts/15 minutes, failed password and MFA counted, successful login clears.
- Preserve password-reset route `throttle:3,1` and controller key `request-password-reset:{ip}`, 3 attempts, 300-second decay.
- Remove quote submit, product-data, chat POST, and nav-poll route throttles; upload throttle removal belongs only after the upload evidence gate.
- Do not queue exports or require a queue worker; never load the full result set into memory.
- CSV formula injection is untrusted-output handling: prefix cells beginning with `=`, `+`, `-`, or `@` with a single quote while preserving the underlying database value.

## File Map

| Path | Responsibility |
|---|---|
| `database/migrations/2026_09_18_000001_expand_quote_estimated_qty_to_text.php` | TEXT conversion with compatible rollback guard. |
| `app/Http/Controllers/QuoteController.php` | Long remarks and removal of rapid/minimum/duplicate guards. |
| `routes/web.php` | Approved feature-throttle removal; auth/reset throttle retention. |
| `app/Filament/Admin/Resources/QuoteRequests/Schemas/QuoteRequestForm.php` | Multiline, untruncated admin editing. |
| `app/Filament/Admin/Resources/QuoteRequests/Schemas/QuoteRequestInfolist.php` | Full admin display. |
| `app/Exports/CsvExportDefinition.php` | Immutable resource/filename/header/value-map contract. |
| `app/Exports/SynchronousCsvExporter.php` | Private temp file, 500-ID chunks, cleanup, response. |
| `app/Filament/Actions/SynchronousExportAction.php` | Filtered table action adapter. |
| `app/Filament/Actions/SynchronousExportBulkAction.php` | Selected-record action adapter. |
| three `app/Filament/Exports/*Exporter.php` files | Definitions preserving current labels/order. |
| three resource `Tables/*Table.php` files | Replace queued actions with synchronous adapters. |
| `tests/Feature/QuoteSubmissionTest.php` | Long text, composition, no duplicate rejection, safety limits. |
| `tests/Feature/FeatureThrottleRegressionTest.php` | Remove list and retain auth/reset protections. |
| `tests/Feature/SynchronousExportTest.php` | Scope, columns, chunks, headers, cleanup, authorization, no jobs. |

---

### Task 1: Expand and preserve quote remarks

**Specialist:** backend/database engineer

**Depends on:** Upload Plan Task 1

**Interfaces:**
- Produces: `quote_requests.estimated_qty` is TEXT; modal composition remains `"{quantity} ({subProduct} | {sizeSpec})"`.

**Files:**
- Create: `database/migrations/2026_09_18_000001_expand_quote_estimated_qty_to_text.php`
- Modify: `app/Http/Controllers/QuoteController.php:69-132,212-249`
- Modify: quote form/infolist files listed above
- Create: `tests/Feature/QuoteSubmissionTest.php`

- [ ] **Step 1: Write red long-remark tests**

POST a 151-character and a 4,000-character hero `remarks` with valid name/mobile; assert 200 and exact trimmed value in `estimated_qty`. POST a 1,500-character modal `estimatedQty` plus `subProduct=Wide Flange` and `sizeSpec=200x100`; assert the full composed value. Assert the 10 KB request still returns 413 and non-string/invalid phone still returns 422.

- [ ] **Step 2: Verify red**

Run: `php artisan test --filter=QuoteSubmissionTest`

Expected: FAIL with 422 `max:150` or truncation/schema constraint.

- [ ] **Step 3: Implement schema and request changes**

Migration uses `Schema::table('quote_requests', fn (Blueprint $table) => $table->text('estimated_qty')->change())`; `down()` must first throw if any value exceeds 150, then restore `string(...,150)` so rollback cannot truncate silently. Remove only both `max:150` rules from `estimatedQty` and hero `remarks`; retain `required|string` for modal and `nullable|string` for hero. Replace admin `TextInput::make('estimated_qty')` with `Textarea` and render infolist text with wrapping/full value.

- [ ] **Step 4: Verify green**

Run: `php artisan test --filter=QuoteSubmissionTest`

Expected: PASS with exact full values.

- [ ] **Step 5: Review and proposed commit**

Reviewer compares submitted/database/admin values byte-for-byte after Laravel's normal trim middleware. Proposed commit:

```powershell
git add database/migrations/2026_09_18_000001_expand_quote_estimated_qty_to_text.php app/Http/Controllers/QuoteController.php app/Filament/Admin/Resources/QuoteRequests tests/Feature/QuoteSubmissionTest.php
git commit -m "feat: preserve long quote remarks"
```

### Task 2: Remove approved feature limiters while locking auth protections

**Specialist:** backend/security engineer

**Depends on:** Task 1; upload throttle remains out of scope until upload evidence review

**Interfaces:**
- Produces: no feature throttles on submit quote/product data/chat/nav; no quote minimum-time/cache/duplicate suppression; unchanged auth/reset limiters.

**Files:**
- Modify: `routes/web.php:38-66`
- Modify: `app/Http/Controllers/QuoteController.php:35-68,127-129,169-171,205-207,245-247`
- Create: `tests/Feature/FeatureThrottleRegressionTest.php`

- [ ] **Step 1: Write red route/controller tests**

Inspect route middleware through `Route::getRoutes()->getByName(...)` and assert no `throttle:*` for `submit_quote`, `quote_product_data`, `chat_messages_api`, `admin.nav_counts`; assert `password.request.post` still has `throttle:3,1`. Submit identical valid quote, contact, and referral payloads rapidly six times and assert six rows with no 429/silent suppression. Submit with current `form_started_at` and assert persistence.

- [ ] **Step 2: Add auth protection regression tests**

Use `RateLimiter::clear`. Exercise `PasswordResetRequestController` four times from one IP: first three accepted, fourth contains `Too many attempts`; assert controller decay remains 300 seconds. Livewire-test `App\Filament\Admin\Pages\Auth\Login` with known password/MFA scenarios to prove failed password and invalid MFA increment the normalized-email-plus-IP key, settings values apply, and success clears it.

- [ ] **Step 3: Verify red**

Run: `php artisan test --filter=FeatureThrottleRegressionTest`

Expected: FAIL on feature middleware and duplicate/minimum-time suppression; auth assertions already pass and must stay green.

- [ ] **Step 4: Remove only approved guards**

Delete feature `->middleware('throttle:...')` calls and stale comments. Remove `$clientIp`, minimum interaction time, `$lastKey` cache access, duplicate fingerprint/RateLimiter check, cache put, and four duplicate `hit()` blocks from `QuoteController`. Keep payload guard and honeypot exactly before branching. Do not alter Login or PasswordResetRequestController.

- [ ] **Step 5: Green and security gate**

Run: `php artisan test --filter=FeatureThrottleRegressionTest`

Expected: PASS. Security reviewer confirms the exact remove/retain lists and compensating controls.

- [ ] **Step 6: Proposed commit**

```powershell
git add routes/web.php app/Http/Controllers/QuoteController.php tests/Feature/FeatureThrottleRegressionTest.php
git commit -m "fix: remove feature submission throttles"
```

### Task 3: Build the bounded synchronous CSV service

**Specialist:** backend engineer

**Depends on:** Upload Plan Task 1

**Interfaces:**
- Produces: `SynchronousCsvExporter::download(Builder $query, CsvExportDefinition $definition, CarbonInterface $at): BinaryFileResponse`; chunks by `id` at 500; correlation UUID on failure.
- `CsvExportDefinition` contains `resource: string`, `headers: array<string>`, and `row: Closure(Model): array`.

**Files:**
- Create: `app/Exports/CsvExportDefinition.php`
- Create: `app/Exports/SynchronousCsvExporter.php`
- Create: `tests/Feature/SynchronousExportTest.php`

- [ ] **Step 1: Write red service tests**

Create 1,201 records with sentinel IDs at 1/500/501/1000/1001; invoke download at frozen `2026-09-18 14:30:45`; assert exact ordered headers, 1,201 unique rows, UTF-8, `text/csv`, attachment `<resource>-20260918-143045.csv`, `Cache-Control: no-store`, and no `jobs`/`exports` rows. Include commas, quotes, newlines, Unicode, 151+ quote text, and formula-leading cells.

- [ ] **Step 2: Add cleanup/failure tests**

Point temp root at `Storage::fake('local')` or injectable test directory. Force row closure to throw on row 501; assert no response transfer starts, no temp artifact remains, log contains correlation ID, and exception maps to a danger notification in the action adapter. For success, invoke response file deletion callback/`deleteFileAfterSend(true)` and assert cleanup.

- [ ] **Step 3: Verify red**

Run: `php artisan test --filter=SynchronousExportTest`

Expected: FAIL because service/types do not exist.

- [ ] **Step 4: Implement private-file completion semantics**

Create `storage/app/tmp/exports` recursively with private permissions. Open with `fopen($path,'xb')`, write header and rows using `fputcsv`; sanitize formula-leading string cells; clone authorized query, remove unsafe ordering, order by ID, and call `chunkById(500, ...)`. Flush and close before constructing `response()->download(..., ['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'no-store, private'])->deleteFileAfterSend(true)`. In `catch/finally`, close handles and unlink unless ownership transferred; log exception with UUID and resource, never row data.

- [ ] **Step 5: Verify green**

Run: `php artisan test --filter=SynchronousExportTest`

Expected: PASS with bounded traversal and cleanup.

- [ ] **Step 6: Security review and proposed commit**

Review private path, formula injection, authorization input, log redaction, and cleanup. Proposed commit:

```powershell
git add app/Exports tests/Feature/SynchronousExportTest.php
git commit -m "feat: add synchronous csv export service"
```

### Task 4: Replace all three Filament queued actions

**Specialist:** Filament/backend engineer

**Depends on:** Task 3

**Interfaces:**
- Consumes: service signature from Task 3 and existing table-filtered `Builder`/selected `Collection` authorization scopes.
- Produces: `SynchronousExportAction` and `SynchronousExportBulkAction`; exporter classes expose `definition(): CsvExportDefinition` with current column order/labels.

**Files:**
- Create: `app/Filament/Actions/SynchronousExportAction.php`
- Create: `app/Filament/Actions/SynchronousExportBulkAction.php`
- Modify: all three exporter and table files listed in File Map
- Modify: `tests/Feature/SynchronousExportTest.php`

- [ ] **Step 1: Write red integration tests**

For each resource, apply one status filter and assert header action exports only visible authorized rows. Select noncontiguous IDs and assert bulk export contains only those IDs. Act as unauthorized/inactive user and assert 403/no file. Assert headers remain exactly: Quote (`Full Name` through `Date Submitted`), Contact (`Full Name` through `Date Submitted`), Referral (`Referrer Name` through `Date Submitted`) in current exporter order.

- [ ] **Step 2: Verify red**

Run: `php artisan test --filter=SynchronousExportTest`

Expected: FAIL because current actions dispatch Filament export jobs.

- [ ] **Step 3: Implement action adapters**

Header action receives the table's filtered query after Filament authorization; bulk action starts from the resource-authorized model query constrained with selected primary keys, not a fresh unrestricted model query. Both call the service synchronously and catch failures before returning a response, sending `Notification::make()->danger()->title('Export failed')->body("The export could not be created (reference {$id}). Please retry.")`. Never fall back to `ExportAction`, `ExportBulkAction`, or dispatch.

- [ ] **Step 4: Preserve definitions exactly**

Convert each existing `ExportColumn` list to explicit header/value closures, eager-loading `category` for quote exports. Format status with `ucfirst` and dates `Y-m-d H:i`; preserve full `estimated_qty`. Replace imports/action registrations in all three table classes.

- [ ] **Step 5: Green and authorization checkpoint**

Run: `php artisan test --filter=SynchronousExportTest`

Expected: PASS; query log demonstrates 500-row chunks and no queue inserts. Reviewer verifies filters/selections cannot broaden resource scope.

- [ ] **Step 6: Proposed commit**

```powershell
git add app/Filament/Actions app/Filament/Exports app/Filament/Admin/Resources/QuoteRequests/Tables app/Filament/Admin/Resources/ContactMessages/Tables app/Filament/Admin/Resources/Referrals/Tables tests/Feature/SynchronousExportTest.php
git commit -m "feat: download admin exports synchronously"
```

### Task 5: Capacity, full regression, and operational checkpoint

**Specialist:** QA/performance engineer

**Depends on:** Tasks 1-4

**Interfaces:** Produces capacity evidence consumed by integrated rollout.

**Files:**
- Create: `docs/operations/synchronous-export-capacity.md`

- [ ] **Step 1: Run focused and full suites**

Run: `php artisan test --filter='QuoteSubmissionTest|FeatureThrottleRegressionTest|SynchronousExportTest'; php artisan test`

Expected: PASS; login/reset regression remains green.

- [ ] **Step 2: Exercise production-sized fixtures**

In Hostinger-equivalent staging, generate/export the larger of 10,000 rows or twice each deployment-time quote/contact/referral count. Record PHP memory limit, max execution time, elapsed time, peak memory, row count, SHA-256, temp directory before/after, and confirm no worker is running.

- [ ] **Step 3: Enforce the gate**

Expected: each export completes within configured timeout/memory and temp files disappear. If not, reduce eager-loaded per-row query cost or chunk size below 500 and rerun; do not queue or load all rows.

- [ ] **Step 4: Proposed commit**

```powershell
git add docs/operations/synchronous-export-capacity.md
git commit -m "docs: record synchronous export capacity"
```

## Acceptance Criteria

- Hero and modal remarks above 150 characters persist/display/export in full, including modal product/size composition.
- Rapid identical quote/contact/referral requests persist normally; product/chat/nav routes have no feature throttle.
- Upload limiter is not touched until upload evidence sign-off.
- Login and both password-reset limiter layers remain unchanged and tested.
- Authorized filtered/selected exports download immediately with exact columns, deterministic filename, 500-ID chunking, private temporary files, cleanup, and no queue job.
- Export failure yields a correlation reference and retry notification before download starts.
- Capacity gate passes on Hostinger-equivalent limits.

## Cross-Plan Handoff

- Chat route throttle removal is asserted here; chat behavior is implemented in `2026-09-18-persistent-quick-chat.md`.
- Upload throttle remains owned by `2026-09-18-upload-infrastructure.md` after evidence review.
- Final manual long-remark CSV comparison and no-worker verification belong to `2026-09-18-integrated-verification-hostinger.md`.

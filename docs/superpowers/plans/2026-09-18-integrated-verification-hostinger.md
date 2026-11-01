# Integrated Verification and Hostinger Rollout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate all stabilization streams, prove every automated/manual acceptance criterion, and roll out safely to Hostinger with evidence-backed rollback controls.

**Architecture:** This is a gated release plan, not a fifth implementation stream. A release candidate is assembled only after upload, quote/export, chat, and projects checkpoints pass; database/media backups and project reconciliation precede ordered schema/code enablement, followed by production-like and live smoke verification in both symlink and fallback media modes.

**Tech Stack:** PHP 8.3+, Laravel 13, Filament 5.7/Livewire, PHPUnit 12, Node built-in test runner, MySQL/SQLite, PowerShell, Hostinger PHP-FPM/shared hosting

**Spec:** `docs/superpowers/specs/2026-09-18-site-stabilization-design.md`

## Global Constraints

- Depends on completion and review of all four linked plans; no partial production rollout.
- Upload correction/throttle removal requires signed exact-response/log evidence; project conversion requires persisted archive reconciliation.
- No queue worker is a final runtime prerequisite.
- Preserve login and both password-reset throttle layers, authorization, CSRF posture, HMAC chat tokens, signed upload behavior, validation, honeypot, and payload/file limits.
- Back up database and media before migrations and again after successful project conversion/import.
- Roll back application only to a database-compatible release; forward-fix additive/data migrations.
- Never reverse project conversion/delete legacy archive during stabilization; never broaden MIME, permissions, or public storage exposure to recover uploads.
- All commands run from repository root or the Hostinger application root and must have captured exit code/output.

## File Map

| Path | Responsibility |
|---|---|
| `docs/operations/stabilization-release-checklist.md` | requirement-by-requirement evidence, owners, timestamps, approvals. |
| `docs/operations/hostinger-rollout.md` | exact preflight/deploy/smoke/rollback sequence. |
| `docs/operations/upload-investigation-2026-09-18.md` | prerequisite upload diagnosis/sign-off. |
| `docs/operations/hostinger-media.md` | symlink/fallback media procedure. |
| `docs/operations/synchronous-export-capacity.md` | no-worker capacity gate. |
| all production/test files named by the four prerequisite plans | release candidate; do not introduce unrelated changes here. |

---

### Task 1: Validate prerequisite artifacts and release scope

**Specialist:** release QA/documentation engineer

**Depends on:** every task in the four linked plans

**Interfaces:** Produces a release checklist with SHA, artifact links, pass/fail, owner, reviewer, UTC timestamp; no unchecked gate may deploy.

**Files:**
- Create: `docs/operations/stabilization-release-checklist.md`
- Verify: all files and evidence named in the four prerequisite plans

- [ ] **Step 1: Inventory only intended changes**

Run:
```powershell
git status --short
```
Expected: only files mapped by the four plans plus operations evidence; no credentials, HAR cookies/tokens, logs with chat tokens, storage uploads, `.env`, vendor, or unrelated refactors.

- [ ] **Step 2: Verify hard-gate artifacts**

Checklist must link: upload exact status/headers/content type/body/redirect and correlated logs with signed root cause; SQLite baseline; export capacity for each resource; project source/archive count+ID+checksum reconciliation and forced-mismatch proof; security approvals for upload/media, chat/token/input, CSV/scope, project schema/rendering.

- [ ] **Step 3: Check API/signature consistency**

Search and record zero stale uses of legacy chat `type`, `poll:true`, `newSessionId`, client token generation, `sessionStorage` transcript authority; queued `ExportAction`/`ExportBulkAction` for the three resources; hardcoded homepage project cards; feature throttles on approved routes; quote `max:150`; and automatic project pruning. Confirm every downstream name matches: `MediaUploadRules::url`, `Project::published`, `SynchronousCsvExporter::download`, `terminate`, `contractVersion`.

- [ ] **Step 4: Proposed documentation commit**

```powershell
git add docs/operations/stabilization-release-checklist.md
git commit -m "docs: add stabilization release gate"
```

### Task 2: Run deterministic automated verification

**Specialist:** QA engineer

**Depends on:** Task 1

**Interfaces:** Produces captured test outputs and environment versions.

**Files:**
- Update evidence in: `docs/operations/stabilization-release-checklist.md`
- Verify: all focused test files named in the four prerequisite plans

- [ ] **Step 1: Clear generated state and record runtime**

Run:
```powershell
php --version
php artisan --version
php artisan optimize:clear
composer validate --strict
```
Expected: PHP satisfies `^8.3`, Laravel boots, caches clear, Composer metadata valid.

- [ ] **Step 2: Run focused suites in dependency order**

```powershell
php artisan test --filter=DatabaseMigrationBootstrapTest
php artisan test --filter='QuoteSubmissionTest|FeatureThrottleRegressionTest|SynchronousExportTest'
php artisan test --filter=ChatPersistenceTest
php artisan test --filter=UploadInfrastructureTest
php artisan test --filter='ProjectMigrationTest|ProjectHomepageTest|ProjectAdminTest'
```
Expected: every command exits 0; SQLite performs full migrations rather than using an existing DB.

- [ ] **Step 3: Run browser-unit and full PHP suites**

```powershell
npm run test:chat
php artisan test
```
Expected: both exit 0. A workstation lacking Node must use CI or a controlled Node environment; lack of Node does not waive the chat test.

- [ ] **Step 4: Verify route/middleware and queue independence**

Run `php artisan route:list --path=api` and `php artisan route:list --path=livewire`. Record that approved feature routes/upload lack throttle middleware, reset keeps `throttle:3,1`, staff routes keep auth/CSRF, and media fallback is GET allowlisted. Snapshot `jobs` count before and after each export; expected unchanged and immediate downloads with no worker process.

- [ ] **Step 5: Review checkpoint**

QA and security reviewers sign only fresh outputs from the candidate SHA; failures return to the owning plan and require the focused plus full suite again.

### Task 3: Prepare and rehearse Hostinger deployment

**Specialist:** infrastructure/database engineer

**Depends on:** Task 2

**Interfaces:** Produces exact `docs/operations/hostinger-rollout.md` and a successful production-clone rehearsal.

**Files:**
- Create: `docs/operations/hostinger-rollout.md`
- Verify: `docs/operations/hostinger-media.md`
- Verify: `docs/operations/synchronous-export-capacity.md`
- Verify: `docs/operations/upload-investigation-2026-09-18.md`

- [ ] **Step 1: Capture preflight**

Verify/document PHP version/extensions from lockfile, document root points to `public`, HTTPS/rewrite/trusted proxy behavior, MySQL version, PHP-FPM logs, `upload_max_filesize`/`post_max_size` >5 MB, execution/memory limits sufficient for recorded export capacity, and writable `storage`, framework cache/session, `bootstrap/cache`, temp export, Livewire temp, categories/blog/projects directories. Permissions must be least privilege, not 0777.

- [ ] **Step 2: Back up and checksum**

Create timestamped database dump and media archive outside document root; calculate SHA-256 and perform a restore rehearsal. Record legacy projects count including trashed and copy the ten original static definitions. Expected: backup restores to isolated database and media listing matches.

- [ ] **Step 3: Rehearse ordered deployment on clone**

Deploy test infrastructure; run archive migration and persist reconciliation; halt intentionally on injected mismatch; restore clone; run conversion/import. Apply quote schema before controller, chat schema before coordinated API/client, export service before action replacement, project schema/import before DB homepage, upload correction/throttle only after signed evidence. Run `php artisan optimize:clear`, migrations once, then cache only supported production configs.

- [ ] **Step 4: Verify both media modes**

First run symlink mode and validate link target. Then remove only staging link, set `PUBLIC_MEDIA_MODE=fallback`, clear config, and verify allowlisted category/blog/project URLs work while traversal/private/non-image paths return 404. Restore chosen production mode.

- [ ] **Step 5: Capacity and timing rehearsal**

Repeat 10,000-or-2x production exports, migrations, and smoke suite under Hostinger-equivalent timeout/memory. Expected: no worker, no temp residue, all within limits; any failure blocks live rollout.

### Task 4: Execute the production maintenance-window rollout

**Specialist:** release manager with database/infrastructure engineer

**Depends on:** Task 3 and explicit go approval

**Interfaces:** Produces deployed SHA, migration ledger, backup IDs/checksums, and smoke evidence.

**Files:**
- Update evidence in: `docs/operations/hostinger-rollout.md`
- Update evidence in: `docs/operations/stabilization-release-checklist.md`

- [ ] **Step 1: Announce maintenance and recheck drift**

Confirm deployed source SHA, production row counts, storage free space, no unreviewed config drift, and backup completion. Disable project section/Create upload actions during migration; do not reintroduce hardcoded cards.

- [ ] **Step 2: Execute exact ordered release**

Run archive/copy, reconciliation and persist output; stop on any mismatch. Run conversion/import, verify ten keys, back up again. Deploy database-compatible quote/chat/export/project code; enable upload correction only with evidence sign-off. Run `php artisan optimize:clear`, `php artisan migrate --force`, and the host-supported cache procedure. No `queue:work` command is needed.

- [ ] **Step 3: Verify data invariants before traffic**

Query full long quote column type; chat lifecycle indexes/FK; legacy archive source count/IDs/checksums; project public composite index; ten migration keys; no duplicate keys; media directories. Expected: all exact.

- [ ] **Step 4: Restore traffic progressively**

Enable read traffic and projects only after imported rows render. Enable admin Create only after upload smoke. Watch Laravel/PHP-FPM/web logs, 4xx/5xx, storage errors, failed jobs (informational only; exports must not add jobs), response times, and disk usage for at least one normal operating interval.

### Task 5: Perform complete manual acceptance matrix

**Specialist:** QA lead with frontend, backend, security, and operations reviewers

**Depends on:** Task 4

**Interfaces:** Consumes the deployed release and produces one evidence row for every design acceptance criterion.

**Files:**
- Update: `docs/operations/stabilization-release-checklist.md`

- [ ] **Quote/throttle:** Submit 151+ exact text through hero/modal, compare request/DB/admin/CSV; rapidly repeat quote/contact/referral/product/chat/nav and see no 429/suppression while malformed/10 KB/CSRF behavior remains. Trigger login and both reset limiter layers and confirm blocking.
- [ ] **Chat:** Start, refresh, close/reopen tab, restart browser, clear display cache, restore full history, poll staff reply, freeze/check activity semantics from DB, terminate, replay/write, reload. Confirm explicit codes/history/read-only/fresh state and no raw token in URL/log.
- [ ] **Exports:** For each resource export filtered and selected rows with worker stopped; verify exact columns/order/full text/filename/no-store/formula safety/temp cleanup. Force pre-download failure and verify correlation notification/no partial file.
- [ ] **Uploads:** Preserve/link diagnosis artifact; upload JPG/PNG/WebP, GIF/SVG, exactly 5 MB and oversized to category/blog/project. Verify Create-disabled, server guard, fixed paths, old-file preservation, actionable errors, logs, and current production media mode.
- [ ] **Projects:** Verify archive evidence; create/preview/search/sort/publish/unpublish/archive/restore/permanent-delete as authorized admin and denial as non-admin. Check zero/one/many homepage rows, order, escaping, alt text, duplicate aria-hidden marquee, and no hardcoded fallback.
- [ ] **Host:** Verify HTTPS/scheme, document root/rewrite, sessions/CSRF, signed Livewire URLs, writable paths, logs, memory/time limits, public URLs, and private/traversal denial.
- [ ] **Final checkpoint:** Every checklist row contains tester, UTC time, expected/actual, screenshot/request ID or query output, and pass. Any fail blocks completion.

### Task 6: Exercise rollback controls and close rollout

**Specialist:** release manager/security/operations engineer

**Depends on:** Task 5

**Interfaces:** Consumes passed acceptance evidence and produces rollback rehearsal, monitoring ownership, and final sign-offs.

**Files:**
- Update: `docs/operations/hostinger-rollout.md`
- Update: `docs/operations/stabilization-release-checklist.md`

- [ ] **Step 1: Rehearse non-destructive rollback decisions**

For application fault, deploy only a DB-compatible candidate preserving long quote text, 30-day chat/history/termination, uploads, and public projects. For homepage fault, omit section but retain rows/archive. For upload fault, disable Create and preserve evidence/files. For chat fault, preserve termination/history. Never run destructive project down migration.

- [ ] **Step 2: Define full pre-change restoration threshold**

Only if an approved maintenance rollback requires the old application, restore the pre-change DB and media backups together after preserving post-deploy submissions separately; do not point old code at converted schema.

- [ ] **Step 3: Confirm observability and ownership**

Assign owners/thresholds for upload 4xx/5xx, chat terminal/error rates, export failures/timeouts/temp residue, quote volume after limiter removal, disk usage, and project rendering. Correlation IDs must not include user content/tokens.

- [ ] **Step 4: Final full verification**

Run from the deployed artifact or identical build: `php artisan test; npm run test:chat`. Expected: zero failures. Record production smoke pass and close maintenance.

- [ ] **Step 5: Proposed commit**

```powershell
git add docs/operations/stabilization-release-checklist.md docs/operations/hostinger-rollout.md
git commit -m "docs: record hostinger stabilization rollout"
```

## Acceptance Criteria

- All ten design acceptance criteria map to signed automated and manual evidence.
- Full SQLite/PHP and client tests pass from the release candidate.
- Upload root cause and project reconciliation gates precede their changes.
- Hostinger rollout works without a queue worker and supports symlink or controlled fallback media.
- Long quotes, unrestricted approved flows, durable chat, immediate exports, secure uploads, and database projects work together.
- Login/reset protections and all non-rate security controls remain verified.
- Backups are restore-tested; rollback never truncates new data, reverses projects destructively, loses media/history, or broadens exposure.

## Critical Path

```text
Upload Plan Task 1 (SQLite migrations)
  +-- Quote/throttle/export plan -------------------------+
  +-- Persistent chat plan -------------------------------+--> this plan Tasks 1-6
  +-- Project archive/schema/content -- upload integration+
  +-- Upload evidence -> reviewed fix -> media foundation-+
```

## Required Final Sign-Offs

- QA: automated suite and complete manual matrix.
- Bug-debugger/operations: upload evidence and producing-layer conclusion.
- Database: project archive/checksum reconciliation and backups.
- Security: auth/reset retention, upload/media, chat token/input, CSV scope/formula, project authorization/escaping.
- Release manager: Hostinger capacity, ordered rollout, observability, and rollback readiness.

# Upload investigation — 2026-09-18 (LOCAL ONLY)

Scope per user ruling: local reproduction only. No Hostinger access, no Hostinger
changes. Assume a fix verified locally transfers. No upload middleware, disks,
paths, session/CSRF, PHP, proxy, caches, or upload throttle were changed before
or during evidence capture. All secrets redacted: no raw session cookie, CSRF
token, signed token, or credentials appear in this document.

- Repo: `C:\Users\shayne\Documents\TDTPowersteel`, branch `feature/site-stabilization`, HEAD `1181827`
- Date: 2026-09-18; probe window ~06:03–06:05 UTC (14:03–14:05 local, UTC+8)
- Server: `php artisan serve --host=127.0.0.1 --port=8471` (PID 788, stopped after probing)
- Selected branch: **(B) session/CSRF** — exactly one branch selected (see §5).

## 1. Symptom

Filament `FileUpload` (Livewire temporary upload) on `/admin/product-categories/create`
(tile image → `categories`, banner → `categories/banners`) and
`/admin/blog-posts/create` (cover → `blog`) fails client-side with an
HTML-instead-of-JSON error: Livewire JS `POST livewire-<hash>/upload-file`
expects `Content-Type: application/json` with body `{"paths": [...]}` but
receives an HTML document, so the upload promise rejects on parse.

## 2. Reproduction (local, read-only — no config changed)

Prerequisites (unchanged): `APP_ENV=local`, `APP_URL=http://localhost`,
`SESSION_DRIVER=database`, `FILESYSTEM_DISK=public`, Livewire temp disk
`public`/dir default `livewire-tmp`/middleware default `throttle:60,1`.

Probe files (metadata):
`probe-small.png`: 313 bytes, `image/png`, 100×100 generated PNG.
`probe-big.bin`: 3,145,728 bytes, `application/octet-stream` (3 MiB filler).
Request inputs for upload probes: multipart `files[0]=<file>`, guest (no
session cookie jar, no `X-CSRF-TOKEN` header, no signed query params).

### 2.1 GET create pages (guest) — redirect chain returns HTML

`GET http://127.0.0.1:8471/admin/product-categories/create` (`--max-redirs 0`):

- Status: `302 Found`
- `Location: http://127.0.0.1:8471/admin/login`
- `Content-Type: text/html; charset=utf-8`
- `Set-Cookie: tdt-powersteel-corporation-session=[REDACTED]; …; path=/; secure; httponly; samesite=strict`
- Body: stock redirect HTML (`Redirecting to …/admin/login`).

`GET http://127.0.0.1:8471/admin/blog-posts/create`: identical
(`302`, same `Location`, same `Content-Type`, same `Secure` session cookie,
same redirect HTML body).

Followed chain (`curl -L`): `final_url=http://127.0.0.1:8471/admin/login`,
`http=200`, `ctype=text/html; charset=utf-8`, `redirects=1`.

`GET http://127.0.0.1:8471/admin/login` → `200 OK`, `Content-Type:
text/html; charset=utf-8`, sets both `XSRF-TOKEN=[REDACTED]; …; secure;
samesite=strict` and `tdt-powersteel-corporation-session=[REDACTED]; …;
secure; httponly; samesite=strict` — i.e. **both cookies carry the `Secure`
attribute while being served over plain `http://`**.

### 2.2 POST Livewire upload endpoint (guest) — HTML where JSON is expected

Route: `POST livewire-d988e63b/upload-file` → `livewire.upload-file` →
`FileUploadController@handle` (middleware `web` + `throttle:60,1`; controller
first runs `abort_unless(request()->hasValidSignature(), 401)`).

- `GET …/upload-file` → `405 Method Not Allowed` (`Allow: POST`), HTML error page.
- `POST …/upload-file` (no file, no CSRF) → **`419 unknown status`,
  `Content-Type: text/html; charset=utf-8`**, `Page Expired` HTML body
  (6,732 bytes on retry), plus `Secure`-flagged session `Set-Cookie`.
- `POST …/upload-file -F files[0]=@probe-small.png` → **same `419`,
  same `text/html`**, same `Page Expired` HTML (6,732 bytes on retry).
  The very first attempt additionally prefixed the body with a one-off PHP
  startup warning (see §5-E); the retry was a clean 419 with no warning.
- `POST …/upload-file -F files[0]=@probe-big.bin` (3 MiB) → **same `419`,
  `text/html`, 6,732 bytes** (fails at CSRF before any size validation).

Auth/CSRF state for all upload probes: guest, no session, no CSRF token, no
`expires`/`signature` query params. Livewire's browser flow always sends the
session cookie + CSRF header + signed URL; any of those missing yields an
HTML 419/401/302 instead of JSON `{"paths": [...]}`.

### 2.3 Timestamp-correlated storage / logs

- `storage/logs/laravel.log` last write `2026-09-18 14:00:59` local
  (06:00:59 UTC) — **before** the probe window; tail contains only older
  entries (a stale sqlite `no such table: product_categories` test trace and
  a `config:show --compact` usage error). **Zero new lines were written during
  06:03–06:05 UTC probing** (`LOG_LEVEL=error`; 302/405/419 are normal
  responses, not error-level exceptions). No exception correlates with the
  HTML-instead-of-JSON responses.
- `storage/app/public/` initially contained only `.gitignore`; `public/storage`
  did not exist (`Test-Path` false) at first check. A `storage:link` junction
  was created mid-investigation (~06:04 UTC) to test preview pathing and
  **removed afterward** (`cmd /c rmdir`); final state matches initial state.
  Write probe via app boot: `Storage::disk('public')->put()` OK, `exists`
  yes, root writable yes; `livewire-tmp/` absent (normal — created on first
  successful upload).
- PHP (serve process): `upload_max_filesize=2M`, `post_max_size=8M`,
  `upload_tmp_dir=(empty→system temp)`, `sys_temp=C:\Users\shayne\AppData\Local\Temp`
  writable yes, `memory_limit=128M`.
- `bootstrap/cache/config.php` absent (no config cache).

## 3. Root cause — branch (B) session/CSRF

`.env` sets `SESSION_SECURE_COOKIE=true` with `SESSION_SAME_SITE=strict`
while local development serves plain `http://` (`APP_URL=http://localhost`,
serve on `http://127.0.0.1:8471`). Every observed `Set-Cookie` carries the
`Secure` attribute. Browsers **never store or send `Secure` cookies over
`http://`**, so locally the session (and its CSRF token) can never persist:
each request looks like a fresh guest, `POST …/upload-file` fails CSRF with
`419 text/html Page Expired`, and the create pages `302`-redirect to the login
HTML page. Livewire's uploader parses each of those HTML bodies as JSON and
surfaces exactly the reported "HTML instead of JSON" failure — deterministically
reproduced above for both create pages and for small and oversized files.

## 4. Verification

- Repro commands (all read-only, all above): guest `GET` of both create pages
  → `302 text/html → /admin/login`; guest `POST` upload (±small/3 MiB file) →
  `419 text/html`; redirect-chain follow → `200 text/html` login.
- Full suite (unchanged code): `php artisan test --compact` →
  `{"tool":"phpunit","result":"passed","tests":93,"passed":93,"assertions":749,…}`
  — **93/93 green**, must stay green.

## 5. Branch scorecard (exactly one selected: B)

- **(A) filesystem permission/path — REJECTED.** `storage/app/public` root
  writable yes; `put`/`exists`/`delete` probe OK; ACLs show full control for
  SYSTEM/Administrators/owner; no permission denial in any response or log.
  `public/storage` missing at first check affects preview URLs only, not the
  upload `POST` (which 419s before touching disk).
- **(B) session/CSRF — SELECTED.** Deterministic evidence in §2.1–2.2 and §3:
  `Secure` + `SameSite=strict` cookies over plain http, persistent guest state,
  `302`/`419` HTML where JSON is required.
- **(C) signed URL / APP_URL / proxy — REJECTED (locally).** CLI-generated
  signed URL has `signature=` present and resolves to route
  `livewire.upload-file`; runtime signatures are built from the request host,
  and no `401` from `hasValidSignature` was observed locally (CSRF fires
  first for guests). `trustProxies` loopback config is consistent with direct
  `artisan serve` (no proxy). Hostinger-domain behavior is out of scope per
  the local-only ruling.
- **(D) rewrite/proxy routing — REJECTED (locally).** All routes resolve to
  the correct controllers (`302` Filament auth guard, `405 Allow: POST`,
  `419` CSRF). No proxy/rewrite exists in the local path; no misrouted or
  HTML-shell responses beyond the expected auth/CSRF ones.
- **(E) PHP limits/temp dir — REJECTED as the cause; kept as follow-up risk.**
  Real mismatch exists (`upload_max_filesize=2M` < Filament `maxSize 5120`
  = 5 MB), and the first file-bearing probe emitted a one-off `PHP Request
  Startup: File upload error - unable to create a temporary file` warning —
  but the immediate retry (same file) and the 3 MiB probe returned clean
  identical `419` pages with no warning, `sys temp` is writable, and small
  files fail identically to oversized ones. Size/temp behavior therefore does
  not explain the consistent HTML-instead-of-JSON symptom.
- **(F) stale release/config cache — REJECTED.** `bootstrap/cache/config.php`
  absent (probe: `config_cache_present=no`); effective config equals the
  checked-in defaults; no release tooling involved locally.

## 6. Exact authorized delta for Task 3 (local only, no code deploy)

1. In the **local untracked `.env` only** (no tracked file, no commit), set
   `SESSION_SECURE_COOKIE=false` (keep `SESSION_SAME_SITE=lax` or unset;
   keep `SESSION_DRIVER=database`). Do not touch middleware, disks, paths,
   Livewire config, PHP ini, proxies, caches, or throttle.
2. Restart `php artisan serve` on `http://127.0.0.1:8471`, log in at
   `/admin/login`, open `/admin/product-categories/create` and
   `/admin/blog-posts/create`, attach a small PNG (< 2 MB to stay under the
   current PHP cap), and confirm `POST …/upload-file` returns
   `200 application/json` with `{"paths": [...]}` and the preview renders.
3. Re-run `php artisan test --compact` and confirm 93/93 still green.
4. Explicitly **out of scope** for Task 3: Hostinger/production cookie or
   `APP_URL` changes, PHP `upload_max_filesize` raise (tracked as follow-up:
   align server `2M` cap with Filament `5 MB`, or lower `maxSize`), symlink
   recreation on servers, and any throttle/middleware edits.

## 7. Follow-up risks

- Production parity: if any environment serves the admin over plain http with
  `SESSION_SECURE_COOKIE=true`, the identical failure will occur there; the
  durable fix is scheme-aware cookie config, but that is a separate task.
- Latent size cap: files between 2 MB and 5 MB will fail PHP-level upload
  validation even after (B) is fixed; align PHP and Filament limits.
- The transient `unable to create a temporary file` warning appeared once
  with no recurrence; if it reappears under load, investigate AV/file-locking
  on the Windows temp dir (branch E) separately.

## 8. Files touched

- `docs/operations/upload-investigation-2026-09-18.md` (this file) — the only
  change. `git status` otherwise clean apart from the pre-existing untracked
  `docs/` dir. Transient artifacts (serve process, `public/storage` junction,
  `storage/app/public/upload-probe/`, `C:\Users\shayne\AppData\Local\Temp\opencode\probe-*`)
  were removed or live outside the repo; no middleware/disks/paths/session/
  CSRF/PHP/proxy/cache/throttle state was altered.

# Local upload temp fix — 2026-10-10 (LOCAL ONLY, Layer A)

Scope: local dev on Windows only. No production/Hostinger change. No app code
changed for this fix (zero production behavior impact).

## Root cause (proven)

`php artisan serve` in default reload mode scrubs `TMP`/`TEMP`/`USERPROFILE`
from the `php -S` child environment — `ServeCommand::startProcess()` only
passes `$passthroughVariables` (verified in
`vendor/laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php`).
PHP's temp resolution then falls back to `C:\Windows` (non-writable), so every
multipart upload fails with `UPLOAD_ERR_NO_TMP_DIR` (error 6):

- `Warning: PHP Request Startup: File upload error - unable to create a
  temporary file` in the serve log,
- `$_FILES…error = 6`, `tmp_name = ""`, `size = 0`,
- `sys_get_temp_dir() = C:\Windows`, `getenv('TMP') = getenv('TEMP') = false`.

In the Livewire flow this surfaces as HTML (warning bytes and/or auth
redirect) where `application/json` is expected, so the uploader's
`JSON.parse` fails. `SESSION_SECURE_COOKIE=false` (already persisted) is
effective and unrelated to this trigger.

Evidence: plain `php -S` with env intact succeeds; env-stripped serve fails
identically to `artisan serve`.

## Why not `php -d … artisan serve`

`ServeCommand::serverCommand()` spawns the child as `[php_binary(), '-S',
host:port, server.php]` with **no `-d` passthrough**, so `-d` flags on the
parent never reach the serving SAPI. The pin must live in `php.ini`
(primary), or use `--no-reload` (fallback: passes full `$_ENV` through, at
the cost of auto-reload).

## Fix applied (local-only, reversible)

1. Created project-local temp dir (committed keepers, contents ignored):
   - `storage/tmp/php-uploads/` (+ `storage/tmp/.gitignore` with `*`/`!.gitignore`)
2. Local Herd php.ini only — `C:\Users\shayne\.config\herd\bin\php84\php.ini`
   (outside the repo, never deployed):
   ```ini
   sys_temp_dir = "C:/Users/shayne/Documents/TDTPowersteel/storage/tmp/php-uploads"
   upload_tmp_dir = "C:/Users/shayne/Documents/TDTPowersteel/storage/tmp/php-uploads"
   ```
   Backup: `php.ini.bak-20261010` next to it. Revert: delete the appended
   block (marked in-file) and restore the two commented defaults.
3. `php artisan storage:link` re-run: `public/storage` → `storage/app/public`
   verified present.
4. Regression guard: `tests/Feature/UploadTempDirTest.php`
   - `storage/tmp/php-uploads` exists + writable (all OSes),
   - on Windows, loaded `upload_tmp_dir` is set + writable.

## Verification (real multipart POST through `artisan serve`, temporary probe
deleted afterward; secrets redacted — none involved)

BEFORE (`:8472`, default serve, ini unpinned) —
`HTTP 200`, `Content-Type: application/json` with a PHP warning prefix:
`Warning: PHP Request Startup: File upload error - unable to create a
temporary file` + body
`{"upload_tmp_dir":"","sys_get_temp_dir":"C:\\Windows","env_tmp":false,
"env_temp":false,"files":{"name":"composer.json","tmp_name":"","error":6,
"size":0}}`. Serve log shows the same warning.

AFTER (`:8473`, restarted serve, ini pinned) — clean `HTTP 200`
`application/json`, no warning:
`{"loaded_ini":"C:\\…\\herd\\bin\\php84\\php.ini",
"upload_tmp_dir":"C:/…/storage/tmp/php-uploads",
"sys_get_temp_dir":"C:/…/storage/tmp/php-uploads","env_tmp":false,
"env_temp":false,"files":{"name":"composer.json",
"tmp_name":"C:\\…\\storage\\tmp\\php-uploads\\php….tmp","error":0,…}}`
(repeated 3×, stable). Note the child env is still scrubbed
(`env_tmp=false`) — the ini pin is what carries the fix.

## Follow-ups (NOT done here)

- Production Hostinger: untouched by design; apply the host-side equivalent
  there (writable `upload_tmp_dir` / correct temp env) in its own task.
- PHP cap `upload_max_filesize=2M` still below the app's 5 MB cap: files
  between 2 MB and 5 MB fail at the PHP layer. Align separately.
- Herd updates may overwrite `php.ini`: re-apply the block (backup kept) or
  fall back to `php artisan serve --no-reload`.
- Task 2 (`shouldRenderJsonWhen`) untouched.

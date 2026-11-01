# Local media operation — categories / blog (projects handoff ready)

Scope: local-only hardening. No Hostinger changes, no throttle/middleware changes.

## Public storage requirement

- Permanent images live on the `public` disk (`storage/app/public`):
  `categories/`, `categories/banners/`, `blog/`, `projects/` (reserved).
- Local preview URLs require the symlink:
  `php artisan storage:link` → `public/storage` → `storage/app/public`.
  Without it, `MediaUploadRules::url()` still builds `/storage/...` but HTTP 404s.
- Verify: `Test-Path public/storage`, upload a <2 MB JPG via
  `/admin/product-categories/create` or `/admin/blog-posts/create`,
  confirm preview renders and `storage/app/public/<dir>/...` exists.

## PHP local cap (2M < 5MB)

- Local `php -i` shows `upload_max_filesize=2M`, `post_max_size=8M`.
- App cap is 5 MB (`MediaUploadRules::MAX_KILOBYTES=5120`) at both
  Filament UI (`acceptedFileTypes`, `maxSize`) and server guard
  (`mutateFormDataBeforeCreate` + `validateStoredPath`).
- Consequence: 2–5 MB files fail at the PHP layer locally even though the
  app allows them. Use <2 MB files for local upload verification, or raise
  local ini to `upload_max_filesize=8M` / `post_max_size=10M`.
- Invalid type/size shows the shared message:
  `Image must be a JPG, PNG, or WebP file no larger than 5 MB.`
  No record is created on guard failure; Create stays disabled via
  `wire:loading.attr="disabled"` while an upload is in flight.
- Replacements never delete the old file until `DB::afterCommit`.

## Contract for Projects tasks

- `App\Support\MediaUploadRules::url(?string $path): ?string`
  - `null`/blank → `null`
  - `/static/images/...` → passthrough unchanged
  - otherwise → `Storage::disk('public')->url($path)`
- Reuse `MIMES`, `MAX_KILOBYTES`, `MESSAGE`, `DIRECTORY_PROJECT='projects'`,
  `validateUploadedFile()`, `validateStoredPath($path, $dir, $required)`,
  `scheduleOldFileDeletion($old, $new)` (calls `DB::afterCommit` internally).

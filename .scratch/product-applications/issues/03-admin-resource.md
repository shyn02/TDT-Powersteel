# 03: Marketing manages Product Applications in the admin

Spec: `.scratch/product-applications/spec.md`

**What to build:** A marketing editor can create, edit, reorder, deactivate, archive, restore, and permanently delete Product Applications from the admin, uploading a thumbnail and an Application video for each. Changes appear on the public page without a deploy.

**Blocked by:** 01

**Status:** done

- [x] The resource lists Product Applications with thumbnail, label, active toggle, and sort order, and follows the same create/edit/archive/restore/force-delete behaviour as the other public-content resources.
- [x] The edit form groups the circle (label, thumbnail upload, optional description), the video (single Application video upload), and visibility and order (active flag, sort order).
- [x] Thumbnail uploads accept JPG, PNG, and WebP up to the shared image size cap, and Application videos accept MP4 and WebM up to 50 MB, stored on the public disk under a dedicated directory.
- [x] Uploaded thumbnails and videos resolve to public URLs through the shared media helper, with blank paths resolving to null.
- [x] Replacing a thumbnail or video deletes the previous file after the database commit, leaving nothing orphaned.
- [x] A Product Application created here appears on the public page; archiving it removes it.
- [x] The admin navigation entry sits with the other public-content resources, after Products.
- [x] Validation rejects a thumbnail or video of the wrong type or over the size cap, without losing the rest of the form.
- [x] A Product Application with no video can still be saved and shows as inert on the public page.

## Comments

Completion notes, including what the two-axis review caught.

- **`is_active` is a switch in the list, not a read-only icon.** The ticket asked for a toggle; the first pass used an icon column that forced an edit round-trip to deactivate.
- **Archived rows are actually pruned.** `prune:archived` had no `ProductApplication` entry, so soft-deleted rows would have accumulated forever while the table advertised a "30-day archive". They are now in the pruned model list, with a test that moves a row 31 days into the past.
- **One guard, two screens.** The create/edit upload validation lived twice; it now lives in `ProductApplicationMedia::errors()`, differing only in whether absent keys are validated (edit must not re-check untouched media).
- **Thumbnails moved onto the managed disk.** Seeded rows originally pointed at the committed `/static/` artwork, which the admin uploader cannot preview or replace, because it resolves against the public disk. The seeder now copies each thumbnail into `product-applications/` and stores that managed path; a follow-up migration re-runs it for databases that already imported the rows. The static originals stay in the repo as the source.
- **Replaced static assets are never deleted.** `MediaUploadRules::shouldDeleteReplacedFile()` gates the after-commit cleanup; a seeded thumbnail is a committed file, not managed media, so replacing it must not try to delete it.
- **A broken video closes the popup** instead of leaving an empty player, mirroring the lightbox's on-error behaviour.
- **Accepted, not changed:**
  - `SocialHighlightForm` keeps its own broader video config (it allows MOV). The new `MediaUploadRules` video constants are the Product Application contract, not a repo-wide one.
  - A 50 MB upload depends on PHP's `upload_max_filesize`/`post_max_size`; this machine has 64M, a host at PHP defaults (2M) would reject the file before Filament sees it. That is a deployment setting, already flagged in `MediaUploadRules`.
  - Re-running the seeder repairs seeded rows by upsert (same as `HomepageProjectSeeder`). It runs once, from migrations; it is not a content-edit path.

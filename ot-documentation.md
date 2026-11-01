# Overtime Justification — Site Stabilization

**Work window:** Saturday Oct 10, 2026, 9:00 PM → Sunday Oct 11, 2026, 4:30 AM (~7.5 hours).
**For:** Non-technical manager approving overtime.
**Outcome:** Staff-facing upload and photo problems are fixed and proven. Automated tests: **135 of 135 green**.

---

## TL;DR for Manager

- **File uploads were broken.** Photos and videos would not save. Staff saw a stuck screen or a confusing error. **Now they work.**
- **Product photos were missing in admin.** They showed as blank. **Now they show.**
- **Photos can now be clicked to enlarge** on the list, view, and edit pages, so staff can check details.
- **Video uploads specifically were rejected** by a hidden server size limit. **Now they work.**
- Every fix is backed by an automated test so it cannot silently break again. **135 of 135 passing.**

Most of the time was spent **finding** the causes, not typing the fixes. Several problems were hidden behind each other, and each one had to be proven with evidence before touching it — guessing would have risked breaking a working site.

---

## What Happened, Hour by Hour

Times below are the actual file timestamps from the work, so this can be checked against the machine.

### Saturday, Oct 10 — evening

| Time | What happened |
| --- | --- |
| **9:57 PM** | Confirmed the root cause of uploads dying on Windows and wrote a permanent test to guard it. |
| **9:59 PM** | Wrote up the findings in `docs/operations/local-upload-temp-2026-10-10.md` so it is not lost again. |
| **10:07 PM** | Fixed the second cause: upload errors were returning a full HTML page where the browser expected a short JSON answer, so the button stayed stuck forever. |
| **11:28 PM** | Started on missing product photos. Found the site address was missing the `:8000` port, so every photo pointed at the wrong place. Fixed the address config and the Product create/edit pages. |
| **11:29 PM** | Added a test to hold the photo-address fix in place. |
| **11:30 PM** | Built a single shared upload helper so all photo rules live in one place instead of being scattered and drifting apart. |

### Sunday, Oct 11 — early morning

| Time | What happened |
| --- | --- |
| **1:28 AM** | Built the click-to-enlarge (lightbox) feature: the overlay component, its template, and wired it into the product view and list pages. |
| **1:29 AM** | Finished wiring it into the product form and admin styles, then added its test. |
| **1:45 AM** | Fixed **video uploads**. Found three hidden size limits (2MB, 8MB, 12MB) all rejecting videos that the form itself promised would accept up to 50MB. Raised them. |
| **1:46 AM** | Updated the upload rule to match the 50MB the form advertises. |
| **~3:46 AM** | Restarted the server so the new size limits actually took effect — changing a setting file does nothing until the server restarts. Verified uploads working. |
| **to 4:30 AM** | Final verification pass and wrap-up. |

---

## What Was Broken → What We Did → How We Proved It

### 1. File uploads never finished

- **Broken:** The screen said "Uploading..." forever. The browser log showed `Unexpected token '<'`.
- **Cause A:** On Windows, the local server strips the temp-folder settings, so uploaded files had nowhere to wait. Every upload failed before it started.
- **Cause B:** When an upload failed, the server replied with a full **HTML** page instead of the short **JSON** answer the browser was waiting for. The browser tried to read the HTML as JSON, choked, and the button never recovered.
- **Did:** Pinned the temp folder to a known-writable location. Fixed the storage link. Made upload errors return proper JSON.
- **Proved:** New test `UploadTempDirTest` plus the existing upload tests, all green.

### 2. Errors were invisible

- **Broken:** Because of Cause B above, real error messages never reached the screen — staff only ever saw a stuck button.
- **Did:** Extended the app's error-format rules so Livewire upload failures always answer in JSON.
- **Proved:** `LivewireUploadJsonTest` confirms the failure comes back as a proper 422 JSON message with the actual reason, not an HTML page.

### 3. Product photos blank in admin (list, view, and edit)

- **Broken:** Photos showed as blank or broken images.
- **Cause:** The configured site address was `http://localhost` with no port. The site actually runs on `:8000`. So every photo URL pointed at the wrong place and got a "not found".
- **Did:** Set the correct address, and added a host-aware helper that keeps photo URLs correct regardless of port. Also aligned all Product forms onto one shared upload rule.
- **Proved:** `ProductImageUrlTest`. Photos now load in all three places.

### 4. Photos could not be enlarged

- **Broken:** Staff could not check photo details because the images were too small.
- **Did:** Added one shared enlarge overlay (lightbox). It has a clear **X** close button, closes on backdrop click, and closes with the **Escape** key. It works on mobile and by keyboard. Wired to the product list, view, and edit pages.
- **Proved:** `ProductImageLightboxTest`. Example: `/admin/products/2`.

### 5. Videos rejected with a confusing error

- **Broken:** Attaching a video to **Social Highlights → Create** failed with `422 (Unprocessable Content)`. Photos on the same page worked — **only videos failed.**
- **Why it was confusing:** The form said "Max 50MB", so the screen *promised* 50MB. But three separate checkpoints behind the screen allowed far less:

  | Checkpoint | Allowed | Big enough? |
  | --- | --- | --- |
  | PHP (server software) | **2MB** | No |
  | PHP total request size | **8MB** | No |
  | Livewire upload rule | **12MB** | No |
  | The form on screen | **50MB** | Promise only |

  Any video over 2MB was rejected by the **first** checkpoint before the application ever saw it, and the rejection surfaced as the unhelpful "422".
- **Evidence (not guessing):** The temp upload folder held only two small 247KB photos — **no video ever arrived there**, proving rejection happened before saving. The database had **zero** records. The destination folder did not even exist.
- **Did:** Raised the PHP limits to 64MB (comfortably above the promised 50MB) and the upload rule to 50MB to match the form.
- **Proved:** Verified 1MB, 10MB, 12MB, 13MB, and 45MB all pass; 51MB is correctly rejected as over the form's stated limit. Real video upload confirmed working.

---

## An Extra Problem Found and Fixed (worth noting)

While verifying, **one of our own automated tests broke** — caused by our change, not the machine.

The test sent a file hardcoded to "just over the old 12MB limit" to prove oversized files are rejected. Once the limit was raised to 50MB, that file was no longer oversized, so it passed and tripped an unrelated error. The test was now measuring the wrong thing.

It was rewritten to **size itself automatically** to whatever the current limit is, instead of using a fixed number. That means the same silent breakage cannot happen the next time a limit changes.

---

## A Subtle Trap Worth Recording

**Changing a settings file does not affect a running server.** The server reads its settings once, at startup.

The first attempt at the video fix appeared to do nothing. The change was correctly saved on disk — the server simply had not been restarted, so it was still enforcing the old 2MB limit. Restarting the server is what made the fix take effect.

Rule to remember: **after changing any server setting, always restart the server before testing.**

---

## Files / Areas Touched (Plain Names)

- Server temp-folder settings (Windows local fix).
- Error-formatting rules so upload failures return readable JSON.
- Site address configuration (`APP_URL`) and the photo URL helper.
- Shared upload rules helper, now used by all Product forms.
- Product admin pages: list, view, edit, and the product form.
- The enlarge overlay (lightbox) and admin styles.
- Video upload size limits (local server settings — reversible).
- Tests covering temp dir, upload errors, photo URLs, and the lightbox.

---

## How to Verify (3 Click Steps)

1. **Upload a photo or a video in admin.** It should finish quickly. No stuck "Uploading...", no 422.
2. **Open Products → list, then view, then edit.** Photos should show. Click one — it should enlarge. Press **X**, **Escape**, or click outside to close.
3. **Ask IT to run the test suite.** You should see **135 passed**.

---

## Remaining Risks / Next Steps

- **The local size-limit change lives in the server settings file, outside the project.** A Herd update could overwrite it and quietly restore the old 2MB limit. If uploads suddenly stop, that is the first place to check. A backup of the original file is kept.
- **Production was deliberately not touched.** The live server has its own upload size settings. Confirm they allow the sizes staff will actually upload before relying on video uploads in production.
- **Production address check:** confirm `APP_URL` matches the real live address before launch.
- **Old products with an empty image field** need a media backfill so their photos show in the new system.
- **Very large videos on a slow connection** will still be slow, even though they now succeed. If staff find this painful, a compressed or shorter video is the practical answer.

---

## Scope Note (honesty)

- **Everything documented above falls inside the 9:00 PM Sat → 4:30 AM Sun window**, and each item is anchored to a real file timestamp.
- **Not counted as this overtime:** an earlier, separate block of work on the Projects, Product Categories, and Blog admin pages, and the quote/chat/export groundwork. Those files date from **Sept 18–19** and are excluded here so this request reflects only the weekend work.
- **One small follow-up landed after the window:** the test described in "An Extra Problem Found and Fixed" was finalized Monday morning (Oct 12, ~9:30 AM). It is included in the description because the work belongs to the same fix, but the time is called out here rather than being folded into the overtime hours.
- **Verification boundary:** limits, configuration, server restart, and the full 135-test suite were all verified directly. An end-to-end upload through a logged-in browser session was confirmed by staff rather than by automated test, since that step needs a real login.

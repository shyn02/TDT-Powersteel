# Product Applications

Status: ready-for-agent

## Problem Statement

TDT Powersteel has approved an illustrated map of its facility with twelve product types drawn as circles around it. Marketing wants visitors to click a circle and watch a short video showing that steel product in use. Today there is no page for this, no way to play the videos, and no way for marketing to change a video, a label, or a circle's position without a developer. The only asset that exists is the artwork itself.

## Solution

A new public page at `/product-applications/` presents the Product Applications map. Each Product Application is an HTML circle placed over the artwork, with its Map label beside it. Clicking a circle opens a modal that plays that product's Application video with its title, and an optional description when one has been entered. A list of the same twelve Product Applications sits beneath the map, acting as the small-screen and assistive-technology path.

Every circle — its label, thumbnail, video, description, position, and visibility — is a row in the admin, editable by marketing in Filament without a deploy. The artwork itself stays a static file in the repo.

## User Stories

1. As a prospective customer, I want to see a single page that shows what TDT Powersteel supplies across its facility, so that I can understand the breadth of the product range at a glance.
2. As a prospective customer, I want to click a circle on the map and immediately watch a short video about that product, so that I can judge whether it fits my project without reading a spec sheet.
3. As a prospective customer, I want the video to start playing without extra clicks, so that exploring the map feels effortless.
4. As a prospective customer, I want the modal to name the product I clicked, so that I know which item I am watching.
5. As a prospective customer, I want a short description of the product in the modal when one is available, so that I get context the video may not state.
6. As a prospective customer, I want to close the modal by clicking outside it, pressing Escape, or using a visible close button, so that I am never trapped in a video.
7. As a prospective customer, I want the video to stop when I close the modal, so that audio does not keep playing behind the page.
8. As a prospective customer on a phone, I want the circles to remain large enough to tap, so that I can use the map without pinching or missing.
9. As a prospective customer on a phone, I want the map labels not to overlap or crowd the artwork, so that the page still reads clearly on a small screen.
10. As a prospective customer on a phone, I want a simple list of the same products below the map, so that I can browse without hitting tiny targets.
11. As a prospective customer, I want the whole map to scale with my screen rather than being cropped, so that no product is cut off.
12. As a prospective customer, I want circles whose video is not ready yet to do nothing when clicked, so that I never hit an empty player or an error.
13. As a prospective customer, I want to reach the page from the main navigation, so that I can find it without a direct link.
14. As a prospective customer, I want the page to end with a way to request a quote, so that interest converts into an enquiry.
15. As a keyboard-only visitor, I want to reach each circle with Tab, see where focus is, and open a video with Enter or Space, so that the map is not mouse-only.
16. As a keyboard-only visitor, I want focus to move into the open modal and return to the circle I came from when it closes, so that I do not lose my place.
17. As a screen-reader user, I want each circle to announce the product it represents, so that the visual map still conveys its meaning.
18. As a screen-reader user, I want the list beneath the map to be the equivalent of the artwork, so that I do not depend on coordinates to understand the page.
19. As a visitor on a slow connection, I want the page not to download twelve videos up front, so that the map loads quickly.
20. As a search engine crawler, I want a normal indexable page with a heading, description, and internal links, so that the page can rank for product-application searches.
21. As a marketing editor, I want to add, edit, reorder, deactivate, and delete Product Applications myself, so that the page stays current without developer time.
22. As a marketing editor, I want to upload a thumbnail and an MP4 per Product Application, so that the circle image and the video are both mine to change.
23. As a marketing editor, I want to set the position of each circle as a percentage of the artwork, so that I can align it precisely with the product's spot on the map.
24. As a marketing editor, I want a live preview of the artwork in the edit form showing where my circle will land, so that I can position it without guessing.
25. As a marketing editor, I want to leave the description blank, so that the modal simply shows the title and video.
26. As a marketing editor, I want to hide a Product Application without deleting it, so that I can take a product down temporarily.
27. As a marketing editor, I want a Product Application with no video to render but not be clickable, so that I can publish the artwork before the videos are ready.
28. As a marketing editor, I want replaced files cleaned up automatically, so that storage does not fill with abandoned uploads.
29. As a marketing editor, I want to see each Product Application's thumbnail and status in the admin list, so that I can scan the set quickly.
30. As a developer, I want the twelve initial rows seeded from the approved artwork, so that the page is complete on first deploy without data entry.
31. As a developer, I want circle positions stored as percentages rather than pixels, so that the layout is resolution-independent.
32. As a developer, I want the artwork to remain a static asset, so that a replaced map is a deliberate, reviewable change rather than a silent content edit.
33. As a developer, I want video uploads to reuse the existing public-disk conventions, so that there is one media contract rather than two.

## Implementation Decisions

**Domain model.** A new `ProductApplication` model backs each circle. Table `product_applications` with: `label`, `image` (thumbnail path), `video_file` (path), `description` (nullable), `x` and `y` (circle centre as a percentage of the artwork, decimal), `is_active`, `sort_order`, timestamps, and soft deletes — matching how other public-content tables in this project behave (Products, Projects, Product Categories).

**Terminology.** The domain vocabulary is fixed in `GLOSSARY.md`: *Product Application*, *Product Applications map*, *Application video*, *Map label*. Code, tests, and admin labels use those words, not "hotspot".

**Page.** New route `/product-applications/` served by `PageController`, rendering a dedicated Blade view. The page follows the site's existing page shape: page banner with breadcrumb, H1 "PRODUCT APPLICATIONS", one-line intro, the map, the twelve-item list, and the site's standard quote call to action. Page title and meta description are set per page, as on every other view.

**Navigation.** A "PRODUCT APPLICATIONS" item is added directly after PRODUCTS in the header nav and after Products in the footer's Quick Links. It is a top-level sibling of Products, not a child. `request()->is('product-applications*')` drives the active state.

**Sitemap.** `/product-applications/` is added to the static page list in the sitemap controller with a priority between the products page and the informational pages.

**Artwork.** The map is a static asset, committed, 1920×1080, 16:9. It is rendered at full container width with a fixed `aspect-ratio`, so percentage positioning is exact at any size. No admin field manages it.

**Circle rendering.** Each active Product Application renders as an absolutely positioned `<button>` at `left: x%`, `top: y%`, translated by −50% on both axes so `x`/`y` are the circle's centre. Diameter is fluid, `clamp(44px, 6.5vw, 96px)`, guaranteeing a 44px minimum tap target. The thumbnail fills the circle, clipped round, inside an orange ring that matches the artwork's accent colour.

**Map labels.** The label is HTML text, not part of the artwork. Its side is derived, not stored: a circle with `x < 50` puts its label to its left, otherwise to the right. Labels are hidden below the site's medium breakpoint, where the list beneath the map carries the same information.

**Leader lines.** Each Product Application may carry an optional Anchor point (`anchor_x`/`anchor_y`, percentages of the artwork). When both are set, a line is drawn from the circle to that point on an SVG layer rendered between the artwork and the circles, so each line appears to start at the circle's edge without edge maths. Lines are `2px` at any scale, hidden from assistive technology, and never intercept clicks. A circle without an anchor draws no line.

**Inert without video.** A Product Application with no `video_file` renders its circle but is not interactive: no pointer cursor, no focus stop, no click handler. `is_active` controls whether the row renders at all. This mirrors the existing product-image lightbox, which never opens on an empty source.

**Modal.** One shared modal per page, opened by clicking or activating a circle or a list item. It contains the product label as its title, the video, and the description only when one is set. The video uses the browser's native controls, plays automatically muted on open, and is paused and reset when the modal closes. The modal follows the same behaviour contract already proven in the admin lightbox: dialog semantics, backdrop click to close, Escape to close, background scroll lock and restore, focus moved to the close control on open and returned to the trigger on close.

**Video handling.** Uploads are restricted to MP4 and WebM, capped at 50 MB, stored on the public disk in a dedicated `product-applications` directory — the same contract the Social Highlights video field already uses. Videos are marked `preload="none"` and only ever played from the modal, so the page does not pull twelve videos on load. Replaced files are deleted after commit through the existing media helper rather than left orphaned.

**Thumbnail handling.** Thumbnails are images, validated through the shared image upload rules (JPG/PNG/WebP, 5 MB cap) and stored on the public disk. Public URLs resolve through the shared media helper so storage URLs never drift from upload logic.

**Admin.** A Filament resource lists Product Applications with thumbnail, label, active toggle, and sort order, and supports create, edit, reorder, soft delete, restore, and force delete consistent with the other content resources. The edit form groups: the circle (label, thumbnail upload, optional description), the video (single MP4/WebM upload with the cap and helper text), and placement (a drag-to-position picker on the artwork plus x/y percentage inputs for precision), with `is_active` and `sort_order`. The admin navigation entry sits alongside the other public-content resources, after Products.

**Mass move.** The list offers a bulk editor showing every circle and leader-line end on one artwork; drags stay client-side and are written in a single transaction on save. It moves existing positions only — adding and removing leader lines stays in the per-record form.

**Dragging.** In the admin, the circle and the end of its leader line are dragged directly on the artwork. Dragging updates local state only and commits once on pointer release, so one drag costs one round-trip; the x/y inputs remain the precise and accessible path to the same values. A circle with no anchor gets an "Add leader line" action that places the anchor beside the circle, and an anchored circle gets "Remove leader line".

**Seed data.** Twelve Product Applications ship in seed data, positioned from the approved artwork: their
`x`/`y` are measured from the supplied composite with the circles in place and pushed outward into the
margin so neighbouring circles and labels do not crowd, and their thumbnails are
cropped from that composite. Every seeded row starts with no video, so the page renders fully and every circle is inert until marketing uploads videos. Seeded thumbnails are placeholders marketing can replace.

**Content safety.** Labels and descriptions are escaped on output, as with every other public field in this project. The video path is resolved through the media helper rather than concatenated in the view.

**Testing seam.** One seam: the page itself, exercised over HTTP with rows in the database. This is the highest available seam and needs no new abstraction. Model-level behaviour (URL resolution, playability) is covered through the model's own public methods.

## Testing Decisions

Good tests here assert externally observable behaviour: what the page returns for a given set of Product Applications, and what the model's public contract resolves to. They do not assert internal structure, private methods, or which Blade partial produced which markup.

Covered at the page seam:
- Only active, non-trashed Product Applications appear, in sort order, and inactive or archived ones do not.
- The page responds successfully and contains the expected heading.
- Position values appear in the rendered markup as percentages, so a positioned circle can be verified without a browser.
- A Product Application with no video is rendered without an interactive control; one with a video is rendered as a button.
- The description appears in the modal payload when set and is absent when not.
- Labels and descriptions are escaped, not rendered as markup.
- Circles are rendered in map order and the list beneath the map mirrors the same set.
- The page omits the map section entirely when no Product Applications are active.
- The route appears in the sitemap output and in the header navigation.

Covered at the model seam:
- A stored video path and a stored thumbnail path resolve to public URLs through the shared media helper, and blank paths resolve to null.
- Playability is false for a blank or missing video and true for a stored one.

Prior art: `ProjectHomepageTest` for page-level content, ordering, exclusion, escaping, and URL resolution; `ProductImageLightboxTest` for asserting an accessibility/markup contract without a browser. No browser or JavaScript test harness exists in this project, so modal interactivity is verified through its markup contract plus manual checking.

## Out of Scope

- Linking a Product Application to a Product Category, or offering a "view products" link from the modal.
- Managing the artwork through the admin.
- YouTube, Vimeo, or other third-party video embeds; this requires a deliberate change to the Content Security Policy, which currently permits only self-hosted media.
- Analytics on which videos are played.
- Multiple maps, or more than one video per Product Application.
- Translations of labels, descriptions, or page copy.

## Further Notes

- The Content Security Policy in the security middleware already allows self-hosted video (`media-src 'self' blob:`) and would block third-party embeds, so no security header change is required for this work.
- The local database is a near-empty stub (one product category, two products), so the twelve seeded rows are also the working demo data for this feature.
- The approved composite artwork, which carries the circle positions and thumbnail crops, is retained in the repo alongside the clean artwork so the placements stay traceable.
- Both artworks are 1920×1080. If either is ever replaced at a different aspect ratio, every stored percentage silently shifts; treat that as a coordinated change, not a content edit.

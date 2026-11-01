# 01: The Product Applications map renders, positioned and reachable

Spec: `.scratch/product-applications/spec.md`

**What to build:** A visitor can reach `/product-applications/` from the main navigation and see the Product Applications map: the clean artwork at full width, twelve circles placed over it, each with its Map label, and a list of the same twelve beneath. Every circle comes from the database, not from the view. Nothing is clickable yet, because no Application videos exist.

**Blocked by:** None (can start immediately)

**Status:** done

- [x] A `product_applications` table and model exist with label, thumbnail path, video path, nullable description, x and y percentages, active flag, sort order, timestamps, and soft deletes.
- [x] Seed data creates the twelve seeded Product Applications with positions measured from the approved composite artwork, and thumbnails cropped from that same artwork. Seeded rows have no video.
- [x] The page responds successfully, carries its own title, meta description, breadcrumb, H1 and one-line intro, ends with the site's quote call to action, and omits the map section entirely when no Product Application is active.
- [x] Circles are absolutely positioned at their stored percentages, centred on that point, and scale with the artwork rather than being pixel-fixed; the minimum diameter keeps a 44px tap target.
- [x] Each Map label renders as HTML text on the outer side: left of the circle when x is below 50, right otherwise. Labels are hidden on small screens.
- [x] Active, non-trashed rows render in sort order; inactive and archived rows do not render.
- [x] The list beneath the map contains the same set, in the same order, and is readable without the artwork.
- [x] Labels and descriptions are escaped on output, never rendered as markup.
- [ ] Circles accept keyboard focus and are announced by their product label to assistive technology. **Deferred to 02** — see the comment below.
- [x] The route appears after PRODUCTS in the header nav and in the footer's Quick Links, with an active state while on the page.
- [x] The route appears in the sitemap output.

## Comments

Completion notes.

- **The focus/announce criterion is deferred.** The spec says a Product Application with no `video_file` renders a circle with *no focus stop*, and every seeded row ships without a video. Twelve focus stops that do nothing would be worse for keyboard users than none. Ticket 02 turns a playable circle into a real `<button>` with an accessible name, which is where focus and announcement belong. The list beneath the map is the accessible path in the meantime.
- **Description escaping is not exercised here** because no description is rendered on this page yet; it lands with the modal in 02.
- **Positions were measured, not guessed.** The twelve circle centres came from radial ring detection on the approved composite (ring radius 63–65 px on a 1920×1080 canvas). Thumbnails are 116 px crops from the same composite, sitting inside the ring so the ring itself is drawn in CSS.
- **A seed migration runs the seeder** (`2026_11_01_000002_import_product_applications`) rather than wiring it into `DatabaseSeeder`, matching how the homepage projects ship. The seeder upserts by an immutable `migration_key`, so a retried deploy repairs rows instead of duplicating them.
- **Below 768 px the map keeps a 620 px floor** inside a horizontal scroller with a visible hint. Shrinking the map to the viewport would force 44 px circles to overlap, since the artwork is dense; the list below is the primary small-screen path.

# 05: Leader lines from each circle to its anchor on the map

Spec: `.scratch/product-applications/spec.md`

**What to build:** Every circle on the Product Applications map is joined to the spot it illustrates by an orange leader line, the way the approved artwork shows it. The point each line ends on is editable in the admin, and a circle with no anchor draws no line.

**Blocked by:** 01

**Status:** done

- [x] A Product Application can carry an optional anchor point, stored as percentages of the artwork.
- [x] An anchored circle renders a line from the circle to its anchor, drawn behind the circles so the line appears to leave the circle's edge.
- [x] Lines keep a constant 2px stroke at any map size, are hidden from assistive technology, and never intercept clicks.
- [x] A circle without an anchor renders no line, and the line layer is omitted entirely when nothing is anchored.
- [x] Marketing can set or clear the anchor in the admin, and the live preview shows the line as they type.
- [x] The twelve seeded circles ship with anchors traced from the approved composite.

## Comments

- **Anchors were traced, not guessed.** Each leader line was followed outward from its circle on the composite until the orange stroke ended, which lands on a small dot already drawn in the clean artwork — 41.56%/43.43% for Purlins, and so on. All twelve anchor points sit inside the 0–100 bounds.
- **The SVG uses a 0–100 viewBox with `preserveAspectRatio="none"`**, so the line endpoints are literally the stored percentages and need no conversion. `vector-effect: non-scaling-stroke` keeps the stroke 2px however wide the map is drawn.
- **No edge maths.** Drawing the line from centre to anchor and stacking the layer under the circles means the circle's own fill covers the inner half of the stroke, so it reads as starting at the rim.
- **The seeder is now schema-aware.** The original import migration replays the seeder on a database where the anchor columns do not exist yet, so the seeder writes anchor columns only when `Schema::hasColumn()` confirms them. Without that, a fresh `migrate` failed on the historical migration.
- **Two migrations:** `000004` adds the nullable columns, `000005` re-runs the seeder so databases that already imported the twelve rows pick up their anchors. Verified against the local MySQL database.

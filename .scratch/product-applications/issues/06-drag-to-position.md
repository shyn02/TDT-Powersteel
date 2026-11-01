# 06: Roomier circles and a drag-to-position editor

Spec: `.scratch/product-applications/spec.md`

**What to build:** The circles sit further out in the white margin, so neighbouring circles and their labels stop crowding each other. In the admin, a marketing editor drags the circle and the end of its leader line around the artwork instead of typing coordinates; the numeric inputs stay for precision.

**Blocked by:** 04, 05

**Status:** done

- [x] The twelve seeded circles are spread outward into the margin, with neighbouring circles no longer crowding.
- [x] The admin edit screen shows the artwork with a draggable circle handle and a draggable anchor handle.
- [x] Dragging either handle moves it live on the artwork, and commits the new percentages to the form once, on release.
- [x] The x/y and anchor inputs still show the same values and remain editable for precise adjustments.
- [x] A circle with no anchor offers "Add leader line"; one with an anchor offers "Remove leader line".
- [x] Dragging the circle does not move the anchor, and vice versa, so the line stretches between them.
- [x] Coordinates stay inside 0–100 whatever the drag does.

## Comments

- **Default positions were recomputed, not hand-nudged.** Each circle was pushed away from the centre of the artwork (×1.17 horizontally, ×1.14 vertically) and clamped to the margin. At a 1200px-wide render the closest pair of circles went from touching to a 40px gap with 96px circles. Verified on a rendered copy of the artwork before committing.
- **One round-trip per drag, not per pointer move.** Alpine moves the handles and the line locally while dragging; on `pointerup` the values are written to the form with `$wire.set()`. Without that, every pointer move would re-render the whole schema, including the FileUpload previews.
- **Pointer capture, not document listeners**, so a drag that leaves the map still finishes cleanly on release and cannot leave a handle stuck to the cursor.
- **The drag surface is not the accessible path.** It is a pointer affordance; the numeric inputs carry the values for keyboard and screen-reader users, and both stay in sync.
- **The thumbnail now renders inside the circle handle**, so the editor sees the real circle rather than a blank ring.

### Follow-up: desktop horizontal scrollbar

The map's `overflow-x: auto` wrapper was showing a scrollbar on desktop because a Map label near the edge reached past the artwork, which made the wrapper legitimately scrollable. Three changes:

- **Desktop has no scrollport.** The wrapper is `overflow: visible` above 767px, so a label can use the container's side gutter (`html` already clips at the viewport, so no page-level scrollbar either). It only becomes a scroller on phones, where the map keeps its 620px floor width.
- **Labels are dropped below 900px instead of 768px.** Between those widths the side gutter is too narrow for a label, so the list below the map is the path, as designed.
- **The four outermost circles moved in slightly** (Steel Pipes and Tubulars to x=16%, Channel Bar to 82%, Sheet Pile to 80%), which keeps their labels inside the artwork at every width where labels are visible.

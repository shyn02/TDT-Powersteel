# 04: Marketing positions a circle with a live preview

Spec: `.scratch/product-applications/spec.md`

**What to build:** A marketing editor sets a Product Application's position by typing its x and y percentages, and sees a preview of the artwork marking exactly where the circle will land, on both the create and edit screens. Saving moves that circle on the public page.

**Blocked by:** 03

**Status:** done

- [x] The edit form has x and y percentage inputs and a labelled preview of the Product Applications map with a marker drawn at those values.
- [x] The preview updates as the values change, without a page reload.
- [x] Values below 0 or above 100 are rejected with a clear validation message.
- [x] The preview appears on both create and edit screens, and is blank or centred rather than broken when no position has been set.
- [x] Saving a changed position moves that circle on the public page.
- [x] A newly created Product Application can be positioned before it is ever published.

## Comments

Completion notes, including what the two-axis review caught.

- **Position rules now live in one place.** `MapPosition` owns the three rules the admin preview and the public circle must agree on: unset means the centre, values outside 0–100 are not a position, and percentages print with at most the two decimals the database stores. The model exposes `position()`, so the circle component and the preview cannot format the same number differently.
- **Out-of-range input no longer looks accepted.** The first pass clamped `150` to `100` and drew a normal marker, which read as valid. The marker still renders at the edge so the editor can see where their input went, but it turns red and the caption says "150% / -20% is outside the map. Enter values between 0 and 100."
- **The caption dropped the product name.** Only x and y are live, so a caption containing the label went stale as soon as someone typed a new label. Rather than make the label live too (another round-trip per keystroke), the caption now states the position only.
- **CSS uses the admin palette variables** instead of re-declaring `#e67026` and `#717074`.
- **The Livewire test needed an authenticated admin.** `Livewire::test(EditProductApplication::class)` mounts the page directly, bypassing the panel middleware, so without `actingAs()` the form never initialises (`getDefaultTestingSchemaName() on null`).
- **Watch out for Pint and fully-qualified class names.** `fully_qualified_strict_types` rewrote `\Illuminate\Validation\ValidationException` in the test's `catch` without adding the import, so the catch silently stopped matching and two guard tests began erroring. If Pint touches a `catch` or type hint in this repo, check the import exists.

## Verification beyond the test suite

A live run against the real MySQL data (12 seeded rows, migrated) returns a 200 page with 12 circles, thumbnails resolving to `/storage/product-applications/*.png`, no playable circles (no videos uploaded yet), and no modal in the markup — exactly the "inert until a video exists" state.

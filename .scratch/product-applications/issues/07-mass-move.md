# 07: Mass move every circle at once

Spec: `.scratch/product-applications/spec.md`

**What to build:** From the Product Applications list, a "Mass move" action opens one editor showing the whole artwork with every circle and every leader-line end on it. Marketing drags any of them and saves once. Nothing is written until Save, so an exploratory rearrangement can be abandoned by leaving the page.

**Blocked by:** 06

**Status:** done

- [x] The list page has a "Mass move" action linking to the editor.
- [x] The editor shows all twelve circles, their leader lines, and their line ends on the artwork, each labelled.
- [x] Dragging a circle moves only that circle; dragging a line end moves only that anchor and stretches the line.
- [x] Circle handles show the real thumbnail.
- [x] "Save positions" writes every changed position in one transaction and confirms with a notification.
- [x] Values are clamped to 0–100 and rounded to two decimals server-side, whatever the drag sends.
- [x] Unknown record ids in the payload are ignored.
- [x] A saved mass move is visible immediately on the public page.
- [x] Only admins can open the editor; staff are refused at the URL and the action is not shown to them.

## Comments

- **One save, not one per drag.** Unlike the per-record editor, this page keeps every drag client-side and persists the whole set in a single transaction, so rearranging twelve circles costs one request.
- **The page is a resource page**, so it inherits the resource's admin-only authorization rather than re-implementing a gate.
- **The artwork is the list.** There is no table of coordinates here; the numbers stay in the per-record form for precise work.
- **Adding or removing leader lines stays in the per-record form.** This editor only moves what already exists, which keeps its payload and its failure modes small.

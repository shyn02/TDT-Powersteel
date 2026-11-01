# 02: Clicking a circle plays its Application video

Spec: `.scratch/product-applications/spec.md`

**What to build:** Clicking or keyboard-activating a circle that has an Application video opens a modal titled with its Map label, playing that video with native controls, showing the description only when one is set. Closing the modal stops and resets the video. A circle with no video stays inert — no pointer, no focus stop, no modal.

**Blocked by:** 01

**Status:** done

- [x] One shared modal serves every Product Application on the page, including the list items beneath the map.
- [x] The modal shows the label as its heading, the video, and the description only when present.
- [x] The video plays automatically muted with native controls, and is paused and reset when the modal closes.
- [x] The modal closes via its own close control, a backdrop click, and Escape; the background cannot scroll while it is open and restores on close.
- [x] Focus moves into the modal on open and returns to the circle or list item that opened it on close.
- [x] A Product Application with no video renders a non-interactive circle: no pointer cursor, no focus stop, no modal.
- [x] Videos are marked not to preload, so opening the page does not fetch twelve files.
- [x] Labels and descriptions in the modal are escaped on output.
- [x] The page works without any change to the Content Security Policy.

## Comments

Completion notes, including what the two-axis review caught.

- **Play order was wrong and is fixed.** `play()` was being called while the modal was still `display:none`. Chrome may tolerate that; Safari/iOS rejects it and the rejection was swallowed, so the dialog would have opened on a paused, blank player. The modal is now unhidden before the video starts.
- **`aria-modal` is now earned.** A Tab trap keeps focus inside the dialog; without it the attribute promised an inert background that was still tabbable. `Escape` and the trap both no-op while the modal is closed, and a second trigger cannot swap the video out from under the opener.
- **Four copies of the circle/row markup became two components.** `x-product-application.circle` and `x-product-application.list-item` each own the button-or-plain-element branch and the modal payload attributes, so the map and the list can no longer drift apart.
- **Selector naming unified.** Triggers are `data-video-trigger`, closers are `data-video-close`, the description payload is `data-video-description`; `pa-modal-*` became `video-modal-*` so the CSS prefix says what it is.
- **The dialog is capped to the viewport** (`max-height`) with the title and close control in a fixed header, so a long description can never push the close button off screen.
- **Tests now parse the DOM** for the modal instead of a brittle regex, scope the list assertions to the `<ul>`, and cover the two states the review found untested: no modal at all when nothing is playable, and an inert list row carrying no trigger.
- **Accepted, not changed:** the scroll lock uses `overflow: hidden`, which is what the admin lightbox and the quote modal already do; iOS Safari can still rubber-band. A shared modal helper across the quote, calculator and video dialogs is a wider refactor than this ticket and would touch working code.

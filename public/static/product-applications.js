/*
 * Product Applications video modal.
 *
 * One shared dialog serves every trigger on the page: the map circles and the
 * list rows beneath the map. A trigger carries its payload as data attributes,
 * so the modal holds no per-item markup.
 *
 * Contract, mirroring the admin product-image lightbox:
 * - Opens only when the trigger has a video URL (empty never opens).
 * - Plays automatically muted with native controls.
 * - Closes on the close control, a backdrop click, or Escape.
 * - Pauses, resets, and releases the video on close, so audio never outlives
 *   the dialog and nothing keeps buffering.
 * - Locks background scroll while open and restores it on close.
 * - Moves focus into the dialog on open, keeps Tab inside it, and returns
 *   focus to the trigger on close.
 */
(function () {
    if (window.__tdtProductApplicationsInstalled) {
        return;
    }
    window.__tdtProductApplicationsInstalled = true;

    var modal = document.getElementById('product-application-modal');
    if (!modal) {
        return;
    }

    var TRIGGER = '[data-video-trigger]';
    var CLOSE = '[data-video-close]';
    var FOCUSABLE = 'a[href], button, video, input, select, textarea, [tabindex]:not([tabindex="-1"])';

    var dialog = modal.querySelector('[role="dialog"]');
    var titleEl = modal.querySelector('.video-modal-title');
    var video = modal.querySelector('.video-modal-video');
    var descriptionEl = modal.querySelector('[data-video-description]');
    var closeBtn = modal.querySelector('.video-modal-close');
    var lastFocused = null;

    function setScrollLocked(locked) {
        document.documentElement.style.overflow = locked ? 'hidden' : '';
        document.body.style.overflow = locked ? 'hidden' : '';
    }

    function show(trigger) {
        // Already open: ignore, so the opener stays the focus-return target.
        if (!modal.hidden) {
            return;
        }

        var url = (trigger.getAttribute('data-video-url') || '').trim();
        if (!url) {
            return;
        }

        lastFocused = trigger;

        if (titleEl) {
            titleEl.textContent = trigger.getAttribute('data-label') || '';
        }

        var description = (trigger.getAttribute('data-description') || '').trim();
        if (descriptionEl) {
            descriptionEl.textContent = description;
            descriptionEl.hidden = description === '';
        }

        // Unhide before playing: a browser will not reliably start a video on
        // a display:none element, so the player would open paused and blank.
        modal.hidden = false;
        setScrollLocked(true);

        if (video) {
            video.src = url;
            var started = video.play();
            if (started && typeof started.catch === 'function') {
                started.catch(function () {});
            }
        }

        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function hide() {
        if (modal.hidden) {
            return;
        }

        modal.hidden = true;

        if (video) {
            try {
                video.pause();
            } catch (e) {}
            video.removeAttribute('src');
            video.load();
        }

        if (descriptionEl) {
            descriptionEl.textContent = '';
            descriptionEl.hidden = true;
        }

        setScrollLocked(false);

        try {
            if (lastFocused && document.contains(lastFocused)) {
                lastFocused.focus();
            }
        } catch (e) {}

        lastFocused = null;
    }

    // A missing or unplayable file must not leave an empty player open.
    if (video) {
        video.addEventListener('error', function () {
            hide();
        });
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }

        var trigger = target.closest(TRIGGER);
        if (trigger) {
            event.preventDefault();
            show(trigger);
            return;
        }

        var closer = target.closest(CLOSE);
        if (closer && modal.contains(closer)) {
            event.preventDefault();
            hide();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (modal.hidden) {
            return;
        }

        if (event.key === 'Escape' || event.key === 'Esc') {
            hide();
            return;
        }

        if (event.key !== 'Tab' || !dialog) {
            return;
        }

        // Keep Tab inside the dialog: aria-modal promises the rest is inert.
        var focusables = dialog.querySelectorAll(FOCUSABLE);
        if (!focusables.length) {
            return;
        }

        var first = focusables[0];
        var last = focusables[focusables.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
})();

// TDT Powersteel - Smart Preloader: logo + blurred BG only when needed (slow network / slow page)
(function () {
    var preloader = document.getElementById('page-preloader');
    if (!preloader) return;

    var SHOW_DELAY_MS = 500;   // only show if load/navigation takes >500ms -> fast internet = no flash
    var MIN_VISIBLE_MS = 450;  // avoid ultra-quick flash when it does show
    var FALLBACK_HIDE_MS = 4000;
    var navShowTimer = null;
    var fallbackTimer = null;

    function isVisible() {
        return !preloader.classList.contains('is-hidden');
    }

    function showNow() {
        if (isVisible()) return;
        preloader.classList.remove('is-hidden');
        document.documentElement.classList.add('preloader-lock');
        window.__tdtPreloaderShown = true;
        window.__tdtPreloaderShowAt = Date.now();
    }

    function hide() {
        if (navShowTimer) { clearTimeout(navShowTimer); navShowTimer = null; }
        if (fallbackTimer) { clearTimeout(fallbackTimer); fallbackTimer = null; }

        if (!isVisible()) {
            // never shown (fast load) -> just ensure lock is removed
            document.documentElement.classList.remove('preloader-lock');
            return;
        }
        // respect minimum visible time to avoid jarring flash
        var shownAt = window.__tdtPreloaderShowAt || Date.now();
        var elapsed = Date.now() - shownAt;
        var delay = Math.max(0, MIN_VISIBLE_MS - elapsed);
        setTimeout(function () {
            preloader.classList.add('is-hidden');
            document.documentElement.classList.remove('preloader-lock');
        }, delay);
    }

    function scheduleShow() {
        if (isVisible()) return;
        if (navShowTimer) return;
        navShowTimer = setTimeout(showNow, SHOW_DELAY_MS);
    }

    // Initial page load handling
    if (document.readyState === 'complete') {
        hide();
    } else {
        window.addEventListener('load', hide, { once: true });
        fallbackTimer = setTimeout(hide, FALLBACK_HIDE_MS);
    }

    // bfcache restore (back/forward) - load won't fire, pageshow will
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            // restored from bfcache is instant -> keep hidden, no flash
            hide();
        }
    });

    // Slow navigation: show only if next page takes a while to arrive
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href) return;
        if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        if (a.target === '_blank' || a.hasAttribute('download')) return;
        try {
            var url = new URL(a.href, window.location.origin);
            if (url.origin !== window.location.origin) return;
            // same-page hash navigation -> no preloader
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;
        } catch (err) { return; }
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;

        // Don't preventDefault - let browser navigate normally.
        // We schedule a show; if navigation is fast (<280ms), timer is cancelled on new page's JS
        // or never fires because page unloads quickly - user sees no flash. If slow, timer fires
        // and preloader appears while waiting for next page.
        scheduleShow();
        // No delay on navigation - fast stays fast.
    }, true);

    // Safety: if JS triggers navigation via location.href / form submit, also schedule
    window.addEventListener('beforeunload', function () {
        // only schedule, not immediate - keeps fast navigations flash-free
        scheduleShow();
    });

    // Expose for manual testing / supervisor demo:
    // window.TDTPreloader.show() -> force show, window.TDTPreloader.hide() -> hide
    // window.TDTPreloader.schedule() -> smart delayed show
    window.TDTPreloader = {
        show: showNow,
        hide: hide,
        schedule: scheduleShow,
        isVisible: isVisible
    };
})();

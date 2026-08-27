/**
 * Admin sidebar live badge counts.
 *
 * Filament only computes each resource's navigation badge when a page
 * is rendered — it never polls on its own, so a staff member sitting on
 * one screen (e.g. Live Chat) won't see a new Quote Request or Referral
 * show up in the sidebar until they navigate somewhere or hit refresh.
 *
 * This polls a small JSON endpoint (NavBadgeController) every few
 * seconds and patches the existing sidebar <a> elements directly,
 * without needing a full Livewire/page reload.
 */
(function () {
    const NAV_COUNTS_URL = '/admin/api/nav-counts';
    const POLL_INTERVAL_MS = 5000; // testing value — dial back up to ~15000-30000 once confirmed working, to go easy on the server

    // JSON key from NavBadgeController -> the URL segment used in that
    // resource's sidebar link (e.g. /admin/quote-requests), plus the
    // Filament color its badge uses (must match each Resource's own
    // getNavigationBadgeColor(), so a freshly-created badge looks
    // identical to one Filament would have rendered itself).
    const RESOURCES = {
        'chat-sessions': { path: 'chat-sessions', color: 'danger' },
        'contact-messages': { path: 'contact-messages', color: 'warning' },
        'quote-requests': { path: 'quote-requests', color: 'warning' },
        'referrals': { path: 'referrals', color: 'warning' },
    };

    function findSidebarLink(pathSegment) {
        const links = document.querySelectorAll('a.fi-sidebar-item-btn[href]');
        for (const link of links) {
            try {
                const url = new URL(link.href, window.location.origin);
                // Match the exact resource index path, e.g. .../admin/referrals
                // (avoid partial matches like "referrals" matching some other slug).
                if (url.pathname.replace(/\/+$/, '').endsWith('/' + pathSegment)) {
                    return link;
                }
            } catch (e) {
                // ignore malformed hrefs
            }
        }
        return null;
    }

    // Builds Filament's exact badge markup from scratch (fi-badge /
    // fi-badge-label-ctn / fi-badge-label, plus fi-color + fi-color-{x}
    // for theming) rather than relying on cloning an existing badge
    // elsewhere on the page. Cloning silently did nothing whenever every
    // resource's count was 0 on page load — there was nothing on the
    // page yet to copy from, so a fresh 0 -> 1 transition (e.g. the very
    // first chat message of the day) never appeared without a manual
    // refresh. This version has no such dependency.
    function buildBadge(count, color) {
        const badge = document.createElement('span');
        badge.className = `fi-badge fi-size-md fi-color fi-color-${color}`;

        const labelCtn = document.createElement('span');
        labelCtn.className = 'fi-badge-label-ctn';

        const label = document.createElement('span');
        label.className = 'fi-badge-label';
        label.textContent = String(count);

        labelCtn.appendChild(label);
        badge.appendChild(labelCtn);
        return badge;
    }

    function setBadge(link, count, color) {
        if (!link) {
            return;
        }

        const ctn = link.querySelector('.fi-sidebar-item-badge-ctn');
        const existingBadge = ctn ? ctn.querySelector('.fi-badge') : null;

        if (count <= 0) {
            if (ctn) {
                ctn.remove();
            }
            return;
        }

        if (existingBadge) {
            const label = existingBadge.querySelector('.fi-badge-label');
            if (label) {
                label.textContent = String(count);
            }
            return;
        }

        const newCtn = document.createElement('span');
        newCtn.className = 'fi-sidebar-item-badge-ctn';
        newCtn.appendChild(buildBadge(count, color));
        link.appendChild(newCtn);
    }

    async function pollCounts() {
        try {
            const response = await fetch(NAV_COUNTS_URL, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const counts = await response.json();

            Object.keys(RESOURCES).forEach((key) => {
                if (!Object.prototype.hasOwnProperty.call(counts, key)) {
                    return;
                }
                const { path, color } = RESOURCES[key];
                const link = findSidebarLink(path);
                setBadge(link, counts[key], color);
            });
        } catch (error) {
            // Silent — a missed poll just means the badge stays as-is
            // until the next successful one; not worth surfacing to the user.
        }
    }

    function start() {
        pollCounts();
        setInterval(pollCounts, POLL_INTERVAL_MS);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();

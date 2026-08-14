/* =========================================
   TDT ADMIN — STATUS QUICK-FILTER PILLS
   -----------------------------------------
   Adds one-click status pills (All / New / Contacted / ...)
   IN THE SAME ROW as the "Type to search" box on select
   changelist pages, instead of making the user open the
   "Filters" panel.

   Also injects a "Download Excel" button next to the
   "Filters" button — exports whatever the current view is
   showing (status pill, search, sidebar filters, and
   date-hierarchy drill-down are all respected server-side).

   To add either feature to another model's changelist, add
   another entry to STATUS_FILTER_PAGES / EXPORT_EXCEL_URLS
   below with that page's URL path.
========================================= */

(function () {
    "use strict";

    var STATUS_FILTER_PAGES = {
        "/admin/core/quoterequest/": {
            param: "status__exact",
            options: [
                { value: "", label: "All" },
                { value: "new", label: "New" },
                { value: "contacted", label: "Contacted" },
                { value: "closed", label: "Closed" }
            ]
        },
        "/admin/core/referral/": {
            param: "status__exact",
            options: [
                { value: "", label: "All" },
                { value: "new", label: "New" },
                { value: "contacted", label: "Contacted" },
                { value: "rewarded", label: "Rewarded" }
            ]
        }
    };

    var EXPORT_EXCEL_URLS = {
        "/admin/core/quoterequest/": "/admin/core/quoterequest/export-excel/",
        "/admin/core/referral/": "/admin/core/referral/export-excel/"
    };

    // Shared helper: matches the current path against a config map's
    // keys by prefix (so date-hierarchy drill-down URLs still match).
    function matchByPrefix(map) {
        var path = window.location.pathname;
        for (var key in map) {
            if (path.indexOf(key) === 0) return map[key];
        }
        return null;
    }

    function currentConfig() {
        return matchByPrefix(STATUS_FILTER_PAGES);
    }

    function buildPills(config) {
        var searchInput = document.querySelector('input[name="q"]');
        if (!searchInput) return;

        var anchor = searchInput.closest("form") || searchInput.parentElement;
        if (!anchor || !anchor.parentElement) return;
        if (document.querySelector(".tdt-status-pills")) return; // avoid duplicating

        var currentParams = new URLSearchParams(window.location.search);
        var active = currentParams.get(config.param) || "";

        var wrap = document.createElement("div");
        wrap.className = "tdt-status-pills";

        config.options.forEach(function (opt) {
            var a = document.createElement("a");
            a.textContent = opt.label;
            a.className = "tdt-status-pill" + (opt.value === active ? " tdt-status-pill-active" : "");

            var newParams = new URLSearchParams(window.location.search);
            if (opt.value) {
                newParams.set(config.param, opt.value);
            } else {
                newParams.delete(config.param);
            }
            newParams.delete("p"); // reset back to page 1 whenever the filter changes

            var qs = newParams.toString();
            a.href = window.location.pathname + (qs ? "?" + qs : "");

            wrap.appendChild(a);
        });

        // Place the search form + pills together inside ONE flex row we
        // build ourselves, so it doesn't depend on wherever Unfold's own
        // markup/CSS happens to render them.
        var row = document.createElement("div");
        row.className = "tdt-search-pills-row";

        anchor.parentElement.insertBefore(row, anchor);
        row.appendChild(anchor); // move the search form into the row
        row.appendChild(wrap);   // add the pills next to it
    }

    // Unfold doesn't give the "Filters" button a stable class name across
    // versions (it's plain Tailwind utility classes), so we find it by its
    // visible text instead — reliable regardless of markup/class changes.
    function findFilterButton() {
        var candidates = document.querySelectorAll("a, button");
        for (var i = 0; i < candidates.length; i++) {
            var text = candidates[i].textContent.trim();
            if (text.indexOf("Filters") === 0) return candidates[i];
        }
        return null;
    }

    function buildExcelButton() {
        var exportUrl = matchByPrefix(EXPORT_EXCEL_URLS);
        if (!exportUrl) return;
        if (document.querySelector(".tdt-excel-download")) return; // avoid duplicating

        var filterBtn = findFilterButton();
        if (!filterBtn || !filterBtn.parentElement) return;

        var a = document.createElement("a");
        a.className = "tdt-excel-download";
        a.href = exportUrl + window.location.search; // keep active filters/search applied
        a.innerHTML =
            'Download Excel<span class="material-symbols-outlined md-18">download</span>';

        filterBtn.parentElement.insertBefore(a, filterBtn);
    }

    function init() {
        var config = currentConfig();
        if (config) buildPills(config);
        buildExcelButton();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
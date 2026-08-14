/* =========================================
   TDT ADMIN — SETTINGS PAGE TAB SWITCHING
   ========================================= */

(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var tabs = document.querySelectorAll(".tdt-tab");
        var panels = document.querySelectorAll(".tdt-panel");
        if (!tabs.length || !panels.length) return; // hindi ito ang settings page

        var initial = window.TDT_ACTIVE_TAB || "general";
        if (!document.querySelector('.tdt-panel[data-panel="' + initial + '"]')) {
            initial = "general";
        }

        function activate(tabName) {
            tabs.forEach(function (t) {
                t.classList.toggle("tdt-tab-active", t.dataset.tab === tabName);
            });
            panels.forEach(function (p) {
                p.classList.toggle("tdt-panel-active", p.dataset.panel === tabName);
            });
            var url = new URL(window.location.href);
            url.searchParams.set("tab", tabName);
            window.history.replaceState(null, "", url);
        }

        tabs.forEach(function (t) {
            t.addEventListener("click", function () {
                activate(t.dataset.tab);
            });
        });

        activate(initial);
    });
})();

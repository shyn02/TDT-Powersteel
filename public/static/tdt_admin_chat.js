/* =========================================
   TDT ADMIN — LIVE CHAT NOTIFICATIONS
   -----------------------------------------
   1) Bell widget (loads on EVERY admin page via UNFOLD["SCRIPTS"] —
      see settings.py note): badge count + dropdown of unread client
      messages + browser (OS-level) push notification.
   2) Dedicated Live Chat page (#tdt-live-chat-app on
      templates/admin/live_chat.html): session list + thread + reply.

   NOTE: If your Django admin is NOT mounted at /admin/, update
   ADMIN_BASE below to match.
========================================= */

(function () {
    "use strict";

    var ADMIN_BASE = "/admin/live-chat/api/";
    var LIVE_CHAT_PAGE_URL = "/admin/core/chatsession/";
    var POLL_MS = 5000;

    // Canned / quick-reply responses for the admin reply box.
    // Clicking a chip drops the text into the reply textarea so the
    // agent can still tweak it before hitting Send.
    var QUICK_REPLIES = [
        { label: "Greeting", text: "Hi! Thanks for reaching out to TDT Powersteel — how can I help you today?" },
        { label: "Ask for details", text: "Could you share the product name, size/spec, and estimated quantity so I can check pricing and availability?" },
        { label: "Delivery info", text: "We deliver nationwide through our distribution hubs. Could I have your delivery address so I can confirm the lead time?" },
        { label: "Will check & get back", text: "Let me check that for you and I'll get back to you shortly." },
        { label: "Closing", text: "Thank you for chatting with TDT Powersteel! Let us know if you need anything else." }
    ];

    function getCookie(name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length === 2) return parts.pop().split(";").shift();
        return null;
    }

    function escapeHtml(s) {
        var d = document.createElement("div");
        d.textContent = s || "";
        return d.innerHTML;
    }

    function apiFetch(path, options) {
        options = options || {};
        options.headers = options.headers || {};
        options.headers["X-Requested-With"] = "XMLHttpRequest";
        if (options.method && options.method !== "GET") {
            options.headers["X-CSRFToken"] = getCookie("csrftoken");
            options.headers["Content-Type"] = "application/json";
        }
        return fetch(ADMIN_BASE + path, options).then(function (r) {
            if (!r.ok) throw new Error("HTTP " + r.status);
            return r.json();
        });
    }

    // ---------------------------------------------------------------
    // DEDICATED LIVE CHAT PAGE
    // ---------------------------------------------------------------
    var activeSessionToken = null;
    var threadPollTimer = null;
    var salesReps = []; // { id, name, position } — for the "Transfer to Sales Rep" menu
    var currentAssignedToId = null;

    function initLiveChatPage() {
        loadSessionsList();
        setInterval(loadSessionsList, POLL_MS);
        loadSalesReps();

        var params = new URLSearchParams(window.location.search);
        var preselect = params.get("session");
        if (preselect) openSession(preselect);

        var form = document.getElementById("tdtLcReplyForm");
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            sendReply();
        });

        var input = document.getElementById("tdtLcReplyInput");
        // Enter = send, Shift+Enter = new line (was just inserting a
        // line break / space before, since a <textarea> doesn't submit
        // its form on Enter by default).
        input.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendReply();
            }
        });
        input.addEventListener("input", function () {
            autosizeReplyInput(input);
        });

        buildQuickReplies();
    }

    function autosizeReplyInput(input) {
        input.style.height = "auto";
        input.style.height = Math.min(input.scrollHeight, 70) + "px";
    }

    function buildQuickReplies() {
        var container = document.getElementById("tdtLcQuickReplies");
        if (!container) return;
        container.innerHTML = "";
        QUICK_REPLIES.forEach(function (item) {
            var chip = document.createElement("button");
            chip.type = "button";
            chip.className = "tdt-lc-chip";
            chip.textContent = item.label;
            chip.addEventListener("click", function () {
                var input = document.getElementById("tdtLcReplyInput");
                input.value = input.value ? (input.value.replace(/\s+$/, "") + " " + item.text) : item.text;
                autosizeReplyInput(input);
                input.focus();
            });
            container.appendChild(chip);
        });
    }

    function loadSalesReps() {
        apiFetch("sales-reps/").then(function (data) {
            salesReps = data.reps || [];
        }).catch(function (err) {
            console.error("[TDT admin chat] sales reps load failed:", err);
        });
    }

    // ---- "Transfer to Sales Rep" ⋮ menu sa thread header ----
    function buildThreadHeader(session) {
        currentAssignedToId = session.assignedToId;
        var header = document.getElementById("tdtLcThreadHeader");

        var assignedBadge = session.assignedToName
            ? '<span class="tdt-lc-assigned-badge">Assigned to ' + escapeHtml(session.assignedToName) + '</span>'
            : '<span class="tdt-lc-assigned-badge tdt-lc-assigned-none">Shared inbox</span>';

        header.innerHTML =
            '<div class="tdt-lc-thread-header-main">' +
            '  <span class="tdt-lc-thread-header-name">' + escapeHtml(session.clientName) +
                 (session.page ? ' <span class="tdt-lc-thread-header-page">— ' + escapeHtml(session.page) + '</span>' : '') +
            '  </span>' +
                 assignedBadge +
            '</div>' +
            '<div class="tdt-lc-thread-menu-wrap">' +
            '  <button type="button" class="tdt-lc-thread-menu-btn" id="tdtLcThreadMenuBtn" aria-label="Chat options">&#8942;</button>' +
            '  <div class="tdt-lc-thread-menu" id="tdtLcThreadMenu" hidden>' +
            '    <div class="tdt-lc-thread-menu-label">Transfer to Sales Rep</div>' +
                  (salesReps.length
                      ? salesReps.map(function (rep) {
                          var activeClass = rep.id === currentAssignedToId ? " tdt-lc-thread-menu-item-active" : "";
                          return '<button type="button" class="tdt-lc-thread-menu-item' + activeClass + '" data-user-id="' + rep.id + '">' +
                              escapeHtml(rep.name) + ' <span class="tdt-lc-thread-menu-position">(' + escapeHtml(rep.position) + ')</span>' +
                              '</button>';
                      }).join("")
                      : '<div class="tdt-lc-thread-menu-empty">No sales reps available.</div>') +
                  (currentAssignedToId
                      ? '<button type="button" class="tdt-lc-thread-menu-item tdt-lc-thread-menu-unassign" data-user-id="">Return to Shared Inbox</button>'
                      : '') +
            '  </div>' +
            '</div>';

        var menuBtn = document.getElementById("tdtLcThreadMenuBtn");
        var menu = document.getElementById("tdtLcThreadMenu");
        menuBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            menu.hidden = !menu.hidden;
        });
        Array.prototype.forEach.call(menu.querySelectorAll("[data-user-id]"), function (item) {
            item.addEventListener("click", function () {
                menu.hidden = true;
                transferSession(item.getAttribute("data-user-id"));
            });
        });
    }

    document.addEventListener("click", function (e) {
        var menu = document.getElementById("tdtLcThreadMenu");
        if (menu && !menu.hidden && !menu.contains(e.target) && e.target.id !== "tdtLcThreadMenuBtn") {
            menu.hidden = true;
        }
    });

    function transferSession(userId) {
        if (!activeSessionToken) return;
        apiFetch("sessions/" + encodeURIComponent(activeSessionToken) + "/transfer/", {
            method: "POST",
            body: JSON.stringify({ userId: userId || null })
        }).then(function () {
            loadThread();
            loadSessionsList();
        }).catch(function (err) {
            console.error("[TDT admin chat] transfer failed:", err);
        });
    }

    function loadSessionsList() {
        apiFetch("sessions/").then(function (data) {
            var list = document.getElementById("tdtLcSessionsList");
            if (!data.sessions.length) {
                list.innerHTML = '<p class="tdt-lc-empty">No chat sessions yet.</p>';
                return;
            }
            list.innerHTML = data.sessions.map(function (s) {
                var activeClass = s.token === activeSessionToken ? " tdt-lc-session-active" : "";
                var unreadBadge = s.unread > 0 ? '<span class="tdt-lc-session-unread">' + s.unread + '</span>' : "";
                var assignedTag = s.assignedToName
                    ? '<span class="tdt-lc-session-assigned">&rarr; ' + escapeHtml(s.assignedToName) + '</span>' : "";
                return '<button type="button" class="tdt-lc-session-item' + activeClass + '" data-token="' + s.token + '">' +
                    '<span class="tdt-lc-session-name">' + escapeHtml(s.clientName) + unreadBadge + '</span>' +
                    '<span class="tdt-lc-session-preview">' + escapeHtml((s.lastMessage || "").slice(0, 50)) + assignedTag + '</span>' +
                    '</button>';
            }).join("");
            Array.prototype.forEach.call(list.querySelectorAll(".tdt-lc-session-item"), function (el) {
                el.addEventListener("click", function () {
                    openSession(el.getAttribute("data-token"));
                });
            });
        });
    }

    function openSession(token) {
        activeSessionToken = token;
        document.getElementById("tdtLcThreadEmpty").style.display = "none";
        document.getElementById("tdtLcThreadActive").classList.add("tdt-lc-thread-visible");

        clearInterval(threadPollTimer);
        loadThread();
        threadPollTimer = setInterval(loadThread, POLL_MS);
    }

    function loadThread() {
        if (!activeSessionToken) return;
        apiFetch("sessions/" + encodeURIComponent(activeSessionToken) + "/").then(function (data) {
            buildThreadHeader(data.session);
            var box = document.getElementById("tdtLcThreadMessages");
            box.innerHTML = data.messages.map(function (m) {
                return '<div class="tdt-lc-msg tdt-lc-msg-' + m.sender + '"><span>' + escapeHtml(m.text) + '</span></div>';
            }).join("");
            box.scrollTop = box.scrollHeight;
            loadSessionsList();
        });
    }

    function sendReply() {
        var input = document.getElementById("tdtLcReplyInput");
        var text = input.value.trim();
        if (!text || !activeSessionToken) return;
        input.value = "";
        autosizeReplyInput(input);
        apiFetch("sessions/" + encodeURIComponent(activeSessionToken) + "/reply/", {
            method: "POST",
            body: JSON.stringify({ text: text })
        }).then(loadThread).catch(function (err) {
            console.error("[TDT admin chat] reply failed:", err);
        });
    }

    // ---------------------------------------------------------------
    // SIDEBAR BADGES (Quote Requests / Referrals / Live Chat) — live
    // update without a full page refresh. Reuses the same
    // exact class list used by Unfold itself for the badge
    // span (taken from the actual HTML) so it automatically
    // follows our CSS override in admin_custom.css
    // (orange number only, no background).
    // ---------------------------------------------------------------
    var SIDEBAR_BADGE_CLASS =
        "font-semibold h-[18px] leading-[18px] ml-2 px-1 relative rounded-xs " +
        "text-center text-[11px] whitespace-nowrap uppercase min-w-[18px] " +
        "bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400";

    var SIDEBAR_BADGE_MAP = [
        { label: "Quote Requests", key: "quoteRequests" },
        { label: "Referrals", key: "referrals" },
        { label: "Live Chat", key: "liveChat" }
    ];

    function findSidebarLink(label) {
        var links = document.querySelectorAll("#nav-sidebar a");
        for (var i = 0; i < links.length; i++) {
            var titleSpan = links[i].querySelector("span:not([class])");
            if (titleSpan && titleSpan.textContent.trim() === label) return links[i];
        }
        return null;
    }

    function updateSidebarBadge(link, count) {
        if (!link) return;
        var badge = link.querySelector('span[class*="min-w-[18px]"]');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement("span");
                badge.className = SIDEBAR_BADGE_CLASS;
                link.appendChild(badge);
            }
            badge.textContent = count > 99 ? "99+" : String(count);
        } else if (badge) {
            badge.remove();
        }
    }

    function pollSidebarBadges() {
        if (!document.getElementById("nav-sidebar")) return;
        apiFetch("sidebar-counts/").then(function (data) {
            SIDEBAR_BADGE_MAP.forEach(function (item) {
                updateSidebarBadge(findSidebarLink(item.label), data[item.key] || 0);
            });
        }).catch(function (err) {
            console.error("[TDT admin chat] sidebar badge poll failed:", err);
        });
    }

    // ---------------------------------------------------------------
    // INIT
    // ---------------------------------------------------------------
    function init() {
        pollSidebarBadges();
        setInterval(pollSidebarBadges, POLL_MS);

        if (document.getElementById("tdt-live-chat-app")) {
            initLiveChatPage();
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
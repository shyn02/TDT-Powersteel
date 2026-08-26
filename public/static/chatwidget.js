/* =========================================
   TDT QUICK CHAT WIDGET — LOGIC
   -----------------------------------------
   UPDATED BEHAVIOR:
   1) STAYS OPEN & SAVED when clicking to another section (Home -> Products).
   2) RESETS COMPLETELY when closing the tab/browser ("X").
   3) RESETS COMPLETELY when hitting Refresh/Reload (F5).
========================================= */

(function () {
    "use strict";

    // 1) DETECT RELOAD AND CLEAR STORAGE
    // Check if the user refreshed the page (F5). If yes, wipe the session.
    const navEntries = performance.getEntriesByType("navigation");
    if (navEntries.length > 0 && navEntries[0].type === "reload") {
        sessionStorage.clear(); // Wipes the chat data specifically on refresh
    }

    // ---- CONFIG ----------------------------------------------------
    const CHAT_API_ENDPOINT = "/api/chat/messages";
    const POLL_INTERVAL_MS = 4000;

    // We now use sessionStorage instead of localStorage.
    // sessionStorage automatically dies when the tab is closed.
    const SS_KEYS = {
        session: "tdt_chat_session_id",
        transcript: "tdt_chat_transcript",
        mode: "tdt_chat_mode",
        panelOpen: "tdt_chat_panel_open",
        hasGreeted: "tdt_chat_has_greeted",
        lastAgentMsgId: "tdt_chat_last_agent_id"
    };

    function getSessionId() {
        let id = sessionStorage.getItem(SS_KEYS.session);
        if (!id) {
            // SEC-05: use cryptographically secure random (256-bit, 64 hex chars)
            if (window.crypto && window.crypto.getRandomValues) {
                const bytes = new Uint8Array(32);
                window.crypto.getRandomValues(bytes);
                id = Array.from(bytes, function (b) { return b.toString(16).padStart(2, "0"); }).join("");
            } else {
                id = "sess_" + Date.now() + "_" + Math.random().toString(36).slice(2, 8);
            }
            sessionStorage.setItem(SS_KEYS.session, id);
        }
        return id;
    }

    function rotateSessionId(newId) {
        if (!newId || typeof newId !== "string") return;
        sessionStorage.setItem(SS_KEYS.session, newId);
    }

    function loadTranscript() {
        try {
            const raw = sessionStorage.getItem(SS_KEYS.transcript);
            return raw ? JSON.parse(raw) : [];
        } catch (err) {
            return [];
        }
    }

    function persistMessage(from, text, label) {
        try {
            const transcript = loadTranscript();
            transcript.push({ from: from, text: text, label: label || null });
            sessionStorage.setItem(SS_KEYS.transcript, JSON.stringify(transcript.slice(-200)));
        } catch (err) { }
    }

    function persistMode(newMode) {
        mode = newMode;
        try { sessionStorage.setItem(SS_KEYS.mode, newMode); } catch (err) { }
    }

    function persistPanelOpen(isOpen) {
        try { sessionStorage.setItem(SS_KEYS.panelOpen, isOpen ? "1" : "0"); } catch (err) { }
    }

    function persistHasGreeted() {
        try { sessionStorage.setItem(SS_KEYS.hasGreeted, "1"); } catch (err) { }
    }

    function persistLastAgentMsgId(id) {
        try { sessionStorage.setItem(SS_KEYS.lastAgentMsgId, String(id)); } catch (err) { }
    }

    // Scripted bot FAQ
    const BOT_MENU = [
        {
            id: "products",
            label: "Our Steel Products",
            reply:"We carry 1,000+ steel products — deformed bars, structural beams, plates, pipes, and more, all PNS-certified. What specific product do you need?"
        },
        {
            id: "quote",
            label: "Request a Quote",
            reply: "Sure! You can tap the 'REQUEST A QUOTE' button at the top of the page, or simply let me know the item and estimated quantity here — I'll forward it to our sales team."
        },
        {
            id: "delivery",
            label: "Delivery Coverage",
            reply: "We deliver nationwide through multiple distribution hubs, so wherever you are in the Philippines, we can deliver your order."
        },
        {
            id: "human",
            label: "Talk to a human agent",
            reply: null
        }
    ];

    const GREETING = "Hi! 👋 Welcome to TDT Powersteel. I'm the quick-chat assistant — pick a topic below, or type your question.";
    const HUMAN_HANDOFF_MSG = "Okay, connecting you to a member of our team now. Please leave your message and a customer service representative will reply as soon as they're available.";
    // Removed verbose queued msg, now shows check like Messenger/Viber (see handleUserSend)

    let mode = "bot";
    let panelOpen = false;
    let hasGreeted = false;
    let pollTimer = null;
    let lastAgentMsgId = 0;

    // ---- DOM BUILD ---------------------------------------------------
    function buildWidget() {
        const launcher = document.createElement("button");
        launcher.className = "tdt-chat-launcher";
        launcher.setAttribute("aria-label", "Open quick chat");
        launcher.innerHTML =
            '<i class="fas fa-comment-dots"></i><i class="fas fa-xmark"></i><span class="tdt-badge"></span>';

        const panel = document.createElement("div");
        panel.className = "tdt-chat-panel";
        panel.setAttribute("role", "dialog");
        panel.setAttribute("aria-label", "TDT Powersteel quick chat");
        panel.innerHTML = `
            <div class="tdt-chat-header">
                <div class="tdt-avatar"><i class="fas fa-hard-hat"></i></div>
                <div class="tdt-header-text">
                    <span class="tdt-header-title">TDT Quick Chat</span>
                    <span class="tdt-header-status">
                        <span class="tdt-status-dot"></span>
                        <span class="tdt-status-label">Assistant online</span>
                    </span>
                </div>
                <button class="tdt-close-btn" aria-label="Close chat">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="tdt-chat-body" id="tdtChatBody"></div>
            <div class="tdt-quick-replies" id="tdtQuickReplies"></div>
            <div class="tdt-chat-input-row">
                <textarea id="tdtChatInput" rows="1" placeholder="Type a message..."></textarea>
                <button class="tdt-chat-send-btn" id="tdtChatSend" aria-label="Send message">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        `;

        document.body.appendChild(launcher);
        document.body.appendChild(panel);

        return { launcher, panel };
    }

    function scrollToBottom(body) {
        body.scrollTop = body.scrollHeight;
    }

    function addMessage(body, from, text, skipPersist, label) {
        const row = document.createElement("div");
        row.className = "tdt-msg-row tdt-from-" + from;

        const bubble = document.createElement("div");
        bubble.className = "tdt-bubble";

        if (from === "agent") {
            const labelEl = document.createElement("span");
            labelEl.className = "tdt-sender-label";
            labelEl.textContent = label || "Agent";
            bubble.appendChild(labelEl);
        }

        const textNode = document.createElement("span");
        textNode.textContent = text;
        bubble.appendChild(textNode);

        row.appendChild(bubble);
        body.appendChild(row);
        scrollToBottom(body);

        if (!skipPersist) persistMessage(from, text, label);
        return row;
    }

    function showTyping(body) {
        const row = document.createElement("div");
        row.className = "tdt-msg-row tdt-from-bot tdt-typing";
        row.innerHTML = '<div class="tdt-bubble"><span></span><span></span><span></span></div>';
        body.appendChild(row);
        scrollToBottom(body);
        return row;
    }

    function renderQuickReplies(container, disabled) {
        container.innerHTML = "";
        if (mode !== "bot") return;
        BOT_MENU.forEach(function (item) {
            const chip = document.createElement("button");
            chip.className = "tdt-chip";
            chip.textContent = item.label;
            chip.disabled = !!disabled;
            chip.addEventListener("click", function () {
                handleMenuPick(item);
            });
            container.appendChild(chip);
        });
    }

    function updateHeaderStatus(label, isHuman) {
        const statusLabel = document.querySelector(".tdt-header-status .tdt-status-label");
        const statusDot = document.querySelector(".tdt-status-dot");
        if (statusLabel) statusLabel.textContent = label;
        if (statusDot) statusDot.classList.toggle("tdt-status-human", !!isHuman);
    }

    // ---- BACKEND HOOK --------------------------------------------------
    async function sendToBackend(payload) {
        try {
            const res = await fetch(CHAT_API_ENDPOINT, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                try {
                    const data = await res.clone().json();
                    if (data && data.newSessionId) {
                        rotateSessionId(data.newSessionId);
                    }
                } catch (e) {}
            }
            return res.ok;
        } catch (err) {
            console.error("[TDT chat] Could not reach the chat server:", err);
            return false;
        }
    }

    function startPollingForAgentReplies(body) {
        if (pollTimer) return;
        pollTimer = setInterval(async function () {
            try {
                const url =
                    CHAT_API_ENDPOINT +
                    "?sessionId=" + encodeURIComponent(getSessionId()) +
                    "&after=" + lastAgentMsgId;
                const res = await fetch(url);
                if (!res.ok) return;
                const data = await res.json();
                (data.messages || []).forEach(function (m) {
                    addMessage(body, "agent", m.text, false, m.agentName);
                    lastAgentMsgId = Math.max(lastAgentMsgId, m.id);
                    persistLastAgentMsgId(lastAgentMsgId);
                    updateHeaderStatus("Chatting with an agent", true);
                });
            } catch (err) {
                console.error("[TDT chat] Polling error:", err);
            }
        }, POLL_INTERVAL_MS);
    }

    // ---- CONVERSATION LOGIC --------------------------------------------
    function handleMenuPick(item) {
        const body = document.getElementById("tdtChatBody");
        const quickReplies = document.getElementById("tdtQuickReplies");

        addMessage(body, "user", item.label);

        if (item.id === "human") {
            switchToHumanMode(body, quickReplies);
            return;
        }

        const typingRow = showTyping(body);
        setTimeout(function () {
            typingRow.remove();
            addMessage(body, "bot", item.reply);
        }, 500);
    }

    function switchToHumanMode(body, quickReplies) {
        persistMode("human");
        quickReplies.innerHTML = "";
        updateHeaderStatus("Waiting for an agent", true);

        const typingRow = showTyping(body);
        setTimeout(function () {
            typingRow.remove();
            addMessage(body, "bot", HUMAN_HANDOFF_MSG);

            sendToBackend({
                type: "handoff_requested",
                sessionId: getSessionId(),
                page: location.pathname.replace(/^\/|\.html$/g, "") || "home"
            });

            startPollingForAgentReplies(body);
        }, 500);
    }

    async function handleUserSend() {
        const input = document.getElementById("tdtChatInput");
        const body = document.getElementById("tdtChatBody");
        const text = input.value.trim();
        if (!text) return;

        const userRow = addMessage(body, "user", text);
        input.value = "";
        autosizeInput(input);

        if (mode === "human") {
            // Small Sent check outside bubble like Messenger/Viber (not inside chat history)
            const sent = document.createElement("div");
            sent.className = "tdt-sent-label";
            sent.innerHTML = "✓ Sent";
            sent.style.cssText = "font-size:10px;color:#9a9a9a;text-align:right;margin:2px 14px 0 0;font-style:italic;letter-spacing:0.02em;";
            // insert right after the user row, outside bubble
            userRow.parentNode.insertBefore(sent, userRow.nextSibling);
            sendToBackend({
                type: "message",
                sessionId: getSessionId(),
                text: text,
                page: location.pathname.replace(/^\/|\.html$/g, "") || "home"
            });
            return;
        }

        const typingRow = showTyping(body);
        setTimeout(function () {
            typingRow.remove();
            addMessage(
                body,
                "bot",
                "I'm sorry, I can't read free-form questions yet. Please choose a topic below, or tap 'Talk to a human agent' to speak with our team."
            );
        }, 500);
    }

    function autosizeInput(input) {
        input.style.height = "auto";
        input.style.height = Math.min(input.scrollHeight, 70) + "px";
    }

    // ---- OPEN / CLOSE ----------------------------------------------------
    function togglePanel(launcher, panel) {
        panelOpen = !panelOpen;
        launcher.classList.toggle("is-open", panelOpen);
        panel.classList.toggle("is-open", panelOpen);
        launcher.setAttribute("aria-label", panelOpen ? "Close quick chat" : "Open quick chat");
        persistPanelOpen(panelOpen);

        if (panelOpen && !hasGreeted) {
            hasGreeted = true;
            persistHasGreeted();
            const body = document.getElementById("tdtChatBody");
            const quickReplies = document.getElementById("tdtQuickReplies");
            const typingRow = showTyping(body);
            setTimeout(function () {
                typingRow.remove();
                addMessage(body, "bot", GREETING);
                renderQuickReplies(quickReplies, false);
            }, 400);
        }
    }

    // Restore state from sessionStorage so the chat doesn't break when navigating pages
    function restoreState(launcher, panel) {
        const body = document.getElementById("tdtChatBody");
        const quickReplies = document.getElementById("tdtQuickReplies");

        mode = sessionStorage.getItem(SS_KEYS.mode) || "bot";
        hasGreeted = sessionStorage.getItem(SS_KEYS.hasGreeted) === "1";
        lastAgentMsgId = parseInt(sessionStorage.getItem(SS_KEYS.lastAgentMsgId) || "0", 10) || 0;
        const wasPanelOpen = sessionStorage.getItem(SS_KEYS.panelOpen) === "1";

        const transcript = loadTranscript();
        transcript.forEach(function (m) {
            addMessage(body, m.from, m.text, true, m.label);
        });

        if (mode === "human") {
            const hasAgentReply = transcript.some(function (m) { return m.from === "agent"; });
            updateHeaderStatus(hasAgentReply ? "Chatting with an agent" : "Waiting for an agent", true);
            startPollingForAgentReplies(body);
        } else if (hasGreeted) {
            renderQuickReplies(quickReplies, false);
        }

        if (wasPanelOpen) {
            panelOpen = true;
            launcher.classList.add("is-open");
            panel.classList.add("is-open");
            launcher.setAttribute("aria-label", "Close quick chat");
        }
    }

    // ---- INIT --------------------------------------------------------
    function init() {
        const { launcher, panel } = buildWidget();

        restoreState(launcher, panel);

        launcher.addEventListener("click", function () {
            togglePanel(launcher, panel);
        });

        panel.querySelector(".tdt-close-btn").addEventListener("click", function () {
            togglePanel(launcher, panel);
        });

        const sendBtn = document.getElementById("tdtChatSend");
        const input = document.getElementById("tdtChatInput");

        sendBtn.addEventListener("click", handleUserSend);
        input.addEventListener("input", function () {
            autosizeInput(input);
        });
        input.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                handleUserSend();
            }
        });

        // Auto-open chat panel immediately so client sees it without clicking
        if (!panelOpen) {
            setTimeout(function () {
                if (!panelOpen) togglePanel(launcher, panel);
            }, 600);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
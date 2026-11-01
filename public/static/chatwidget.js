/* =========================================
   TDT QUICK CHAT WIDGET — LOGIC
   -----------------------------------------
   DURABLE BEHAVIOR (Task 4):
   1) Token persists in localStorage across refresh / tab restart.
   2) Server history is authoritative; the client keeps only small
      UI hints (mode / panelOpen / unread). There is no client
      transcript cache.
   3) On init, restore only when a token exists; render the complete
      server history, derive polling from the greatest rendered
      server message ID, then poll incrementally.
   4) Creation tokens returned by the server are stored; the client
      never invents tokens and never auto-creates a replacement
      session after a terminal state.
   5) Invalid/expired resets to fresh state; terminated renders the
      returned history read-only for the current page, stops
      polling, then clears namespaced keys so the next init is fresh.
   6) Message text is rendered with textContent only (no HTML).
   ========================================= */

export const CONTRACT_VERSION = 1;
export const CHAT_API_ENDPOINT = "/api/chat/messages";
export const POLL_INTERVAL_MS = 4000;
export const CHAT_KEY_PREFIX = "tdt.quickchat.v1.";
export const MAX_UNREAD_COUNT = 99;

export const CHAT_KEYS = {
    session: CHAT_KEY_PREFIX + "session",
    mode: CHAT_KEY_PREFIX + "mode",
    panelOpen: CHAT_KEY_PREFIX + "panelOpen",
    unread: CHAT_KEY_PREFIX + "unread",
};

// ---- Pure helpers (testable; storage/DOM injected) ------------------------

export function getStoredToken(storage) {
    try {
        if (!storage || typeof storage.getItem !== "function") return null;
        const raw = storage.getItem(CHAT_KEYS.session);
        return typeof raw === "string" && raw.length > 0 ? raw : null;
    } catch (err) {
        return null;
    }
}

export function setStoredToken(storage, token) {
    try {
        if (!storage || typeof storage.setItem !== "function") return;
        if (typeof token === "string" && token.length > 0) {
            storage.setItem(CHAT_KEYS.session, token);
        } else if (typeof storage.removeItem === "function") {
            storage.removeItem(CHAT_KEYS.session);
        }
    } catch (err) { /* private-mode: persistence best-effort only */ }
}

export function shouldRestore(token) {
    return typeof token === "string" && token.length > 0;
}

export function buildRestoreRequest(token) {
    if (!shouldRestore(token)) return null;
    return {
        contractVersion: CONTRACT_VERSION,
        operation: "restore",
        sessionId: token,
    };
}

export function buildPollRequest(token, after) {
    if (!shouldRestore(token)) return null;
    let n = Number(after);
    if (!Number.isInteger(n) || n < 0) n = 0;
    if (n > 2147483647) n = 2147483647;
    return {
        contractVersion: CONTRACT_VERSION,
        operation: "poll",
        sessionId: token,
        after: n,
    };
}

export function buildMessageRequest(token, text, page) {
    const base = {
        contractVersion: CONTRACT_VERSION,
        operation: "message",
        text: String(text ?? ""),
        page: String(page ?? "home"),
    };
    if (shouldRestore(token)) {
        return { ...base, sessionId: token };
    }
    return base;
}

export function buildHandoffRequest(token, page) {
    const base = {
        contractVersion: CONTRACT_VERSION,
        operation: "handoff_requested",
        page: String(page ?? "home"),
    };
    if (shouldRestore(token)) {
        return { ...base, sessionId: token };
    }
    return base;
}

export function greatestMessageId(messages) {
    if (!Array.isArray(messages)) return 0;
    let max = 0;
    for (const m of messages) {
        const id = Number(m && m.id);
        if (Number.isInteger(id) && id > max) max = id;
    }
    return max;
}

/** Remove only namespaced UI-hint keys; unrelated storage must survive. */
export function clearChatKeys(storage) {
    if (!storage) return [];
    const removed = [];
    try {
        for (const k of Object.values(CHAT_KEYS)) {
            try {
                if (typeof storage.getItem === "function" && storage.getItem(k) !== null) {
                    if (typeof storage.removeItem === "function") storage.removeItem(k);
                    removed.push(k);
                }
            } catch (err) { /* ignore per-key */ }
        }
        if (typeof storage.length === "number" && typeof storage.key === "function") {
            const snapshot = [];
            for (let i = 0; i < storage.length; i += 1) {
                try {
                    const k = storage.key(i);
                    if (k != null) snapshot.push(k);
                } catch (err) { /* ignore */ }
            }
            for (const k of snapshot) {
                if (typeof k === "string" && k.startsWith(CHAT_KEY_PREFIX) && !removed.includes(k)) {
                    try {
                        if (typeof storage.removeItem === "function") storage.removeItem(k);
                        removed.push(k);
                    } catch (err) { /* ignore */ }
                }
            }
        }
    } catch (err) { /* best-effort cleanup */ }
    return removed;
}

/**
 * Reduce a server response to a UI decision. Pure: never touches storage,
 * never mints tokens, never performs network I/O.
 */
export function reduceChatResponse(response) {
    const status = response && typeof response.status === "string" ? response.status : "invalid";
    const messages = response && Array.isArray(response.messages) ? response.messages : [];
    if (status === "active") {
        return { kind: "active", messages, clearKeys: false, stopPolling: false };
    }
    if (status === "terminated") {
        return {
            kind: "terminated",
            messages,
            readOnly: true,
            disableInput: true,
            stopPolling: true,
            clearKeys: true,
        };
    }
    return { kind: "fresh", messages: [], clearKeys: true, stopPolling: true };
}

export function toDisplayFrom(sender) {
    if (sender === "staff") return "agent";
    if (sender === "client") return "user";
    return "bot";
}

/** Build one message row with textContent only. Never uses innerHTML. */
export function createMessageRow(doc, message) {
    const from = toDisplayFrom(message && message.sender);
    const text = message && message.text != null ? String(message.text) : "";
    const label = message && message.agentName != null ? String(message.agentName) : "Agent";

    const row = doc.createElement("div");
    row.className = "tdt-msg-row tdt-from-" + from;

    const bubble = doc.createElement("div");
    bubble.className = "tdt-bubble";

    if (from === "agent") {
        const labelEl = doc.createElement("span");
        labelEl.className = "tdt-sender-label";
        labelEl.textContent = label;
        bubble.appendChild(labelEl);
    }

    const textNode = doc.createElement("span");
    textNode.textContent = text;
    bubble.appendChild(textNode);

    row.appendChild(bubble);
    return row;
}

/** Append server rows to a container with textContent only. Returns max id. */
export function renderServerMessages(doc, container, messages) {
    const rows = Array.isArray(messages) ? messages : [];
    for (const m of rows) {
        container.appendChild(createMessageRow(doc, m));
    }
    return greatestMessageId(rows);
}

/** Disable inputs for a terminated session (current page stays read-only). */
export function setTerminatedReadOnly({ textarea, sendButton, chipsContainer } = {}) {
    if (textarea) {
        try {
            textarea.disabled = true;
            if (typeof textarea.setAttribute === "function") textarea.setAttribute("aria-disabled", "true");
        } catch (err) { /* ignore */ }
    }
    if (sendButton) {
        try {
            sendButton.disabled = true;
            if (typeof sendButton.setAttribute === "function") sendButton.setAttribute("aria-disabled", "true");
        } catch (err) { /* ignore */ }
    }
    if (chipsContainer) {
        try {
            if (Array.isArray(chipsContainer.children)) {
                for (const child of chipsContainer.children) {
                    try { child.disabled = true; } catch (err) { /* ignore */ }
                }
            } else if (typeof chipsContainer.querySelectorAll === "function") {
                for (const child of chipsContainer.querySelectorAll("button")) {
                    try { child.disabled = true; } catch (err) { /* ignore */ }
                }
            }
            if (typeof chipsContainer.setAttribute === "function") chipsContainer.setAttribute("data-disabled", "true");
        } catch (err) { /* ignore */ }
    }
}

// ---- Browser widget (guarded so Node tests import helpers only) -----------

const isBrowser = typeof document !== "undefined" && typeof window !== "undefined";

if (isBrowser) {
    const BOT_MENU = [
        {
            id: "products",
            label: "Our Steel Products",
            reply: "We carry 1,000+ steel products — deformed bars, structural beams, plates, pipes, and more, all PNS-certified. What specific product do you need?"
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
    const TERMINATED_MSG = "This chat has ended. Thanks for reaching out to TDT Powersteel — start a new message anytime and we'll pick it up from there.";
    const EXPIRED_MSG = "Your previous chat session expired, so we cleared it here. Send a new message and we'll pick it up from there.";

    let mode = "bot";
    let panelOpen = false;
    let maxId = 0;
    let pollTimer = null;
    let pollInFlight = false;
    let terminatedThisPage = false;
    let hasGreetedThisPage = false;

    function safeStorage() {
        try {
            return window.localStorage || null;
        } catch (err) {
            return null;
        }
    }

    function currentPage() {
        try {
            return (window.location.pathname || "").replace(/^\/|\.html$/g, "") || "home";
        } catch (err) {
            return "home";
        }
    }

    function getToken() {
        return getStoredToken(safeStorage());
    }

    function persistMode(newMode) {
        mode = newMode;
        try {
            const s = safeStorage();
            if (s) s.setItem(CHAT_KEYS.mode, newMode);
        } catch (err) { /* ignore */ }
    }

    function persistPanelOpen(isOpen) {
        try {
            const s = safeStorage();
            if (s) s.setItem(CHAT_KEYS.panelOpen, isOpen ? "1" : "0");
        } catch (err) { /* ignore */ }
    }

    function getUnreadCount() {
        try {
            const s = safeStorage();
            const raw = s ? s.getItem(CHAT_KEYS.unread) : null;
            const n = parseInt(raw || "0", 10);
            return Number.isInteger(n) && n > 0 ? Math.min(n, MAX_UNREAD_COUNT) : 0;
        } catch (err) {
            return 0;
        }
    }

    function updateBadge(count) {
        const badge = document.querySelector(".tdt-chat-launcher .tdt-badge");
        if (!badge) return;
        const n = Math.max(0, Math.min(Number(count) || 0, MAX_UNREAD_COUNT));
        if (n > 0) {
            badge.textContent = n > 9 ? "9+" : String(n);
            badge.style.display = "flex";
        } else {
            badge.textContent = "";
            badge.style.display = "none";
        }
    }

    function setUnreadCount(n) {
        const capped = Math.max(0, Math.min(Number(n) || 0, MAX_UNREAD_COUNT));
        try {
            const s = safeStorage();
            if (s) s.setItem(CHAT_KEYS.unread, String(capped));
        } catch (err) { /* ignore */ }
        updateBadge(capped);
    }

    function incUnread(delta) {
        setUnreadCount(getUnreadCount() + (Number(delta) || 0));
    }

    function clearUnread() {
        setUnreadCount(0);
    }

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

    /** Local UI-hint row only (bot greetings/FAQ/errors). Never persisted. */
    function addHintMessage(body, from, text, label) {
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
        textNode.textContent = String(text ?? "");
        bubble.appendChild(textNode);

        row.appendChild(bubble);
        body.appendChild(row);
        scrollToBottom(body);
        return row;
    }

    function appendNewServerRows(body, messages) {
        const fresh = (Array.isArray(messages) ? messages : []).filter((m) => Number(m && m.id) > maxId);
        if (fresh.length) {
            renderServerMessages(document, body, fresh);
            maxId = Math.max(maxId, greatestMessageId(fresh));
            scrollToBottom(body);
        }
        return fresh;
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
        if (mode !== "bot" || terminatedThisPage) return;
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

    function setInputsEnabled(enabled) {
        const input = document.getElementById("tdtChatInput");
        const sendBtn = document.getElementById("tdtChatSend");
        if (input) {
            input.disabled = !enabled;
            if (!enabled) input.setAttribute("aria-disabled", "true");
            else input.removeAttribute("aria-disabled");
        }
        if (sendBtn) {
            sendBtn.disabled = !enabled;
            if (!enabled) sendBtn.setAttribute("aria-disabled", "true");
            else sendBtn.removeAttribute("aria-disabled");
        }
    }

    async function postChat(payload) {
        try {
            const res = await fetch(CHAT_API_ENDPOINT, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            let data = null;
            try {
                data = await res.json();
            } catch (err) {
                data = null;
            }
            return { httpOk: res.ok, httpStatus: res.status, data };
        } catch (err) {
            return { networkError: true };
        }
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        pollInFlight = false;
    }

    function applyTerminal(body, quickReplies, kind, messages) {
        stopPolling();
        if (kind === "terminated") {
            terminatedThisPage = true;
            appendNewServerRows(body, messages);
            const input = document.getElementById("tdtChatInput");
            const sendBtn = document.getElementById("tdtChatSend");
            setTerminatedReadOnly({ textarea: input, sendButton: sendBtn, chipsContainer: quickReplies });
            setInputsEnabled(false);
            if (quickReplies) quickReplies.innerHTML = "";
            updateHeaderStatus("Chat ended", false);
            addHintMessage(body, "bot", TERMINATED_MSG);
            clearChatKeys(safeStorage());
            return;
        }
        // invalid / expired: fresh state, no replacement session is created here.
        terminatedThisPage = false;
        clearChatKeys(safeStorage());
        maxId = 0;
        persistMode("bot");
        setInputsEnabled(true);
        updateHeaderStatus("Assistant online", false);
        if (body) body.innerHTML = "";
        hasGreetedThisPage = false;
        if (quickReplies) renderQuickReplies(quickReplies, false);
        if (kind === "expired") {
            addHintMessage(body, "bot", EXPIRED_MSG);
        }
    }

    function applyActive(body, quickReplies, messages) {
        const fresh = appendNewServerRows(body, messages);
        const hasStaff = (Array.isArray(messages) ? messages : []).some((m) => m && m.sender === "staff");
        if (hasStaff) {
            updateHeaderStatus("Chatting with an agent", true);
        }
        if (fresh.some((m) => m && m.sender === "staff") && !panelOpen) {
            incUnread(fresh.filter((m) => m && m.sender === "staff").length);
        }
        return fresh;
    }

    function startPolling(body) {
        if (pollTimer || terminatedThisPage) return;
        pollTimer = setInterval(async function () {
            if (terminatedThisPage || pollInFlight) return;
            const token = getToken();
            const req = buildPollRequest(token, maxId);
            if (!req) {
                stopPolling();
                return;
            }
            pollInFlight = true;
            try {
                // Token travels in the POST body only, never in the URL.
                const res = await postChat(req);
                if (res.networkError || !res.data) return;
                const reduced = reduceChatResponse(res.data);
                const quickReplies = document.getElementById("tdtQuickReplies");
                if (reduced.kind === "active") {
                    applyActive(body, quickReplies, reduced.messages);
                } else {
                    applyTerminal(body, quickReplies, reduced.kind, reduced.messages);
                }
            } catch (err) {
                /* polling is best-effort */
            } finally {
                pollInFlight = false;
            }
        }, POLL_INTERVAL_MS);
    }

    function handleMenuPick(item) {
        if (terminatedThisPage) return;
        const body = document.getElementById("tdtChatBody");
        const quickReplies = document.getElementById("tdtQuickReplies");

        addHintMessage(body, "user", item.label);

        if (item.id === "human") {
            switchToHumanMode(body, quickReplies);
            return;
        }

        const typingRow = showTyping(body);
        setTimeout(function () {
            typingRow.remove();
            addHintMessage(body, "bot", item.reply);
        }, 500);
    }

    async function switchToHumanMode(body, quickReplies) {
        if (terminatedThisPage) return;
        persistMode("human");
        if (quickReplies) quickReplies.innerHTML = "";
        updateHeaderStatus("Waiting for an agent", true);

        const typingRow = showTyping(body);
        const req = buildHandoffRequest(getToken(), currentPage());
        const res = await postChat(req);
        typingRow.remove();

        if (res.networkError || !res.data) {
            addHintMessage(body, "bot", HUMAN_HANDOFF_MSG);
            startPolling(body);
            return;
        }

        const reduced = reduceChatResponse(res.data);
        if (reduced.kind === "active") {
            if (res.data && typeof res.data.sessionId === "string" && res.data.sessionId.length > 0) {
                setStoredToken(safeStorage(), res.data.sessionId);
            }
            addHintMessage(body, "bot", HUMAN_HANDOFF_MSG);
            applyActive(body, quickReplies, reduced.messages);
            startPolling(body);
            return;
        }

        applyTerminal(body, quickReplies, reduced.kind, reduced.messages);
    }

    async function handleUserSend() {
        if (terminatedThisPage) return;
        const input = document.getElementById("tdtChatInput");
        const body = document.getElementById("tdtChatBody");
        const quickReplies = document.getElementById("tdtQuickReplies");
        const text = (input.value || "").trim();
        if (!text) return;

        input.value = "";
        autosizeInput(input);

        // Server history stays authoritative: render the server echo (not an
        // optimistic transcript) once the write is committed.
        const req = buildMessageRequest(getToken(), text, currentPage());
        const res = await postChat(req);

        if (res.networkError || !res.data) {
            addHintMessage(body, "bot", "Sorry — we couldn't send that just now. Please check your connection and try again.");
            return;
        }

        const reduced = reduceChatResponse(res.data);
        if (reduced.kind === "active") {
            if (res.data && typeof res.data.sessionId === "string" && res.data.sessionId.length > 0) {
                setStoredToken(safeStorage(), res.data.sessionId);
            }
            applyActive(body, quickReplies, reduced.messages);
            startPolling(body);
            if (mode === "human") {
                return;
            }
            const typingRow = showTyping(body);
            setTimeout(function () {
                if (terminatedThisPage) {
                    typingRow.remove();
                    return;
                }
                typingRow.remove();
                addHintMessage(
                    body,
                    "bot",
                    "I'm sorry, I can't read free-form questions yet. Please choose a topic below, or tap 'Talk to a human agent' to speak with our team."
                );
            }, 500);
            return;
        }

        applyTerminal(body, quickReplies, reduced.kind, reduced.messages);
    }

    function autosizeInput(input) {
        input.style.height = "auto";
        input.style.height = Math.min(input.scrollHeight, 70) + "px";
    }

    function togglePanel(launcher, panel) {
        if (terminatedThisPage && !panelOpen) {
            // Terminated sessions stay read-only; still allow viewing history.
        }
        panelOpen = !panelOpen;
        launcher.classList.toggle("is-open", panelOpen);
        panel.classList.toggle("is-open", panelOpen);
        launcher.setAttribute("aria-label", panelOpen ? "Close quick chat" : "Open quick chat");
        persistPanelOpen(panelOpen);
        if (panelOpen) {
            clearUnread();
        }

        if (panelOpen && !hasGreetedThisPage && maxId === 0 && !getToken()) {
            hasGreetedThisPage = true;
            const body = document.getElementById("tdtChatBody");
            const quickReplies = document.getElementById("tdtQuickReplies");
            const typingRow = showTyping(body);
            setTimeout(function () {
                typingRow.remove();
                addHintMessage(body, "bot", GREETING);
                renderQuickReplies(quickReplies, false);
            }, 400);
        }
    }

    async function restoreFromServer(body, quickReplies) {
        const token = getToken();
        if (!shouldRestore(token)) {
            maxId = 0;
            return;
        }
        const req = buildRestoreRequest(token);
        const res = await postChat(req);
        if (res.networkError || !res.data) {
            // Keep the token; a transient failure must not wipe durable state.
            return;
        }
        const reduced = reduceChatResponse(res.data);
        if (reduced.kind === "active") {
            renderServerMessages(document, body, reduced.messages);
            maxId = greatestMessageId(reduced.messages);
            scrollToBottom(body);
            const hasStaff = reduced.messages.some((m) => m && m.sender === "staff");
            updateHeaderStatus(hasStaff ? "Chatting with an agent" : "Waiting for an agent", hasStaff || mode === "human");
            if (mode === "bot") {
                renderQuickReplies(quickReplies, false);
            }
            startPolling(body);
            return;
        }
        applyTerminal(body, quickReplies, reduced.kind, reduced.messages);
    }

    function restoreUiHints(launcher, panel) {
        try {
            const s = safeStorage();
            const savedMode = s ? s.getItem(CHAT_KEYS.mode) : null;
            mode = savedMode === "human" ? "human" : "bot";
            const wasPanelOpen = s ? s.getItem(CHAT_KEYS.panelOpen) === "1" : false;
            if (wasPanelOpen) {
                panelOpen = true;
                launcher.classList.add("is-open");
                panel.classList.add("is-open");
                launcher.setAttribute("aria-label", "Close quick chat");
                clearUnread();
            } else {
                updateBadge(getUnreadCount());
            }
            if (mode === "human") {
                updateHeaderStatus("Waiting for an agent", true);
            }
        } catch (err) { /* hints are optional */ }
    }

    function init() {
        const { launcher, panel } = buildWidget();

        restoreUiHints(launcher, panel);
        restoreFromServer(
            document.getElementById("tdtChatBody"),
            document.getElementById("tdtQuickReplies")
        );

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

        // Auto-open only on the home page when there is no durable session
        // and the panel has never been opened (bounded hint keys only).
        const isHomePage = window.location.pathname === "/" || window.location.pathname === "/index" || window.location.pathname === "";
        let panelHint = null;
        try {
            const s = safeStorage();
            panelHint = s ? s.getItem(CHAT_KEYS.panelOpen) : null;
        } catch (err) {
            panelHint = null;
        }
        const isFirstEntry = !getToken() && panelHint === null;
        if (!panelOpen && isHomePage && isFirstEntry) {
            setTimeout(function () {
                if (!panelOpen) togglePanel(launcher, panel);
            }, 700);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
}

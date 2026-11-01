import test from 'node:test';
import assert from 'node:assert/strict';

import * as widget from '../../public/static/chatwidget.js';

// ---- Fakes ---------------------------------------------------------------

class FakeStorage {
  constructor(initial = {}) {
    this._store = { ...initial };
  }
  getItem(k) {
    return Object.prototype.hasOwnProperty.call(this._store, k) ? this._store[k] : null;
  }
  setItem(k, v) {
    this._store[k] = String(v);
  }
  removeItem(k) {
    delete this._store[k];
  }
  clear() {
    this._store = {};
  }
  get length() {
    return Object.keys(this._store).length;
  }
  key(i) {
    return Object.keys(this._store)[i] ?? null;
  }
}

class FakeEl {
  constructor(tag) {
    this.tagName = tag;
    this.children = [];
    this._text = '';
    this.disabled = false;
    this.attributes = {};
    this._innerHTML = '';
    this.innerHTMLUsed = false;
    this.className = '';
  }
  set textContent(v) {
    this._text = String(v);
  }
  get textContent() {
    return this._text;
  }
  set innerHTML(v) {
    this.innerHTMLUsed = true;
    this._innerHTML = String(v);
  }
  get innerHTML() {
    return this._innerHTML;
  }
  appendChild(c) {
    this.children.push(c);
    return c;
  }
  setAttribute(k, v) {
    this.attributes[k] = String(v);
  }
  collectText() {
    let out = [this._text];
    for (const c of this.children) {
      if (typeof c.collectText === 'function') out.push(c.collectText());
      else if (typeof c.textContent === 'string') out.push(c.textContent);
    }
    return out.join('|');
  }
}

class FakeDoc {
  constructor() {
    this.created = [];
  }
  createElement(tag) {
    const el = new FakeEl(tag);
    this.created.push(el);
    return el;
  }
}

const TOKEN = 'opaque-token-abc123';

// 1. Refresh / tab restart retains token (durable localStorage, not sessionStorage).
test('refresh retains token: stored token survives simulated reload', () => {
  const storage = new FakeStorage();
  widget.setStoredToken(storage, TOKEN);
  // Simulate refresh: new accessor over the same underlying storage.
  const reloaded = widget.getStoredToken(storage);
  assert.equal(reloaded, TOKEN);
  assert.equal(widget.shouldRestore(reloaded), true);
  const req = widget.buildRestoreRequest(reloaded);
  assert.deepEqual(req, {
    contractVersion: 1,
    operation: 'restore',
    sessionId: TOKEN,
  });
});

// 2. Transcript deletion still restores server rows (server authoritative).
test('transcript deletion still restores server rows from token only', () => {
  const storage = new FakeStorage();
  widget.setStoredToken(storage, TOKEN);
  // No transcript key exists at all; restore depends only on the token.
  const req = widget.buildRestoreRequest(widget.getStoredToken(storage));
  assert.ok(req);
  assert.equal(req.sessionId, TOKEN);
  assert.ok(!('type' in req));
  assert.ok(!('poll' in req));

  const serverRows = [
    { id: 3, sender: 'client', text: 'hello', agentName: null },
    { id: 7, sender: 'staff', text: 'hi there', agentName: 'Agent' },
  ];
  const reduced = widget.reduceChatResponse({ status: 'active', messages: serverRows });
  assert.equal(reduced.kind, 'active');
  assert.deepEqual(reduced.messages, serverRows);
  assert.equal(widget.greatestMessageId(serverRows), 7);

  const doc = new FakeDoc();
  const body = new FakeEl('div');
  const maxId = widget.renderServerMessages(doc, body, serverRows);
  assert.equal(maxId, 7);
  const renderedText = body.collectText();
  assert.ok(renderedText.includes('hello'));
  assert.ok(renderedText.includes('hi there'));
});

// 3. Absent token performs no restore/create and invents nothing.
test('absent token performs no restore/create', () => {
  const storage = new FakeStorage();
  assert.equal(widget.getStoredToken(storage), null);
  assert.equal(widget.shouldRestore(null), false);
  assert.equal(widget.shouldRestore(''), false);
  assert.equal(widget.buildRestoreRequest(null), null);
  assert.equal(widget.buildPollRequest(null, 0), null);
  assert.equal(widget.buildRestoreRequest(''), null);
  // No client token invention helper may exist.
  assert.equal(widget.inventToken, undefined);
  assert.equal(widget.getSessionId, undefined);
  assert.equal(widget.rotateSessionId, undefined);
});

// 4. Terminal responses clear only namespaced keys.
test('terminal cleanup clears only tdt.quickchat.v1.* keys', () => {
  const storage = new FakeStorage({
    [widget.CHAT_KEYS.session]: TOKEN,
    [widget.CHAT_KEYS.mode]: 'human',
    [widget.CHAT_KEYS.panelOpen]: '1',
    [widget.CHAT_KEYS.unread]: '2',
    unrelated_key: 'keep-me',
    tdt_chat_session_id: 'legacy-keep-or-remove-does-not-matter',
  });
  // Legacy unprefixed key is NOT namespaced; it must survive namespaced cleanup.
  // (The new widget never writes it.)
  widget.clearChatKeys(storage);
  assert.equal(storage.getItem(widget.CHAT_KEYS.session), null);
  assert.equal(storage.getItem(widget.CHAT_KEYS.mode), null);
  assert.equal(storage.getItem(widget.CHAT_KEYS.panelOpen), null);
  assert.equal(storage.getItem(widget.CHAT_KEYS.unread), null);
  assert.equal(storage.getItem('unrelated_key'), 'keep-me');
  assert.equal(storage.getItem('tdt_chat_session_id'), 'legacy-keep-or-remove-does-not-matter');
});

// 5. Termination renders final history read-only for the current page.
test('termination renders final history read-only (textContent only, disabled)', () => {
  const serverHistory = [
    { id: 1, sender: 'client', text: '<script>alert(1)</script>', agentName: null },
    { id: 2, sender: 'staff', text: '<img src=x onerror=alert(2)> bye', agentName: 'Rep' },
  ];
  const reduced = widget.reduceChatResponse({ status: 'terminated', messages: serverHistory });
  assert.equal(reduced.kind, 'terminated');
  assert.deepEqual(reduced.messages, serverHistory);
  assert.equal(reduced.readOnly, true);
  assert.equal(reduced.disableInput, true);
  assert.equal(reduced.stopPolling, true);
  assert.equal(reduced.clearKeys, true);

  const doc = new FakeDoc();
  const body = new FakeEl('div');
  const maxId = widget.renderServerMessages(doc, body, reduced.messages);
  assert.equal(maxId, 2);
  // textContent only: no element may have used innerHTML for message text.
  for (const el of doc.created) {
    assert.equal(el.innerHTMLUsed, false);
  }
  const renderedText = body.collectText();
  assert.ok(renderedText.includes('<script>alert(1)</script>'));
  assert.ok(renderedText.includes('<img src=x onerror=alert(2)> bye'));

  const textarea = new FakeEl('textarea');
  const sendBtn = new FakeEl('button');
  const chips = new FakeEl('div');
  widget.setTerminatedReadOnly({ textarea, sendButton: sendBtn, chipsContainer: chips });
  assert.equal(textarea.disabled, true);
  assert.equal(sendBtn.disabled, true);
});

// 6. Next init after termination is fresh with no automatic replacement session.
test('next init after termination is fresh with no auto replacement session', () => {
  const storage = new FakeStorage({
    [widget.CHAT_KEYS.session]: TOKEN,
    [widget.CHAT_KEYS.mode]: 'human',
  });
  const reduced = widget.reduceChatResponse({ status: 'terminated', messages: [{ id: 1, sender: 'staff', text: 'bye', agentName: 'Rep' }] });
  assert.equal(reduced.kind, 'terminated');
  // Termination clears keys; it must not mint a replacement token.
  assert.ok(!('sessionId' in reduced));
  assert.ok(!('token' in reduced));
  widget.clearChatKeys(storage);
  // Next init: no token -> no restore, no create.
  assert.equal(widget.getStoredToken(storage), null);
  assert.equal(widget.shouldRestore(widget.getStoredToken(storage)), false);
  assert.equal(widget.buildRestoreRequest(widget.getStoredToken(storage)), null);
});

// 7. Invalid/expired resets to fresh (no read-only, no replacement session).
test('invalid/expired responses reset to fresh state', () => {
  for (const status of ['invalid', 'expired']) {
    const reduced = widget.reduceChatResponse({ status, messages: [] });
    assert.equal(reduced.kind, 'fresh', `status=${status}`);
    assert.equal(reduced.clearKeys, true);
    assert.equal(reduced.stopPolling, true);
    assert.equal(reduced.disableInput ?? false, false);
    assert.ok(!('sessionId' in reduced));
  }
  const reducedMissing = widget.reduceChatResponse(null);
  assert.equal(reducedMissing.kind, 'fresh');
});

// 8. Poll derives from greatest rendered server message ID across both senders.
test('poll uses greatest rendered server message id across senders', () => {
  const rows = [
    { id: 10, sender: 'client', text: 'a', agentName: null },
    { id: 4, sender: 'staff', text: 'b', agentName: 'Agent' },
    { id: 12, sender: 'client', text: 'c', agentName: null },
  ];
  assert.equal(widget.greatestMessageId(rows), 12);
  assert.equal(widget.greatestMessageId([]), 0);
  const req = widget.buildPollRequest(TOKEN, widget.greatestMessageId(rows));
  assert.deepEqual(req, {
    contractVersion: 1,
    operation: 'poll',
    sessionId: TOKEN,
    after: 12,
  });
});

// 9. Request builders use the versioned contract only (no legacy fields, body-only).
test('request builders use contractVersion 1 with no legacy fields', () => {
  const restore = widget.buildRestoreRequest(TOKEN);
  assert.equal(restore.contractVersion, 1);
  assert.ok(!('type' in restore));
  assert.ok(!('poll' in restore));
  assert.ok(!('newSessionId' in restore));
  assert.ok(!('after' in restore));

  const poll = widget.buildPollRequest(TOKEN, 5);
  assert.equal(poll.contractVersion, 1);
  assert.equal(poll.after, 5);
  assert.ok(!('type' in poll));

  const msg = widget.buildMessageRequest(TOKEN, 'hi', 'home');
  assert.deepEqual(msg, { contractVersion: 1, operation: 'message', sessionId: TOKEN, text: 'hi', page: 'home' });

  const msgFirst = widget.buildMessageRequest(null, 'hi', 'home');
  assert.deepEqual(msgFirst, { contractVersion: 1, operation: 'message', text: 'hi', page: 'home' });
  assert.ok(!('sessionId' in msgFirst));

  const hand = widget.buildHandoffRequest(TOKEN, 'home');
  assert.deepEqual(hand, { contractVersion: 1, operation: 'handoff_requested', sessionId: TOKEN, page: 'home' });

  const handFirst = widget.buildHandoffRequest(null, 'home');
  assert.deepEqual(handFirst, { contractVersion: 1, operation: 'handoff_requested', page: 'home' });
});

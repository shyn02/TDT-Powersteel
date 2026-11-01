# Persistent Quick Chat Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make quick chat server-authoritative and recoverable for 30 rolling days in the same browser/origin, with explicit invalid/expired/terminated behavior and authorized termination.

**Architecture:** Keep the opaque 256-bit token HMAC-hashed in `chat_sessions.session_token`; only the token/UI hints persist in namespaced `localStorage`. A versioned POST command contract restores complete server history, polls incrementally, and writes transactionally while lifecycle predicates prevent expiry extension by reads and block terminal sessions.

**Tech Stack:** Laravel 13, Eloquent/MySQL/SQLite, Filament 5.7/Livewire, PHPUnit 12, browser Fetch/localStorage, vanilla JavaScript

**Spec:** `docs/superpowers/specs/2026-09-18-site-stabilization-design.md`

## Global Constraints

- Depends on Upload Plan Task 1; may run in parallel with upload, quote/export, and projects afterward.
- Contract is always `POST /api/chat/messages` with `contractVersion: 1`; token appears only in POST JSON, never URL/log context.
- First accepted `message`/`handoff_requested` creates a server 256-bit token; restore/poll never creates.
- Activity advances only after a committed guest/staff message or handoff; expiry is exactly activity plus 30 days and active iff `now() < expires_at`.
- State precedence: terminated, then expired/revoked, then active; `is_active`/assignment status cannot override it.
- Every response has `Cache-Control: no-store`; render messages with `textContent`.
- Preserve existing policy authorization and HMAC-at-rest design; do not add account/cross-device identity or a queue worker.

## File Map

| Path | Responsibility |
|---|---|
| `database/migrations/2026_09_18_000002_add_chat_lifecycle_fields.php` | lifecycle fields/indexes/FK/backfill. |
| `app/Models/ChatSession.php` | casts, terminator relation, lifecycle predicates. |
| `app/Actions/Chat/ResolveGuestChat.php` | token lookup and terminal response classification. |
| `app/Actions/Chat/WriteChatMessage.php` | locked atomic message/activity/expiry write. |
| `app/Http/Requests/ChatMessageRequest.php` | exact discriminated version-1 validation. |
| `app/Http/Controllers/ChatController.php` | restore/poll/write response contract. |
| `app/Filament/Admin/Resources/ChatSessions/Pages/ViewChatSession.php` | staff write lifecycle and terminate action. |
| `app/Policies/ChatSessionPolicy.php` | explicit `terminate` authorization. |
| `public/static/chatwidget.js` | localStorage bootstrap, lifecycle UI, safe rendering. |
| `tests/Feature/ChatPersistenceTest.php` | API/time/termination/auth tests. |
| `tests/js/chatwidget.test.mjs` | browser-storage and terminal UI tests using Node built-in runner. |
| `package.json` | `test:chat` script (`node --test tests/js/chatwidget.test.mjs`). |

---

### Task 1: Add and backfill lifecycle schema

**Specialist:** database/backend engineer

**Depends on:** Upload Plan Task 1

**Interfaces:** Produces nullable `last_activity_at`, `terminated_at`, `terminated_by`; indexed expiry/termination; `terminated_by -> users.id nullOnDelete`.

**Files:**
- Create: `database/migrations/2026_09_18_000002_add_chat_lifecycle_fields.php`
- Modify: `app/Models/ChatSession.php`
- Create: `tests/Feature/ChatPersistenceTest.php`

- [ ] **Step 1: Write red migration/model tests** in `tests/Feature/ChatPersistenceTest.php`: create active, revoked, and soft-deleted sessions; run/backfill assertions that activity is `last_message_at ?? created_at`, expiry is exactly +30 days for non-terminated rows, revoked remains revoked/inactive, and terminator deletion nulls actor but retains timestamp.
- [ ] **Step 2: Run** `php artisan test --filter=ChatPersistenceTest` **Expected:** FAIL for missing columns.
- [ ] **Step 3: Create migration/model changes.** Add fields, indexes, FK; chunk backfill all non-terminated rows including trashed using DB expressions portable to SQLite/MySQL or PHP Carbon per row. Never clear `revoked_at`. Add casts/fillable, `terminator()`, `lifecycleStatus(CarbonInterface $now): string`, `isGuestWritable(...)` with specified precedence.
- [ ] **Step 4: Run** `php artisan test --filter=ChatPersistenceTest` **Expected:** PASS for schema/backfill/boundary.
- [ ] **Step 5: Review/commit checkpoint.** Database reviewer checks exact boundary and no reactivation. Proposed commit:
```powershell
git add database/migrations/2026_09_18_000002_add_chat_lifecycle_fields.php app/Models/ChatSession.php tests/Feature/ChatPersistenceTest.php
```

### Task 2: Implement the versioned server contract

**Specialist:** backend/security engineer

**Depends on:** Task 1

**Interfaces:**
- Consumes one of `{contractVersion:1,operation:"restore",sessionId}`, `{...,operation:"poll",sessionId,after}`, `{...,operation:"message",sessionId?:string,text,page}`, `{...,operation:"handoff_requested",sessionId?:string,page}`.
- Produces active message objects `{id:int,sender:"client"|"staff",text:string,agentName:?string,createdAt:ISO-8601}` and exact `CHAT_INVALID` 404 / `CHAT_EXPIRED` 410 / `CHAT_TERMINATED` 410 contracts.

**Files:**
- Create: `app/Actions/Chat/ResolveGuestChat.php`
- Create: `app/Actions/Chat/WriteChatMessage.php`
- Create: `app/Http/Requests/ChatMessageRequest.php`
- Modify: `app/Http/Controllers/ChatController.php`
- Modify: `tests/Feature/ChatPersistenceTest.php`

- [ ] **Step 1: Write red API tests.** Cover malformed/version/unknown operation 422 with no session; first write returns `sessionId`; restore full client+staff history; poll only IDs greater than `after`; invalid 404 empty; expired 410 empty at exact boundary; revoked invalid 404; terminated 410 retained full history; reads do not mutate; guest/handoff commits roll expiry +30 days; failed/terminal writes do not.
- [ ] **Step 2: Run** `php artisan test --filter=ChatPersistenceTest` **Expected:** FAIL against legacy `type/poll/newSessionId` contract.
- [ ] **Step 3: Implement request validation and resolver.** Use conditional rules per operation, prohibit `sessionId` only on first writes, max text 2000/page 150/token 128. Resolver hashes via HMAC SHA-256 with app key and never logs raw input. Response builder sets `contractVersion`, lifecycle fields, ISO timestamps, messages, and `no-store` on success/errors.
- [ ] **Step 4: Implement locked writes.** In a DB transaction resolve/create, `lockForUpdate`, re-evaluate lifecycle, create message, then set one captured `$activityAt`, `last_message_at`, `last_activity_at`, `expires_at=$activityAt->copy()->addDays(30)`, `is_active=true`; return token only on creation. Handoff message is `[Requested to speak with a live agent]`.
- [ ] **Step 5: Run** `php artisan test --filter=ChatPersistenceTest` **Expected:** PASS, including frozen-time edge `now == expires_at`.
- [ ] **Step 6: Security gate/commit.** Review token entropy, HMAC lookup, body-only transport, no-store, validation/XSS-neutral JSON, locking. Proposed commit:
```powershell
git add app/Actions/Chat app/Http/Requests/ChatMessageRequest.php app/Http/Controllers/ChatController.php tests/Feature/ChatPersistenceTest.php
```

### Task 3: Make staff replies roll activity and add idempotent termination

**Specialist:** Filament/backend engineer

**Depends on:** Task 2

**Interfaces:** Produces policy method `terminate(User $user, ChatSession $record): bool`; termination atomically sets `terminated_at`, `terminated_by`, `is_active=false`, `status=closed` and never deletes messages.

**Files:**
- Modify: `app/Filament/Admin/Resources/ChatSessions/Pages/ViewChatSession.php`
- Modify: `app/Policies/ChatSessionPolicy.php`
- Modify: `tests/Feature/ChatPersistenceTest.php`

- [ ] **Step 1: Write red tests** for authorized and unauthorized actors, repeat termination preserving original actor/time, retained history, blocked guest replay, and staff reply updating activity/expiry only after message commit. Assert viewing/claiming/termination itself does not extend expiry.
- [ ] **Step 2: Run** `php artisan test --filter=ChatPersistenceTest` **Expected:** FAIL for missing termination.
- [ ] **Step 3: Implement.** Route staff replies through `WriteChatMessage`; reject reply when lifecycle terminal. Add Filament danger action requiring confirmation, explicit `$this->authorize('terminate',$record)`, transaction/lock, first-write-wins audit via `ActivityLog`, success notification. The new policy method returns true only for an active admin-position user; do not broaden existing view/update rules.
- [ ] **Step 4: Run** `php artisan test --filter=ChatPersistenceTest` **Expected:** PASS.
- [ ] **Step 5: Authorization review/commit.** Proposed commit:
```powershell
git add app/Filament/Admin/Resources/ChatSessions/Pages/ViewChatSession.php app/Policies/ChatSessionPolicy.php tests/Feature/ChatPersistenceTest.php
```

### Task 4: Replace client transcript authority with durable token restore

**Specialist:** frontend engineer

**Depends on:** Task 2

**Interfaces:** Uses namespaced keys `tdt.quickchat.v1.session`, `.mode`, `.panelOpen`, `.unread`; greatest rendered server message ID drives poll.

**Files:**
- Modify: `public/static/chatwidget.js`
- Create: `tests/js/chatwidget.test.mjs`
- Modify: `package.json`

- [ ] **Step 1: Extract testable client helpers** from `chatwidget.js` as exported pure functions guarded for browser use: request builders, `clearChatKeys(storage)`, response reducer, and safe renderer. Write `tests/js/chatwidget.test.mjs` with fake storage/DOM proving refresh/tab restart retains token, transcript deletion still restores server rows, absent token performs no restore/create, terminal responses clear only namespaced keys, termination renders final history read-only for current page, next init is fresh, and no automatic replacement session.
- [ ] **Step 2: Run** `npm run test:chat` **Expected:** FAIL (or on Windows without Node, record `node is not recognized` and run in CI/Hostinger-equivalent Node environment before merge; this is a blocking environment prerequisite, not a skipped test).
- [ ] **Step 3: Implement client flow.** Remove reload/sessionStorage clearing and client token invention. On init restore only when token exists; render complete server history, set max ID, then poll. Store returned creation token in localStorage. Handle invalid/expired with fresh state; handle terminated by rendering returned history, disabling textarea/send/chips, stopping polling, then clearing keys. Use `textContent` only and bounded UI-hint cache, never authoritative transcript.
- [ ] **Step 4: Run** `npm run test:chat` **Expected:** PASS.
- [ ] **Step 5: Frontend security review/commit.** Confirm token absent from URL/console and unrelated localStorage survives cleanup. Proposed commit:
```powershell
git add public/static/chatwidget.js tests/js/chatwidget.test.mjs package.json
```

### Task 5: Integrated chat verification

**Specialist:** QA engineer

**Depends on:** Tasks 1-4 and quote plan route-throttle task

**Interfaces:** Consumes the final API/client/admin contract and produces signed chat acceptance evidence for the integrated rollout.

**Files:**
- Verify: `tests/Feature/ChatPersistenceTest.php`
- Verify: `tests/js/chatwidget.test.mjs`
- Record results in: `docs/operations/stabilization-release-checklist.md` (created by the integrated plan)

- [ ] Run `php artisan test --filter=ChatPersistenceTest; npm run test:chat; php artisan test` — expected all PASS.
- [ ] Manually start chat, refresh, close/reopen tab, restart browser, delete only cached display data, receive staff response, terminate, replay token/write, and reload. Expected: server history restores; terminated current page is read-only with final history; next load is fresh; no replacement until a new guest write.
- [ ] Verify six rapid chat POSTs are not 429, passive polls do not change expiry, and logs/URLs contain no raw token.
- [ ] Proposed commit for test documentation only: `git commit -m "test: verify persistent quick chat"` after staging only changed test/runbook files.

## Acceptance Criteria

- Exact v1 request/response/status contracts and no-store headers are enforced.
- Rolling 30-day expiry changes only on committed guest/staff message or handoff.
- Restore is complete, poll incremental, server history authoritative, and same-origin localStorage token survives restart.
- Invalid/expired/terminated states and cleanup/read-only behavior match the spec.
- Termination is authorized, audited, idempotent, history-preserving, and replay-safe.
- No token appears in URL/log context; rendering is text-only; full PHP and JS suites pass.

## Cross-Plan Handoff

- Route throttle removal is owned/tested by `2026-09-18-quote-throttling-export.md`.
- Final Hostinger/browser verification waits for all plans in `2026-09-18-integrated-verification-hostinger.md`.

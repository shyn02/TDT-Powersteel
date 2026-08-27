<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\SystemSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function messages(Request $request): JsonResponse
    {
        // SEC-03: Polling moved to POST body (poll=true) to avoid token in URL.
        // Detect poll by explicit poll flag; otherwise treat POST as postMessage.
        if ($request->boolean('poll')) {
            return $this->pollMessages($request);
        }
        if ($request->isMethod('post')) {
            // If payload contains 'after' without message fields, treat as poll for backward compat
            if ($request->has('after') && ! $request->filled('type') && ! $request->filled('text')) {
                return $this->pollMessages($request);
            }
            return $this->postMessage($request);
        }
        return $this->pollMessages($request);
    }

    protected function hashToken(string $raw): string
    {
        return hash_hmac('sha256', $raw, config('app.key'));
    }

    protected function findSessionByToken(string $raw): ?ChatSession
    {
        // SEC-02: Only HMAC-hashed lookup; raw fallback removed after migration window.
        $hash = $this->hashToken($raw);
        return ChatSession::where('session_token', $hash)->first();
    }

    protected function isSessionExpired(ChatSession $session): bool
    {
        if ($session->revoked_at) return true;
        if ($session->expires_at && $session->expires_at->isPast()) return true;
        return false;
    }

    protected function postMessage(Request $request): JsonResponse
    {
        $request->validate([
            'sessionId' => ['required', 'string', 'max:128'],
            'type' => ['nullable', 'string', 'in:message,handoff_requested', 'max:32'],
            'text' => ['nullable', 'string', 'max:2000'],
            'page' => ['nullable', 'string', 'max:150'],
        ]);

        $rawId = trim((string) $request->input('sessionId', ''));
        $type = (string) $request->input('type', '');
        $text = trim((string) $request->input('text', ''));
        $page = trim((string) $request->input('page', ''));

        if ($rawId === '') {
            return response()->json(['status' => 'error', 'message' => 'Missing sessionId'], 400);
        }

        // SEC-05/SEC-01: Normalize legacy low-entropy tokens to high-entropy server-generated
        $isLowEntropy = str_starts_with($rawId, 'sess_') || strlen($rawId) < 32 || ! preg_match('/^[a-f0-9]{32,128}$/i', $rawId);
        $needsRotation = false;
        $effectiveRaw = $rawId;
        if ($isLowEntropy) {
            $existing = $this->findSessionByToken($rawId);
            if (! $existing) {
                $needsRotation = true;
                $effectiveRaw = bin2hex(random_bytes(32));
            }
        }

        $hash = $this->hashToken($effectiveRaw);
        $session = ChatSession::firstOrCreate(
            ['session_token' => $hash],
            [
                'client_name' => $page !== '' ? "Visitor ({$page})" : 'Website Visitor',
                'page' => $page,
                'token_version' => 2,
                'expires_at' => now()->addHours(24),
            ]
        );

        // SEC-02: Enforce revoked and expiry (24h) on every write path
        if ($this->isSessionExpired($session)) {
            return response()->json(['status' => 'error', 'message' => 'Session expired'], 401)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        // If we rotated, ensure token hash is stored (firstOrCreate already did)
        if (! $session->wasRecentlyCreated && $page !== '' && ! $session->page) {
            $session->page = $page;
        }

        if ($type === 'handoff_requested') {
            ChatMessage::create([
                'session_id' => $session->id,
                'sender' => 'client',
                'message' => '[Requested to speak with a live agent]',
                'created_at' => now(),
            ]);
        } elseif ($type === 'message' && $text !== '') {
            ChatMessage::create([
                'session_id' => $session->id,
                'sender' => 'client',
                'message' => $text,
                'created_at' => now(),
            ]);
        }

        $session->is_active = true;
        $session->last_message_at = now();
        $session->save();

        $payload = ['status' => 'ok'];
        if ($needsRotation) {
            $payload['newSessionId'] = $effectiveRaw;
        }
        return response()->json($payload)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    protected function pollMessages(Request $request): JsonResponse
    {
        $request->validate([
            'sessionId' => ['required', 'string', 'max:128'],
            'after' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'poll' => ['nullable', 'boolean'],
        ]);
        // SEC-03: Prefer body/input over query string; still accept query for transition but no token in URL is preferred.
        // SEC-03 mitigation: reject tokens that arrive only via query string when method is GET after rollout.
        $rawId = trim((string) $request->input('sessionId', $request->query('sessionId', '')));
        $afterId = (int) $request->input('after', $request->query('after', 0));

        // Enforce Cache-Control: no-store on all poll responses (SEC-03)
        $noStore = ['Cache-Control' => 'no-store, no-cache, must-revalidate', 'Pragma' => 'no-cache'];

        $messages = [];
        $session = $rawId !== '' ? $this->findSessionByToken($rawId) : null;

        if ($session && $this->isSessionExpired($session)) {
            return response()->json(['status' => 'error', 'message' => 'Session expired'], 401)->withHeaders($noStore);
        }

        if ($session) {
            $rows = $session->messages()
                ->where('sender', 'staff')
                ->where('id', '>', $afterId)
                ->with('staffUser')
                ->orderBy('id')
                ->get();

            foreach ($rows as $m) {
                $messages[] = [
                    'id' => $m->id,
                    'text' => $m->message,
                    'agentName' => $m->staffUser?->name ?: 'Agent',
                ];
            }
        }

        return response()->json(['messages' => $messages])->withHeaders($noStore);
    }

    public function unassignedQueue(Request $request): JsonResponse
    {
        // Policy check via Gate (admin/sales/manager/support)
        $this->authorize('viewAny', ChatSession::class);

        $sessions = ChatSession::where('status', 'unassigned')
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get();

        $chats = $sessions->map(fn (ChatSession $session) => [
            'id' => $session->id,
            'clientName' => $session->client_name,
            'page' => $session->page,
            'createdAt' => $session->created_at?->toIso8601String(),
            'lastMessageAt' => $session->last_message_at?->toIso8601String(),
            'unreadCount' => $session->messages()->where('sender', 'client')->where('is_read', false)->count(),
        ]);

        return response()->json(['status' => 'ok', 'count' => $chats->count(), 'chats' => $chats]);
    }

    public function claim(Request $request, int $sessionId): JsonResponse
    {
        $rep = $request->user();

        // SEC-08: Authorize against concrete record, not class. Load first to verify existence.
        $preSession = ChatSession::find($sessionId);
        if (! $preSession) {
            return response()->json(['status' => 'error', 'message' => 'Chat not found.'], 404);
        }
        $this->authorize('update', $preSession);

        try {
            $result = DB::transaction(function () use ($sessionId, $rep) {
                $session = ChatSession::where('id', $sessionId)->lockForUpdate()->first();

                if (! $session) {
                    return ['error' => ['message' => 'Chat not found.'], 'status' => 404];
                }

                // Re-authorize on the locked concrete record to prevent TOCTOU (SEC-08)
                if (! \Illuminate\Support\Facades\Gate::allows('update', $session)) {
                    return ['error' => ['message' => 'This action is unauthorized.'], 'status' => 403];
                }

                if ($session->status !== 'unassigned') {
                    return ['error' => [
                        'code' => 'ALREADY_CLAIMED',
                        'message' => 'This chat has already been claimed by another rep.',
                    ], 'status' => 409];
                }

                $settings = SystemSettings::current();
                $maxActive = $settings->max_active_chats_per_rep;

                $currentActiveCount = ChatSession::where('assigned_to', $rep->id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->count();

                if ($currentActiveCount >= $maxActive) {
                    return ['error' => [
                        'code' => 'CAPACITY_REACHED',
                        'message' => "You already have {$currentActiveCount} active chat(s), which meets the limit of {$maxActive}. Close or hand off a chat before claiming another.",
                    ], 'status' => 403];
                }

                $session->assigned_to = $rep->id;
                $session->status = 'active';
                $session->save();
                try { ActivityLog::log($rep, "Claimed chat session #{$session->id}"); } catch (\Throwable $e) {}

                return ['session' => $session];
            });
        } catch (\Throwable $e) {
            report($e);
            $id = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error('Chat claim failed', ['error_id' => $id, 'exception' => $e]);
            return response()->json(['status' => 'error', 'message' => 'The request could not be completed.', 'error_id' => $id], 500);
        }

        if (isset($result['error'])) {
            return response()->json(array_merge(['status' => 'error'], $result['error']), $result['status']);
        }

        $session = $result['session'];

        return response()->json([
            'status' => 'ok',
            'message' => 'Chat claimed successfully.',
            'chat' => [
                'id' => $session->id,
                'clientName' => $session->client_name,
                'status' => $session->status,
                'assignedTo' => $rep->name,
            ],
        ]);
    }
}

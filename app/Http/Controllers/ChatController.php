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
        if ($request->isMethod('post')) {
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
        // Try hashed first (new), then raw for legacy backward compat
        $hash = $this->hashToken($raw);
        $session = ChatSession::where('session_token', $hash)->first();
        if ($session) return $session;
        // Legacy fallback: raw token stored before hashing
        return ChatSession::where('session_token', $raw)->first();
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

        // Revoked/legacy check - immediately reject if revoked
        if ($session->revoked_at) {
            return response()->json(['status' => 'error', 'message' => 'Session expired'], 401);
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
        return response()->json($payload);
    }

    protected function pollMessages(Request $request): JsonResponse
    {
        $request->validate([
            'sessionId' => ['required', 'string', 'max:128'],
            'after' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
        ]);
        $rawId = trim((string) $request->query('sessionId', ''));
        $afterId = (int) $request->query('after', 0);

        $messages = [];
        $session = $this->findSessionByToken($rawId);

        if ($session && $session->revoked_at) {
            return response()->json(['status' => 'error', 'message' => 'Session revoked'], 401);
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

        return response()->json(['messages' => $messages]);
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
        $this->authorize('update', ChatSession::class);
        $rep = $request->user();

        try {
            $result = DB::transaction(function () use ($sessionId, $rep) {
                $session = ChatSession::where('id', $sessionId)->lockForUpdate()->first();

                if (! $session) {
                    return ['error' => ['message' => 'Chat not found.'], 'status' => 404];
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

<?php

namespace App\Http\Controllers;

use App\Actions\Chat\ResolveGuestChat;
use App\Actions\Chat\WriteChatMessage;
use App\Http\Requests\ChatMessageRequest;
use App\Models\ActivityLog;
use App\Models\ChatSession;
use App\Models\SystemSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function messages(ChatMessageRequest $request, ResolveGuestChat $resolver, WriteChatMessage $writer): JsonResponse
    {
        // Defense in depth: the FormRequest already rejects query-string
        // credentials, but never resolve a token from the URL here.
        if ($request->query('sessionId') !== null || $request->query('after') !== null) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['sessionId' => ['Use POST body, not query string.']],
            ], 422)->header('Cache-Control', 'no-store');
        }

        $validated = $request->validated();
        $operation = $validated['operation'];

        if ($operation === 'restore') {
            return $this->handleRestore($validated, $resolver);
        }

        if ($operation === 'poll') {
            return $this->handlePoll($validated, $resolver);
        }

        return $this->handleWrite($validated, $writer);
    }

    protected function handleRestore(array $validated, ResolveGuestChat $resolver): JsonResponse
    {
        $session = $resolver($validated['sessionId'] ?? null);

        if (! $session) {
            return $this->terminalResponse(null, 'invalid', 'CHAT_INVALID', 404);
        }

        $status = $session->lifecycleStatus(now());

        if ($status === 'active') {
            return $this->activeResponse($session, $this->fullHistory($session));
        }

        if ($status === 'terminated') {
            return $this->terminalResponse($session, 'terminated', 'CHAT_TERMINATED', 410);
        }

        if ($status === 'expired') {
            return $this->terminalResponse($session, 'expired', 'CHAT_EXPIRED', 410);
        }

        return $this->terminalResponse($session, 'invalid', 'CHAT_INVALID', 404);
    }

    protected function handlePoll(array $validated, ResolveGuestChat $resolver): JsonResponse
    {
        $session = $resolver($validated['sessionId'] ?? null);

        if (! $session) {
            return $this->terminalResponse(null, 'invalid', 'CHAT_INVALID', 404);
        }

        $status = $session->lifecycleStatus(now());

        if ($status === 'active') {
            $after = (int) $validated['after'];
            $rows = $session->messages()->with('staffUser')->where('id', '>', $after)->orderBy('id')->get();

            return $this->activeResponse($session, $rows);
        }

        if ($status === 'terminated') {
            return $this->terminalResponse($session, 'terminated', 'CHAT_TERMINATED', 410);
        }

        if ($status === 'expired') {
            return $this->terminalResponse($session, 'expired', 'CHAT_EXPIRED', 410);
        }

        return $this->terminalResponse($session, 'invalid', 'CHAT_INVALID', 404);
    }

    protected function handleWrite(array $validated, WriteChatMessage $writer): JsonResponse
    {
        $operation = $validated['operation'];

        $result = $writer->forGuest(
            $validated['sessionId'] ?? null,
            $operation,
            $validated['text'] ?? null,
            $validated['page'] ?? null
        );

        if (isset($result['error'])) {
            $error = $result['error'];
            /** @var ?ChatSession $session */
            $session = $result['session'] ?? null;

            if ($error === 'terminated') {
                return $this->terminalResponse($session, 'terminated', 'CHAT_TERMINATED', 410);
            }

            if ($error === 'expired') {
                return $this->terminalResponse($session, 'expired', 'CHAT_EXPIRED', 410);
            }

            return $this->terminalResponse($session, 'invalid', 'CHAT_INVALID', 404);
        }

        /** @var ChatSession $session */
        $session = $result['session'];
        $payload = $this->activePayload($session, $this->fullHistory($session));

        if (! empty($result['created'])) {
            $payload['sessionId'] = $result['rawToken'];
        }

        return response()->json($payload)->header('Cache-Control', 'no-store');
    }

    protected function fullHistory(ChatSession $session): Collection
    {
        return $session->messages()->with('staffUser')->orderBy('id')->get();
    }

    protected function serializeMessages(Collection $rows): array
    {
        return $rows->map(fn ($m) => [
            'id' => $m->id,
            'sender' => $m->sender,
            'text' => $m->message,
            'agentName' => $m->sender === 'staff' ? ($m->staffUser?->name ?: 'Agent') : null,
            'createdAt' => $m->created_at?->toIso8601String(),
        ])->all();
    }

    protected function activePayload(ChatSession $session, Collection $rows): array
    {
        return [
            'contractVersion' => 1,
            'status' => 'active',
            'expiresAt' => $session->expires_at?->toIso8601String(),
            'messages' => $this->serializeMessages($rows),
        ];
    }

    protected function activeResponse(ChatSession $session, Collection $rows): JsonResponse
    {
        return response()->json($this->activePayload($session, $rows))
            ->header('Cache-Control', 'no-store');
    }

    protected function terminalResponse(?ChatSession $session, string $status, string $code, int $http): JsonResponse
    {
        $messages = ($status === 'terminated' && $session)
            ? $this->serializeMessages($this->fullHistory($session))
            : [];

        return response()->json([
            'contractVersion' => 1,
            'status' => $status,
            'code' => $code,
            'messages' => $messages,
        ], $http)->header('Cache-Control', 'no-store');
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

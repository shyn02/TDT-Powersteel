<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\SystemSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Port of Django's chat_messages_api — same URL handles both
     * directions (POST to send/handoff, GET to poll for staff replies),
     * matching CHAT_API_ENDPOINT usage in static/chatwidget.js.
     */
    public function messages(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            return $this->postMessage($request);
        }

        return $this->pollMessages($request);
    }

    protected function postMessage(Request $request): JsonResponse
    {
        $sessionId = trim((string) $request->input('sessionId', ''));
        $type = (string) $request->input('type', '');
        $text = trim((string) $request->input('text', ''));
        $page = trim((string) $request->input('page', ''));

        if ($sessionId === '') {
            return response()->json(['status' => 'error', 'message' => 'Missing sessionId'], 400);
        }

        $session = ChatSession::firstOrCreate(
            ['session_token' => $sessionId],
            [
                'client_name' => $page !== '' ? "Visitor ({$page})" : 'Website Visitor',
                'page' => $page,
            ]
        );

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

        return response()->json(['status' => 'ok']);
    }

    protected function pollMessages(Request $request): JsonResponse
    {
        $sessionId = trim((string) $request->query('sessionId', ''));
        $afterId = (int) $request->query('after', 0);

        $messages = [];
        $session = ChatSession::where('session_token', $sessionId)->first();

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

    /**
     * Port of Django's unassigned_chat_queue — the shared "Chat Pool" a
     * Sales Rep pulls from. Oldest-first (FIFO).
     */
    public function unassignedQueue(Request $request): JsonResponse
    {
        $sessions = ChatSession::where('status', 'unassigned')
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get();

        $chats = $sessions->map(fn (ChatSession $session) => [
            'id' => $session->id,
            'sessionToken' => $session->session_token,
            'clientName' => $session->client_name,
            'page' => $session->page,
            'createdAt' => $session->created_at?->toIso8601String(),
            'lastMessageAt' => $session->last_message_at?->toIso8601String(),
            'unreadCount' => $session->messages()->where('sender', 'client')->where('is_read', false)->count(),
        ]);

        return response()->json(['status' => 'ok', 'count' => $chats->count(), 'chats' => $chats]);
    }

    /**
     * Port of Django's claim_chat — locks the target session row (and the
     * rep's active-chat count) inside a single DB transaction so two reps
     * racing to claim the same chat can't both succeed, and rejects the
     * claim once the rep hits the Admin-configured capacity limit.
     */
    public function claim(Request $request, int $sessionId): JsonResponse
    {
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

                return ['session' => $session];
            });
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => "Unexpected error: {$e->getMessage()}"], 500);
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
                'sessionToken' => $session->session_token,
                'clientName' => $session->client_name,
                'status' => $session->status,
                'assignedTo' => $rep->name,
            ],
        ]);
    }
}
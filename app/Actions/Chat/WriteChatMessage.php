<?php

namespace App\Actions\Chat;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Thrown when a staff write is attempted against a terminal session.
 */
class ChatWriteRejectedException extends RuntimeException
{
    public function __construct(public readonly string $lifecycle, string $message = 'Chat session is not writable.')
    {
        parent::__construct($message);
    }
}

/**
 * Atomic guest/staff chat writes.
 *
 * Every committed message/handoff shares one mechanism: lock the session row,
 * re-evaluate lifecycle via the model predicates, create the message, then set
 * one captured activity timestamp across last_message_at/last_activity_at and
 * expires_at = activity + 30 days with is_active = true. Reads never mutate;
 * failed or terminal writes leave message/activity/expiry untouched.
 */
class WriteChatMessage
{
    public const HANDOFF_TEXT = '[Requested to speak with a live agent]';

    /**
     * Guest write via the opaque token path.
     *
     * A missing/blank token creates a new server-credentialed session; a
     * provided-but-unknown token is reported invalid (never auto-created).
     *
     * @return array{session?: ?ChatSession, created?: bool, rawToken?: ?string, message?: ?ChatMessage, error?: ?string}
     */
    public function forGuest(?string $rawToken, string $kind, ?string $text, ?string $page): array
    {
        $raw = $rawToken !== null ? trim($rawToken) : '';

        if ($raw === '') {
            return DB::transaction(function () use ($kind, $text, $page): array {
                // One captured timestamp for the whole committed write.
                $activityAt = now();
                $newRaw = bin2hex(random_bytes(32));
                $hash = hash_hmac('sha256', $newRaw, (string) config('app.key'));
                $pageValue = $page !== null ? trim($page) : '';

                $session = ChatSession::create([
                    'session_token' => $hash,
                    'client_name' => $pageValue !== '' ? "Visitor ({$pageValue})" : 'Website Visitor',
                    'page' => $pageValue !== '' ? $pageValue : null,
                    'is_active' => true,
                    'created_at' => $activityAt->copy(),
                    'last_message_at' => $activityAt->copy(),
                    'last_activity_at' => $activityAt->copy(),
                    'expires_at' => $activityAt->copy()->addDays(30),
                    'status' => 'unassigned',
                    'token_version' => 2,
                ]);

                $message = $this->applyCommittedWrite($session, $activityAt, [
                    'sender' => 'client',
                    'staff_user_id' => null,
                    'message' => $kind === 'handoff_requested' ? self::HANDOFF_TEXT : trim((string) $text),
                    'created_at' => $activityAt->copy(),
                ]);

                return [
                    'session' => $session->fresh(),
                    'created' => true,
                    'rawToken' => $newRaw,
                    'message' => $message,
                ];
            });
        }

        $hash = hash_hmac('sha256', $raw, (string) config('app.key'));
        $found = ChatSession::where('session_token', $hash)->first();

        if (! $found) {
            return ['error' => 'invalid', 'session' => null];
        }

        return DB::transaction(function () use ($found, $kind, $text, $page): array {
            $locked = ChatSession::where('id', $found->id)->lockForUpdate()->first();

            if (! $locked) {
                return ['error' => 'invalid', 'session' => null];
            }

            // One captured timestamp: recheck lifecycle against it, then reuse it for the write.
            $activityAt = now();
            $status = $locked->lifecycleStatus($activityAt);

            if ($status !== 'active') {
                return ['error' => $status, 'session' => $locked];
            }

            $pageValue = $page !== null ? trim($page) : '';
            if ($pageValue !== '' && ! $locked->page) {
                $locked->page = $pageValue;
            }

            $message = $this->applyCommittedWrite($locked, $activityAt, [
                'sender' => 'client',
                'staff_user_id' => null,
                'message' => $kind === 'handoff_requested' ? self::HANDOFF_TEXT : trim((string) $text),
                'created_at' => $activityAt->copy(),
            ]);

            return [
                'session' => $locked->fresh(),
                'created' => false,
                'rawToken' => null,
                'message' => $message,
            ];
        });
    }

    /**
     * Explicit authorized staff entry point.
     *
     * The caller must already be authorized (policy/Gate); this method never
     * resolves a guest token. It shares the atomic message/activity/expiry
     * mechanism and rejects terminal sessions without mutating anything.
     */
    public function forStaff(ChatSession $session, int $staffUserId, string $text): ChatMessage
    {
        $body = trim($text);

        if ($body === '') {
            throw new InvalidArgumentException('Message text is required.');
        }

        return DB::transaction(function () use ($session, $staffUserId, $body): ChatMessage {
            $locked = ChatSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            // One captured timestamp for lifecycle recheck and the committed write.
            $activityAt = now();

            if (! $locked->isGuestWritable($activityAt)) {
                throw new ChatWriteRejectedException($locked->lifecycleStatus($activityAt));
            }

            return $this->applyCommittedWrite($locked, $activityAt, [
                'sender' => 'staff',
                'staff_user_id' => $staffUserId,
                'message' => $body,
                'created_at' => $activityAt->copy(),
            ]);
        });
    }

    /**
     * Create the message and roll activity/expiry atomically.
     */
    private function applyCommittedWrite(ChatSession $locked, CarbonInterface $activityAt, array $attributes): ChatMessage
    {
        $message = $locked->messages()->create($attributes);

        $locked->last_message_at = $activityAt->copy();
        $locked->last_activity_at = $activityAt->copy();
        $locked->expires_at = $activityAt->copy()->addDays(30);
        $locked->is_active = true;
        $locked->save();

        return $message;
    }
}

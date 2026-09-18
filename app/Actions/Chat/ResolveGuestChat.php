<?php

namespace App\Actions\Chat;

use App\Models\ChatSession;

/**
 * Resolves the opaque guest token to its persisted session.
 *
 * Lookup is HMAC SHA-256 with the application key only; the raw token is
 * never logged, never placed in a URL, and never used as a query fallback.
 * Lifecycle classification is left to ChatSession::lifecycleStatus() /
 * isGuestWritable() so precedence stays in one place.
 */
class ResolveGuestChat
{
    public function __invoke(?string $rawToken): ?ChatSession
    {
        $raw = $rawToken !== null ? trim($rawToken) : '';

        if ($raw === '') {
            return null;
        }

        $hash = hash_hmac('sha256', $raw, (string) config('app.key'));

        return ChatSession::where('session_token', $hash)->first();
    }
}

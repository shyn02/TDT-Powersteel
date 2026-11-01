<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflared connects locally and forwards the client IP and public scheme.
        // Do not trust X-Forwarded-Host: Cloudflare passes that client-controlled
        // header through, which would allow host poisoning in generated URLs.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Global security headers for every web response (CSP, HSTS, etc.)
        $middleware->append(SecurityHeaders::class);

        // Only the genuinely public, anonymous-visitor endpoints are CSRF
        // exempt (quote modal, contact/referral forms, and the public chat
        // widget). These are plain fetch() POSTs from visitors who may not
        // have a fresh session yet, matching how the old Django version
        // handled them via a CSRF cookie rather than Laravel's token.
        //
        // IMPORTANT: this list must stay explicit rather than a blanket
        // 'api/*' exemption — 'api/chats/*' below requires login and must
        // keep CSRF protection, or a logged-in staff member could be
        // tricked into performing actions (e.g. claiming a chat) via a
        // forged cross-site request.
        $middleware->validateCsrfTokens(except: [
            'api/submit-quote/',
            'api/chat/messages',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Layer B (upload HTML trap) fix: the callback below REPLACES
        // Laravel's default expectsJson() check, so without the extra arms
        // Livewire upload/update failures (419/401/422) render text/html
        // despite Accept: application/json + XHR, and Livewire's
        // JSON.parse('<...') throws `Unexpected token '<'`, sticking the
        // Create button on "Uploading". The added arms only change the
        // exception RENDER format (JSON vs HTML) — no auth/session/middleware
        // weakening. `is('livewire*/*')` matches the versioned endpoint
        // prefix (livewire-<hash>/upload-file, livewire-<hash>/update);
        // routeIs covers both the `livewire.*` names and the actual update
        // route name `default-livewire.update`; expectsJson() restores the
        // framework default for any other XHR JSON client.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('livewire*/*')
                || $request->routeIs('livewire.*')
                || $request->routeIs('default-livewire.*')
                || $request->expectsJson(),
        );
    })
    ->withSchedule(function (Schedule $schedule) {
        // Archive retention: permanently delete soft-deleted records after 30 days
        $schedule->command('prune:archived')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
    })->create();

<?php

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
        // Global security headers for every web response (CSP, HSTS, etc.)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

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
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->withSchedule(function (Schedule $schedule) {
        // Archive retention: permanently delete soft-deleted records after 30 days
        $schedule->command('prune:archived')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
    })->create();

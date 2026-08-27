<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\Referral;
use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\ChatMessagePolicy;
use App\Policies\ChatSessionPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\QuoteRequestPolicy;
use App\Policies\ReferralPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);

        // SEC-01: Register policies for function-level authorization
        Gate::policy(QuoteRequest::class, QuoteRequestPolicy::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);
        Gate::policy(Referral::class, ReferralPolicy::class);
        Gate::policy(ChatSession::class, ChatSessionPolicy::class);
        Gate::policy(ChatMessage::class, ChatMessagePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // SEC-04: Wire SiteSettings session timeout to runtime config (safe, try/catch for install/migrate)
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                $timeout = \App\Models\SiteSettings::current()->session_timeout_minutes;
                if (is_numeric($timeout) && $timeout > 0 && $timeout <= 1440) {
                    config(['session.lifetime' => (int) $timeout]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore during migrations/install when table not ready
        }

        // SEC-09: Structured security event logging
        Event::listen(Login::class, function (Login $event) {
            try { ActivityLog::log($event->user, 'Login success'); } catch (\Throwable $e) {}
        });
        Event::listen(Failed::class, function (Failed $event) {
            try {
                // SEC-05: Redact PII — mask email and IP to reduce exposure in audit logs
                $emailRaw = $event->credentials['email'] ?? 'unknown';
                if (is_string($emailRaw) && str_contains($emailRaw, '@')) {
                    [$local, $domain] = explode('@', $emailRaw, 2);
                    $maskedEmail = substr($local, 0, 1) . '***@' . $domain;
                } else {
                    $maskedEmail = '***';
                }
                $ip = request()->ip() ?? 'unknown';
                $maskedIp = preg_match('/^\d+\.\d+\.\d+\.\d+$/', $ip) ? preg_replace('/\.\d+$/', '.***', $ip) : '***';
                ActivityLog::log(null, "Login failed for {$maskedEmail} from {$maskedIp}");
            } catch (\Throwable $e) {}
        });
        Gate::after(function ($user, $ability, $result, $arguments) {
            if ($result === false && $user) {
                try { ActivityLog::log($user, "Authorization denied: {$ability}"); } catch (\Throwable $e) {}
            }
        });
    }
}
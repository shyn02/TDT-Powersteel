<?php

namespace App\Providers;

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
    }
}
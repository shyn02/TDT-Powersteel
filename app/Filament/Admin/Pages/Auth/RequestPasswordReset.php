<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Support\Icons\Heroicon;

class RequestPasswordReset extends SimplePage
{
    protected static ?string $title = 'Request Password Reset';

    protected static string $routePath = 'request-password-reset';

    protected string $view = 'filament-panels::pages.auth.request-password-reset';

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->autofocus()
                    ->placeholder('your@email.com')
                    ->helperText('We will create a reset request for an admin to review.'),
            ]);
    }

    public function request(): void
    {
        $key = 'request-password-reset:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            Notification::make()->title("Too many attempts. Try again in {$seconds}s.")->danger()->send();
            return;
        }

        $data = $this->form->getState();
        $email = strtolower(trim($data['email'] ?? ''));

        $user = User::where('email', $email)->first();

        // Always show success to avoid email enumeration, but only create request if user exists
        if ($user) {
            // Prevent duplicate pending requests
            $existing = PasswordResetRequest::where('email', $email)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();
            if (!$existing) {
                PasswordResetRequest::create([
                    'email' => $email,
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'requested_at' => now(),
                    'expires_at' => now()->addHours(24),
                ]);
                try { \App\Models\ActivityLog::log(null, "Password reset requested for {$email}"); } catch (\Throwable $e) {}
            }
        }

        RateLimiter::hit($key, 300);

        Notification::make()
            ->title('If an account exists for that email, a reset request has been submitted.')
            ->body('An admin will review it and you will be contacted. If you are an admin, check User Access & Accounts → Password Reset Requests.')
            ->success()
            ->send();

        $this->form->fill();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('request')
                ->label('Submit Request')
                ->submit('request')
                ->icon(Heroicon::OutlinedPaperAirplane),
        ];
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label('Back to Sign in')
            ->url(filament()->getLoginUrl());
    }

    public function getSubheading(): ?string
    {
        return $this->loginAction();
    }
}

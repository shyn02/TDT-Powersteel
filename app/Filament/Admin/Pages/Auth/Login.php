<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Models\SiteSettings;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Matches Django's role-checked login (templates/admin_login.html +
 * _role_checked_admin_login()) — the login form has an Admin/Staff tab.
 * Correct email/password isn't enough on its own: the selected tab has to
 * match what the account's UserProfile.position actually is, or the login
 * is rejected with an explanation of which tab to use instead.
 */
class Login extends BaseLogin
{
    protected ?string $roleMismatchMessage = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getRoleFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                $this->getMfaFormComponent(),
            ]);
    }

    protected function getRoleFormComponent(): Component
    {
        return Radio::make('role')
            ->label('Signing in as')
            ->options([
                'admin' => 'Admin',
                'staff' => 'Staff',
            ])
            ->default('admin')
            ->inline()
            ->required();
    }

    protected function getMfaFormComponent(): Component
    {
        return TextInput::make('mfa_code')
            ->label('MFA Code (if enabled)')
            ->placeholder('6-digit code or recovery code')
            ->helperText('Leave blank if your account does not have MFA enabled. Admin accounts will be required to enroll.')
            ->maxLength(20)
            ->autocomplete('one-time-code');
    }

    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        if (! parent::isUserAllowedToAccessPanel($user)) {
            return false;
        }

        $selectedRole = $this->data['role'] ?? 'admin';
        $isAdminAccount = method_exists($user, 'isAdminPosition') && $user->isAdminPosition();

        $roleMatches = ($selectedRole === 'admin' && $isAdminAccount)
            || ($selectedRole === 'staff' && ! $isAdminAccount);

        if (! $roleMatches) {
            $this->roleMismatchMessage = $isAdminAccount
                ? 'This is an Admin account — please sign in from the Admin tab.'
                : 'This is a Staff account — please sign in from the Staff tab.';

            return false;
        }

        return true;
    }

    public function authenticate(): ?LoginResponse
    {
        $settings = SiteSettings::current();
        $maxAttempts = (int) ($settings->max_login_attempts ?? 5);
        $lockoutMinutes = (int) ($settings->lockout_minutes ?? 15);
        $key = strtolower($this->data['email'] ?? '') . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'data.email' => "Too many login attempts. Please try again in {$seconds} seconds (lockout: {$lockoutMinutes} min).",
            ]);
        }

        try {
            $result = parent::authenticate();
            // SEC-04: Enforce MFA for privileged accounts after password success
            $user = \Filament\Facades\Filament::auth()->user();
            if ($user && method_exists($user, 'hasMfaEnabled') && $user->hasMfaEnabled()) {
                $code = trim((string) ($this->data['mfa_code'] ?? ''));
                if ($code === '') {
                    \Filament\Facades\Filament::auth()->logout();
                    throw ValidationException::withMessages([
                        'data.mfa_code' => 'MFA code is required for this account.',
                    ]);
                }
                if (! $user->verifyMfaCode($code)) {
                    \Filament\Facades\Filament::auth()->logout();
                    try { \App\Models\ActivityLog::log($user, 'MFA failed for ' . $user->email); } catch (\Throwable $e) {}
                    // Rate-limit MFA attempts as well
                    RateLimiter::hit($key, $lockoutMinutes * 60);
                    throw ValidationException::withMessages([
                        'data.mfa_code' => 'Invalid MFA code or recovery code.',
                    ]);
                }
                try { \App\Models\ActivityLog::log($user, 'MFA success for ' . $user->email); } catch (\Throwable $e) {}
                $user->update(['mfa_verified_at' => now()]);
            } elseif ($user && method_exists($user, 'isAdminPosition') && $user->isAdminPosition() && ! $user->hasMfaEnabled()) {
                // Admin without MFA — log warning for audit, allow login but encourage enrollment (staged rollout)
                try { \App\Models\ActivityLog::log($user, 'Admin login without MFA (enrollment pending)'); } catch (\Throwable $e) {}
            }
            RateLimiter::clear($key);
            return $result;
        } catch (ValidationException $e) {
            RateLimiter::hit($key, $lockoutMinutes * 60);
            throw $e;
        }
    }

    protected function throwFailureValidationException(): never
    {
        if ($this->roleMismatchMessage) {
            throw ValidationException::withMessages([
                'data.role' => $this->roleMismatchMessage,
            ]);
        }

        parent::throwFailureValidationException();
    }
}

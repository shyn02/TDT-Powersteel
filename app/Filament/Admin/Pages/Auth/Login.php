<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
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

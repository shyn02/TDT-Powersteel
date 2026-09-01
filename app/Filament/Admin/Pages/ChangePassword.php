<?php

namespace App\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use BackedEnum;

class ChangePassword extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|\UnitEnum|null $navigationGroup = 'User Access & Accounts';
    protected static ?string $navigationLabel = 'Change Password';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;
    protected string $view = 'filament.admin.pages.change-password';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('current_password')
                    ->label('Current Password')
                    ->password()
                    ->required()
                    ->revealable(),
                TextInput::make('new_password')
                    ->label('New Password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                    ->helperText('Min 8 chars, mixed case, number & symbol'),
                TextInput::make('new_password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->same('new_password'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            Notification::make()->title('Current password is incorrect.')->danger()->send();
            return;
        }

        $user->forceFill([
            'password' => Hash::make($data['new_password']),
            'must_change_password' => false,
            'password_expires_at' => null,
        ])->save();

        try { \App\Models\ActivityLog::log($user, 'Changed password'); } catch (\Throwable $e) {}

        Notification::make()->title('Password changed successfully.')->success()->send();

        $this->form->fill();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Update Password')
                ->submit('save')
                ->icon(Heroicon::OutlinedKey),
        ];
    }
}

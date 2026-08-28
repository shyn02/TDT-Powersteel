<?php

namespace App\Filament\Admin\Pages;

use App\Models\ActivityLog;
use App\Services\TotpService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * SEC-04: Self-service TOTP MFA setup for the logged-in user.
 * Accessible to any panel user; admin can help staff enroll.
 * Shows QR, secret, verification, recovery codes, and disable.
 */
class MfaSetup extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static string|\UnitEnum|null $navigationGroup = 'User Access & Accounts';
    protected static ?string $navigationLabel = 'MFA Setup';
    protected static ?string $title = 'MFA Setup';
    protected string $view = 'filament.admin.pages.mfa-setup';

    public ?array $data = [];
    public ?string $generatedSecret = null;
    public ?string $qrUrl = null;
    public array $recoveryCodesPlain = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $user = Auth::user();
        $this->generatedSecret = $user->mfa_secret ? null : null;
        $this->form->fill([
            'code' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Two-Factor Authentication')
                    ->description(fn () => Auth::user()?->hasMfaEnabled() ? 'MFA is ENABLED for your account.' : 'MFA is NOT enabled. Enroll below.')
                    ->schema([
                        TextInput::make('code')
                            ->label('Verification Code')
                            ->placeholder('6-digit code from authenticator or recovery code')
                            ->helperText('Enter code to verify after generating secret.')
                            ->maxLength(20),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $hasMfa = $user?->hasMfaEnabled();

        return [
            Action::make('generate')
                ->label($hasMfa ? 'Regenerate Secret' : 'Generate Secret & QR')
                ->icon('heroicon-o-qr-code')
                ->requiresConfirmation()
                ->action(function () {
                    $user = Auth::user();
                    $secret = TotpService::generateSecret();
                    $codes = TotpService::generateRecoveryCodes(8);
                    $hashed = array_map(fn($c) => Hash::make($c), $codes);
                    $user->update([
                        'mfa_secret' => $secret,
                        'mfa_enabled' => false,
                        'mfa_recovery_codes' => $hashed,
                        'mfa_verified_at' => null,
                    ]);
                    $this->generatedSecret = $secret;
                    $this->qrUrl = TotpService::getQrUrl($user->email, $secret);
                    $this->recoveryCodesPlain = $codes;
                    ActivityLog::log($user, 'MFA secret generated for ' . $user->email);
                    Notification::make()->title('Secret generated — scan QR and verify code below')->success()->send();
                }),

            Action::make('verify')
                ->label('Verify & Enable MFA')
                ->icon('heroicon-o-check-circle')
                ->form([
                    TextInput::make('code')->label('6-digit code')->required()->maxLength(6),
                ])
                ->action(function (array $data) {
                    $user = Auth::user();
                    $code = $data['code'] ?? $this->data['code'] ?? '';
                    if (! $user->mfa_secret) {
                        Notification::make()->title('Generate a secret first')->danger()->send();
                        return;
                    }
                    if (! TotpService::verify($user->mfa_secret, $code)) {
                        ActivityLog::log($user, 'MFA verification failed for ' . $user->email);
                        Notification::make()->title('Invalid code')->danger()->send();
                        return;
                    }
                    $user->update(['mfa_enabled' => true, 'mfa_verified_at' => now()]);
                    ActivityLog::log($user, 'MFA enabled for ' . $user->email);
                    Notification::make()->title('MFA enabled successfully')->success()->send();
                }),

            Action::make('disable')
                ->label('Disable MFA')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $hasMfa)
                ->action(function () {
                    $user = Auth::user();
                    $user->update(['mfa_secret' => null, 'mfa_enabled' => false, 'mfa_recovery_codes' => null, 'mfa_verified_at' => null]);
                    $this->generatedSecret = null;
                    $this->qrUrl = null;
                    $this->recoveryCodesPlain = [];
                    ActivityLog::log($user, 'MFA disabled for ' . $user->email);
                    Notification::make()->title('MFA disabled')->warning()->send();
                }),
        ];
    }

    public function getViewData(): array
    {
        $user = Auth::user();
        return [
            'hasMfa' => $user?->hasMfaEnabled(),
            'secret' => $this->generatedSecret ?? $user?->mfa_secret,
            'qrUrl' => $this->qrUrl ?? ($user?->mfa_secret ? TotpService::getQrUrl($user->email, $user->mfa_secret) : null),
            'recoveryPlain' => $this->recoveryCodesPlain,
            'user' => $user,
        ];
    }
}

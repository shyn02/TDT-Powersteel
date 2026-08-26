<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\SiteSettings;
use App\Models\UserProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Full name')
                            ->required(),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->rule(function () {
                                $settings = SiteSettings::current();
                                if ($settings->require_strong_passwords) {
                                    return Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised();
                                }
                                return Password::min(8);
                            })
                            ->helperText(fn () => SiteSettings::current()->require_strong_passwords
                                ? 'Strong passwords required: min 12 chars, mixed case, numbers, symbols.'
                                : 'Leave blank to keep the current password. Enable strong passwords in Settings > Security.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Turn off to block this account from logging in.'),
                    ]),

                // Mirrors Django's UserProfileInline — position + contact
                // number live on a separate user_profiles row, but are
                // edited right here on the User form.
                Fieldset::make('Position / Profile')
                    ->relationship('profile')
                    ->columns(2)
                    ->schema([
                        Select::make('position')
                            ->options(collect(['admin', 'sales_rep', 'warehouse_staff', 'manager', 'support'])
                                ->mapWithKeys(fn ($value) => [$value => str($value)->replace('_', ' ')->title()]))
                            ->default('sales_rep')
                            ->required(),
                        TextInput::make('contact_number')
                            ->label('Contact number')
                            ->tel(),
                    ]),
            ]);
    }
}

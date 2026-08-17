<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\UserProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->helperText('Leave blank to keep the current password.'),
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

<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SiteSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('site_name')
                    ->required()
                    ->default('TDT Powersteel'),
                TextInput::make('support_email')
                    ->email(),
                TextInput::make('support_phone')
                    ->tel(),
                TextInput::make('company_address'),
                TextInput::make('session_timeout_minutes')
                    ->required()
                    ->numeric()
                    ->default(60),
                TextInput::make('max_login_attempts')
                    ->required()
                    ->numeric()
                    ->default(5),
                TextInput::make('lockout_minutes')
                    ->required()
                    ->numeric()
                    ->default(15),
                Toggle::make('require_strong_passwords')
                    ->required(),
                Toggle::make('notify_new_quote')
                    ->required(),
                Toggle::make('notify_new_referral')
                    ->required(),
                Toggle::make('notify_new_chat')
                    ->required(),
                TextInput::make('notification_email')
                    ->email(),
                TextInput::make('timezone_name')
                    ->required()
                    ->default('Asia/Manila'),
                TextInput::make('currency')
                    ->required()
                    ->default('PHP'),
                TextInput::make('date_format')
                    ->required()
                    ->default('F j, Y'),
                Toggle::make('maintenance_mode')
                    ->required(),
                Textarea::make('maintenance_message')
                    ->required()
                    ->default('System is currently under maintenance. Please check back later.')
                    ->columnSpanFull(),
                TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }
}

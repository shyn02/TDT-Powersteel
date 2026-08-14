<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SiteSettingsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('site_name'),
                TextEntry::make('support_email')
                    ->placeholder('-'),
                TextEntry::make('support_phone')
                    ->placeholder('-'),
                TextEntry::make('company_address')
                    ->placeholder('-'),
                TextEntry::make('session_timeout_minutes')
                    ->numeric(),
                TextEntry::make('max_login_attempts')
                    ->numeric(),
                TextEntry::make('lockout_minutes')
                    ->numeric(),
                IconEntry::make('notify_new_quote')
                    ->boolean(),
                IconEntry::make('notify_new_referral')
                    ->boolean(),
                IconEntry::make('notify_new_chat')
                    ->boolean(),
                TextEntry::make('notification_email')
                    ->placeholder('-'),
                TextEntry::make('timezone_name'),
                TextEntry::make('currency'),
                TextEntry::make('date_format'),
                IconEntry::make('maintenance_mode')
                    ->boolean(),
                TextEntry::make('maintenance_message')
                    ->columnSpanFull(),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_by')
                    ->numeric()
                    ->placeholder('-'),
            ]);
    }
}

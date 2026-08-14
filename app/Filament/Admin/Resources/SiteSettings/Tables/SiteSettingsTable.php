<?php

namespace App\Filament\Admin\Resources\SiteSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site_name')
                    ->searchable(),
                TextColumn::make('support_email')
                    ->searchable(),
                TextColumn::make('support_phone')
                    ->searchable(),
                TextColumn::make('company_address')
                    ->searchable(),
                TextColumn::make('session_timeout_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_login_attempts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lockout_minutes')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('notify_new_quote')
                    ->boolean(),
                IconColumn::make('notify_new_referral')
                    ->boolean(),
                IconColumn::make('notify_new_chat')
                    ->boolean(),
                TextColumn::make('notification_email')
                    ->searchable(),
                TextColumn::make('timezone_name')
                    ->searchable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('date_format')
                    ->searchable(),
                IconColumn::make('maintenance_mode')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

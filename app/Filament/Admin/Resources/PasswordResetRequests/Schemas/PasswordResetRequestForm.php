<?php

namespace App\Filament\Admin\Resources\PasswordResetRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PasswordResetRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                DateTimePicker::make('requested_at')
                    ->required(),
                DateTimePicker::make('resolved_at'),
                TextInput::make('resolved_by')
                    ->numeric(),
            ]);
    }
}

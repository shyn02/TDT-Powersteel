<?php

namespace App\Filament\Admin\Resources\ChatSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChatSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('client_name')
                    ->required()
                    ->default('Website Visitor'),
                TextInput::make('page'),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('last_message_at')
                    ->required(),
                TextInput::make('assigned_to')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('unassigned'),
                Toggle::make('is_priority')
                    ->required(),
            ]);
    }
}

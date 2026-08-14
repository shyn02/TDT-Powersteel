<?php

namespace App\Filament\Admin\Resources\ChatMessages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChatMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('session_id')
                    ->label('Chat session')
                    ->relationship('session', 'client_name')
                    ->searchable()
                    ->preload()
                    ->required(),

                // Add form: session + message only — this is the quick
                // reply. Sender/staff_user get force-set to 'staff' /
                // the current user in CreateChatMessage (see page class),
                // matching Django's save_model() override.
                Textarea::make('message')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                // Only relevant once editing an existing (client) message.
                Select::make('sender')
                    ->options([
                        'client' => 'Client',
                        'staff' => 'Staff',
                    ])
                    ->required()
                    ->hiddenOn('create'),
                Select::make('staff_user_id')
                    ->label('Staff user')
                    ->relationship('staffUser', 'name')
                    ->searchable()
                    ->hiddenOn('create'),
                Toggle::make('is_read')
                    ->hiddenOn('create'),
                DateTimePicker::make('created_at')
                    ->disabled()
                    ->hiddenOn('create'),
            ]);
    }
}

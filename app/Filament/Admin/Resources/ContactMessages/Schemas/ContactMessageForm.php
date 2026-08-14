<?php

namespace App\Filament\Admin\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('company_name'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('landline'),
                TextInput::make('address'),
                TextInput::make('how_heard')
                    ->label('How they heard about us'),
                Textarea::make('message')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'unread' => 'Unread',
                        'read' => 'Read',
                        'responded' => 'Responded',
                    ])
                    ->required()
                    ->default('unread'),
                Toggle::make('is_seen')
                    ->label('Seen'),
            ]);
    }
}

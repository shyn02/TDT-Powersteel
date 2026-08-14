<?php

namespace App\Filament\Admin\Resources\ContactMessages\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('full_name'),
                TextEntry::make('company_name')->placeholder('-'),
                TextEntry::make('email')->label('Email address')->placeholder('-'),
                TextEntry::make('phone')->placeholder('-'),
                TextEntry::make('landline')->placeholder('-'),
                TextEntry::make('address')->placeholder('-'),
                TextEntry::make('how_heard')->label('How they heard about us')->placeholder('-'),
                TextEntry::make('message')->columnSpanFull()->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'unread' => 'warning',
                        'read' => 'info',
                        'responded' => 'success',
                        default => 'gray',
                    }),
                IconEntry::make('is_seen')->boolean(),
                TextEntry::make('created_at')->dateTime(),
            ]);
    }
}

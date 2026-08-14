<?php

namespace App\Filament\Admin\Resources\QuoteRequests\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuoteRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('category.name')
                    ->label('Category')
                    ->placeholder('-'),
                TextEntry::make('product.name')
                    ->label('Product')
                    ->placeholder('-'),
                TextEntry::make('full_name'),
                TextEntry::make('company_name')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-'),
                TextEntry::make('how_heard')
                    ->placeholder('-'),
                TextEntry::make('estimated_qty')
                    ->label('Estimated Quantity'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'warning',
                        'contacted' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    }),
                IconEntry::make('is_seen')
                    ->boolean(),
                TextEntry::make('source'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}

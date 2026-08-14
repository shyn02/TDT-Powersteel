<?php

namespace App\Filament\Admin\Resources\QuoteRequests\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuoteRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('company_name'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('address'),
                TextInput::make('how_heard')
                    ->label('How they heard about us'),
                TextInput::make('estimated_qty')
                    ->label('Estimated Quantity')
                    ->required(),
                Select::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'closed' => 'Closed',
                    ])
                    ->required()
                    ->default('new'),
                Toggle::make('is_seen')
                    ->label('Seen'),
                Select::make('source')
                    ->options([
                        'home' => 'Home',
                        'product' => 'Product',
                    ])
                    ->required()
                    ->default('home'),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\ProductCategories\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

// Mirrors Django's ProductInline (StackedInline) on ProductCategoryAdmin —
// lets you view/add/edit a category's products without leaving the category page.
class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('products'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('specs')
                    ->columnSpanFull(),
                Textarea::make('sizes')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->size(40)
                    ->square(),
                TextColumn::make('name')
                    ->searchable(),
                ToggleColumn::make('is_active'),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

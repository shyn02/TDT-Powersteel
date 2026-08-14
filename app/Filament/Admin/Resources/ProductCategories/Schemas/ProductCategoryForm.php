<?php

namespace App\Filament\Admin\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                        Toggle::make('is_active')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Products page tile / category banner')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image')
                            ->label('Tile image')
                            ->image()
                            ->disk('public')
                            ->directory('categories')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Max 5MB. Large phone photos should be resized/compressed first.'),
                        FileUpload::make('banner_image')
                            ->image()
                            ->disk('public')
                            ->directory('categories')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Max 5MB. Large phone photos should be resized/compressed first.'),
                    ]),

                Section::make('Category page text')
                    ->schema([
                        Textarea::make('banner_desc')
                            ->label('Banner description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('intro_desc')
                            ->label('Intro description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
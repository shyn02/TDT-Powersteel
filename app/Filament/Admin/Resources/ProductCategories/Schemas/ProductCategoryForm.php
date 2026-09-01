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
                    ->schema([
                        FileUpload::make('image')
                            ->label('Tile image')
                            ->image()
                            ->panelLayout('grid')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->disk('public')
                            ->directory('categories')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Max 5MB. Only JPG, PNG, WebP allowed. SVG is blocked for security.')
                            ->columnSpanFull(),
                        FileUpload::make('banner_image')
                            ->image()
                            ->panelLayout('grid')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->disk('public')
                            ->directory('categories/banners')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Max 5MB. Only JPG, PNG, WebP allowed. SVG is blocked for security.')
                            ->columnSpanFull(),
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
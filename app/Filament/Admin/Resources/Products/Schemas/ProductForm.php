<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->options(fn () => ProductCategory::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Details shown on the site')
                    ->schema([
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('specs')
                            ->label('Specs (one per line)')
                            ->helperText('Each line becomes a bullet point on the product page.')
                            ->rows(5)
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->image()
                            ->panelLayout('grid')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Max 5MB. Only JPG, PNG, WebP allowed. SVG is blocked for security.'),
                    ]),

                Section::make('Request a Quote popup')
                    ->schema([
                        Textarea::make('sizes')
                            ->label('Sizes (one per line)')
                            ->helperText('Populates the size dropdown in the "Request a Quote" modal.')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Weight Calculator')
                    ->schema([
                        Select::make('calculator_type')
                            ->label('Calculator formula')
                            ->options(['none' => '— No calculator (hide the button) —', ...Product::CALCULATOR_TYPES])
                            ->native(false)
                            ->placeholder('Auto-detect (by product name / category)')
                            ->helperText('Leave blank to auto-match by product name or category, same as existing products. Pick a shape if the site shows "not available" for this product. Pick "No calculator" to hide the button entirely, even if its category normally shows one.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
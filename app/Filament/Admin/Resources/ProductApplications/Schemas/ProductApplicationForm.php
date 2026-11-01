<?php

namespace App\Filament\Admin\Resources\ProductApplications\Schemas;

use App\Support\MapPosition;
use App\Support\MediaUploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ProductApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Circle')
                    ->columns(2)
                    ->schema([
                        TextInput::make('label')
                            ->label('Map label')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Shown beside the circle and as the video title.'),
                        FileUpload::make('image')
                            ->label('Thumbnail')
                            ->image()
                            ->panelLayout('grid')
                            ->acceptedFileTypes(MediaUploadRules::MIMES)
                            ->disk('public')
                            ->directory(MediaUploadRules::DIRECTORY_PRODUCT_APPLICATION)
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(MediaUploadRules::MAX_KILOBYTES)
                            ->helperText('Clipped to a circle on the map. Max 5MB, JPG/PNG/WebP.'),
                        Textarea::make('description')
                            ->label('Optional description')
                            ->rows(3)
                            ->helperText('Shown under the video in the popup. Leave blank to hide it.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Position on the map')
                    ->description('Where this circle sits, as a percentage of the artwork. The preview updates as you type.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('x')
                            ->label('Horizontal (x)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(50)
                            ->suffix('%')
                            ->live(),
                        TextInput::make('y')
                            ->label('Vertical (y)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(50)
                            ->suffix('%')
                            ->live(),
                        TextInput::make('anchor_x')
                            ->label('Leader line anchor (x)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->live()
                            ->helperText('Where the line points on the map. Leave both anchor fields blank for no line.'),
                        TextInput::make('anchor_y')
                            ->label('Leader line anchor (y)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->live()
                            ->helperText('Set both anchor values together, or neither.'),
                        View::make('filament.admin.product-application-position-preview')
                            ->viewData(fn (Get $get) => [
                                'position' => MapPosition::from($get('x'), $get('y')),
                                'anchor' => is_numeric($get('anchor_x')) && is_numeric($get('anchor_y'))
                                    ? MapPosition::from($get('anchor_x'), $get('anchor_y'))
                                    : null,
                                'thumbnail' => is_string($get('image'))
                                    ? MediaUploadRules::url($get('image'))
                                    : null,
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Application video')
                    ->schema([
                        FileUpload::make('video_file')
                            ->label('Video')
                            ->acceptedFileTypes(MediaUploadRules::VIDEO_MIMES)
                            ->disk('public')
                            ->directory(MediaUploadRules::DIRECTORY_PRODUCT_APPLICATION)
                            ->visibility('public')
                            ->maxSize(MediaUploadRules::VIDEO_MAX_KILOBYTES)
                            ->columnSpanFull()
                            ->helperText('MP4 or WebM, max 50MB. A product without a video still appears on the map, but the circle cannot be opened.'),
                    ]),

                Section::make('Visibility and order')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Order')
                            ->numeric()
                            ->required()
                            ->default(0),
                        Hidden::make('migration_key')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}

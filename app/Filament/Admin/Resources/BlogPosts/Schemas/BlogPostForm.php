<?php

namespace App\Filament\Admin\Resources\BlogPosts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('tag')
                    ->required()
                    ->default('Guides'),
                TextInput::make('excerpt')
                    ->required(),
                FileUpload::make('cover_image')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                    ->disk('public')
                    ->directory('blog')
                    ->imageEditor()
                    ->maxSize(5120)
                    ->helperText('Max 5MB. Only JPG, PNG, WebP allowed. SVG is blocked for security.'),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_featured')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                DatePicker::make('published_date')
                    ->required(),
            ]);
    }
}
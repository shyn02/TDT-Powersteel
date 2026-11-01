<?php

namespace App\Filament\Admin\Resources\Projects\Schemas;

use App\Support\MediaUploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tag')
                    ->maxLength(255),
                FileUpload::make('image')
                    ->image()
                    ->panelLayout('grid')
                    ->acceptedFileTypes(MediaUploadRules::MIMES)
                    ->disk('public')
                    ->directory(MediaUploadRules::DIRECTORY_PROJECT)
                    ->visibility('public')
                    ->imageEditor()
                    ->imagePreviewHeight('200')
                    ->maxSize(MediaUploadRules::MAX_KILOBYTES)
                    ->helperText('Max 5MB. Only JPG, PNG, WebP allowed. SVG is blocked for security.')
                    ->columnSpanFull(),
                TextInput::make('location')
                    ->maxLength(255),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('info')
                    ->rows(4)
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->label('Published')
                    ->required()
                    ->default(false),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Hidden::make('migration_key')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}

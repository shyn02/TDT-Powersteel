<?php

namespace App\Filament\Admin\Resources\SocialHighlights\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SocialHighlightForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('platform')
                    ->required()
                    ->default('instagram_embed'),
                TextInput::make('tag_label')
                    ->required()
                    ->default('Instagram'),
                TextInput::make('badge_label')
                    ->required()
                    ->default('Featured Post'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('description')
                    ->required(),
                TextInput::make('link_url')
                    ->url()
                    ->required(),
                TextInput::make('embed_permalink'),
                TextInput::make('handle'),
                TextInput::make('video_file'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}

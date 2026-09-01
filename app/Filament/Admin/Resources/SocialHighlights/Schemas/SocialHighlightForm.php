<?php

namespace App\Filament\Admin\Resources\SocialHighlights\Schemas;

use Filament\Forms\Components\FileUpload;
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
                TextInput::make('embed_permalink')
                    ->helperText('Used only when no video file is uploaded below. For Facebook/Instagram posts — reels are unreliable via this method, upload a video file instead.'),
                TextInput::make('handle'),
                FileUpload::make('video_file')
                    ->label('Video file')
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                    ->panelLayout('grid')
                    ->disk('public')
                    ->directory('social_highlights')
                    ->visibility('public')
                    ->maxSize(51200)
                    ->columnSpanFull()
                    ->helperText('Max 50MB. MP4, WebM, or MOV. Takes priority over the Embed permalink above — recommended, since it always works regardless of Facebook/Instagram embed availability.'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
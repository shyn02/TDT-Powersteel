<?php

namespace App\Filament\Admin\Resources\SocialHighlights\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SocialHighlightInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('platform'),
                TextEntry::make('tag_label'),
                TextEntry::make('badge_label'),
                TextEntry::make('title'),
                TextEntry::make('description'),
                TextEntry::make('link_url'),
                TextEntry::make('embed_permalink')
                    ->placeholder('-'),
                TextEntry::make('handle')
                    ->placeholder('-'),
                TextEntry::make('video_file')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('order')
                    ->numeric(),
            ]);
    }
}

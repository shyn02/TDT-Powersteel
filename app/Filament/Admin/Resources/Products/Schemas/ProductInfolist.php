<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Support\ProductImageLightbox;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('category.name')
                    ->label('Category'),
                TextEntry::make('name'),
                TextEntry::make('specs')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('sizes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                ImageEntry::make('image')
                    ->disk('public')
                    ->placeholder('-')
                    // Click-to-enlarge: marks the image for the shared
                    // product-image-lightbox overlay (see ProductImageLightbox).
                    ->extraImgAttributes(ProductImageLightbox::triggerAttributes()),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

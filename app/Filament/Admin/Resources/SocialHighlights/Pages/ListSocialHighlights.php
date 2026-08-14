<?php

namespace App\Filament\Admin\Resources\SocialHighlights\Pages;

use App\Filament\Admin\Resources\SocialHighlights\SocialHighlightResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSocialHighlights extends ListRecords
{
    protected static string $resource = SocialHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

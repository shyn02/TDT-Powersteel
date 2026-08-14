<?php

namespace App\Filament\Admin\Resources\SocialHighlights\Pages;

use App\Filament\Admin\Resources\SocialHighlights\SocialHighlightResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSocialHighlight extends EditRecord
{
    protected static string $resource = SocialHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

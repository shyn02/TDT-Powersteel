<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteSettings extends EditRecord
{
    protected static string $resource = SiteSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

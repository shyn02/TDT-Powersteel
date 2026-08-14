<?php

namespace App\Filament\Admin\Resources\SystemSettings\Pages;

use App\Filament\Admin\Resources\SystemSettings\SystemSettingsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSystemSettings extends ViewRecord
{
    protected static string $resource = SystemSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

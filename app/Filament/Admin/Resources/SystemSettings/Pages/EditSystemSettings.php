<?php

namespace App\Filament\Admin\Resources\SystemSettings\Pages;

use App\Filament\Admin\Resources\SystemSettings\SystemSettingsResource;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;

class EditSystemSettings extends EditRecord
{
    protected static string $resource = SystemSettingsResource::class;

    // No ViewAction/DeleteAction — Django blocks add & delete on this
    // singleton; there's nothing to view separately either.
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_at'] = now();
        $data['updated_by'] = Auth::id();

        return $data;
    }
}

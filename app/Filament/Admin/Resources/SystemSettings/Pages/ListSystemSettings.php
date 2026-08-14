<?php

namespace App\Filament\Admin\Resources\SystemSettings\Pages;

use App\Filament\Admin\Resources\SystemSettings\SystemSettingsResource;
use App\Models\SystemSettings;
use Filament\Resources\Pages\ListRecords;

// Django's changelist_view skips the list entirely and redirects straight
// to the (singleton) change form, creating the row on first visit via
// get_solo(). Same here — nobody should ever see a "list of one".
class ListSystemSettings extends ListRecords
{
    protected static string $resource = SystemSettingsResource::class;

    public function mount(): void
    {
        $settings = SystemSettings::current();

        $this->redirect(SystemSettingsResource::getUrl('edit', ['record' => $settings]));
    }
}

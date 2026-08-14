<?php

namespace App\Filament\Admin\Resources\PasswordResetRequests\Pages;

use App\Filament\Admin\Resources\PasswordResetRequests\PasswordResetRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPasswordResetRequests extends ListRecords
{
    protected static string $resource = PasswordResetRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

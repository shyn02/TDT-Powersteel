<?php

namespace App\Filament\Admin\Resources\PasswordResetRequests\Pages;

use App\Filament\Admin\Resources\PasswordResetRequests\PasswordResetRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPasswordResetRequest extends EditRecord
{
    protected static string $resource = PasswordResetRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

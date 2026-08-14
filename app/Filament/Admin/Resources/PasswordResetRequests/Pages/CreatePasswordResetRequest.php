<?php

namespace App\Filament\Admin\Resources\PasswordResetRequests\Pages;

use App\Filament\Admin\Resources\PasswordResetRequests\PasswordResetRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePasswordResetRequest extends CreateRecord
{
    protected static string $resource = PasswordResetRequestResource::class;
}

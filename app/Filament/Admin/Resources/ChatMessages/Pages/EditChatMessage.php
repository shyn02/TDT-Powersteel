<?php

namespace App\Filament\Admin\Resources\ChatMessages\Pages;

use App\Filament\Admin\Resources\ChatMessages\ChatMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChatMessage extends EditRecord
{
    protected static string $resource = ChatMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

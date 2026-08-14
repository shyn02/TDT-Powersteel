<?php

namespace App\Filament\Admin\Resources\ChatSessions\Pages;

use App\Filament\Admin\Resources\ChatSessions\ChatSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditChatSession extends EditRecord
{
    protected static string $resource = ChatSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

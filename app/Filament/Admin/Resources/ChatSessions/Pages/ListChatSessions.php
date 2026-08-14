<?php

namespace App\Filament\Admin\Resources\ChatSessions\Pages;

use App\Filament\Admin\Resources\ChatSessions\ChatSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChatSessions extends ListRecords
{
    protected static string $resource = ChatSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

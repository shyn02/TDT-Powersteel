<?php

namespace App\Filament\Admin\Resources\ChatSessions\Pages;

use App\Filament\Admin\Resources\ChatSessions\ChatSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChatSession extends CreateRecord
{
    protected static string $resource = ChatSessionResource::class;
}

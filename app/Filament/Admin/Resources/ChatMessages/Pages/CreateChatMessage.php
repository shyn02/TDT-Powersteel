<?php

namespace App\Filament\Admin\Resources\ChatMessages\Pages;

use App\Filament\Admin\Resources\ChatMessages\ChatMessageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateChatMessage extends CreateRecord
{
    protected static string $resource = ChatMessageResource::class;

    // Matches Django's ChatMessageAdmin.save_model(): the "Add" form only
    // collects session + message; sender/staff_user are force-set here.
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sender'] = 'staff';
        $data['staff_user_id'] = Auth::id();
        $data['is_read'] = true;
        $data['created_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->session->update([
            'is_active' => true,
            'last_message_at' => now(),
        ]);
    }
}

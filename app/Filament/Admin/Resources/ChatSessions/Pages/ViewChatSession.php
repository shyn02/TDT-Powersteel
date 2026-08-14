<?php

namespace App\Filament\Admin\Resources\ChatSessions\Pages;

use App\Filament\Admin\Resources\ChatSessions\ChatSessionResource;
use App\Models\ChatMessage;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewChatSession extends ViewRecord
{
    protected static string $resource = ChatSessionResource::class;

    protected string $view = 'filament.admin.resources.chat-sessions.pages.view-chat-session';

    public string $newMessage = '';

    // Canned responses shown as quick-tap chips above the input box —
    // clicking one fills the reply field so staff can still edit before
    // sending, rather than firing off immediately.
    public array $quickReplies = [
        'Salamat po sa inyong mensahe! Sasagot po kami sa lalong madaling panahon.',
        'Puwede niyo pong tawagan ang (02) 8831-0000 para sa agarang tulong.',
        'Isang saglit lang po, ichecheck namin yan.',
        'Available po kami Mon-Fri, 8:00 AM - 5:00 PM.',
        'Paki-share po ang inyong contact number para masundan namin kayo.',
    ];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->messages()
            ->where('sender', 'client')
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function useQuickReply(string $text): void
    {
        $this->newMessage = $text;
    }

    public function sendReply(): void
    {
        $text = trim($this->newMessage);

        if ($text === '') {
            return;
        }

        ChatMessage::create([
            'session_id' => $this->record->id,
            'sender' => 'staff',
            'staff_user_id' => Auth::id(),
            'message' => $text,
            'is_read' => true,
            'created_at' => now(),
        ]);

        $this->record->update([
            'is_active' => true,
            'last_message_at' => now(),
        ]);

        $this->newMessage = '';

        Notification::make()->title('Reply sent')->success()->send();
    }

    public function markIncomingAsRead(): void
    {
        $this->record->messages()
            ->where('sender', 'client')
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

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
        'Thank you for your message! We will respond as soon as possible.',
        'You can call (02) 8831-0000 for immediate assistance.',
        'One moment please, we will check on that.',
        'We are available Mon-Fri, 8:00 AM - 5:00 PM.',
        'Please share your contact number so we can follow up with you.',
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

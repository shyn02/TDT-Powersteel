<?php

namespace App\Filament\Admin\Resources\ChatSessions\Pages;

use App\Actions\Chat\ChatWriteRejectedException;
use App\Actions\Chat\WriteChatMessage;
use App\Filament\Admin\Resources\ChatSessions\ChatSessionResource;
use App\Models\ActivityLog;
use App\Models\ChatSession;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $staffId = Auth::id();

        if ($staffId === null) {
            return;
        }

        try {
            (new WriteChatMessage)->forStaff($this->record, (int) $staffId, $text);
        } catch (ChatWriteRejectedException $e) {
            Notification::make()
                ->title('Chat is '.$e->lifecycle.' and cannot accept replies.')
                ->danger()
                ->send();

            return;
        }

        $this->record->refresh();
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
            Action::make('terminate')
                ->label('Terminate chat')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Terminate chat?')
                ->modalDescription('Guests will no longer be able to reply. History is retained.')
                ->action(function (ChatSession $record) {
                    $this->authorize('terminate', $record);

                    $actor = Auth::user();

                    DB::transaction(function () use ($record, $actor) {
                        $locked = ChatSession::where('id', $record->id)->lockForUpdate()->firstOrFail();

                        // Idempotent first-write-wins: repeat termination preserves
                        // the original actor/time and never touches activity/expiry.
                        if ($locked->terminated_at !== null) {
                            return;
                        }

                        $now = now();

                        $locked->terminated_at = $now->copy();
                        $locked->terminated_by = $actor?->id;
                        $locked->is_active = false;
                        $locked->status = 'closed';
                        $locked->save();

                        try {
                            ActivityLog::log($actor, "Terminated chat session #{$locked->id}");
                        } catch (\Throwable $e) {
                        }
                    });

                    Notification::make()->title('Chat terminated')->success()->send();
                }),
        ];
    }
}

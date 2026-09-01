<?php

namespace App\Filament\Concerns;

/**
 * Matches Django's change_view() override on QuoteRequestAdmin /
 * ContactMessageAdmin / ReferralAdmin — opening the detail page of a
 * record automatically flips is_seen to true, which lowers the sidebar
 * badge count (and un-bolds the row) without requiring a manual action.
 */
trait MarksSeenOnView
{
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $updates = [];
        if ($this->record->is_seen === false) {
            $updates['is_seen'] = true;
        }
        // Auto-advance ContactMessage status unread -> read on view (matches expected UX; QuoteRequest/Referral keep 'new')
        if (isset($this->record->status) && $this->record->status === 'unread') {
            $updates['status'] = 'read';
        }

        if (! empty($updates)) {
            $this->record->update($updates);
        }
    }
}

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

        if ($this->record->is_seen === false) {
            $this->record->update(['is_seen' => true]);
        }
    }
}

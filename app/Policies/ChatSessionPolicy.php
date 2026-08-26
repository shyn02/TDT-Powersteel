<?php

namespace App\Policies;

use App\Models\ChatSession;
use App\Models\User;

class ChatSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->profile?->position, ['admin','sales_rep','manager','support'], true);
    }
    public function view(User $user, ChatSession $record): bool
    {
        // Admin sees all, sales/support sees own assigned or unassigned pool
        if ($user->isAdminPosition()) return true;
        $pos = $user->profile?->position;
        if (! in_array($pos, ['sales_rep','manager','support'], true)) return false;
        // Own assigned or unassigned
        return $record->assigned_to === $user->id || $record->status === 'unassigned';
    }
    public function create(User $user): bool { return false; } // sessions created by visitors, not staff
    public function update(User $user, ChatSession $record): bool
    {
        return $user->isAdminPosition() || ($record->assigned_to === $user->id && in_array($user->profile?->position, ['sales_rep','support','manager'], true));
    }
    public function delete(User $user, ChatSession $record): bool { return $user->isAdminPosition(); }
    public function deleteAny(User $user): bool { return $user->isAdminPosition(); }
    public function restore(User $user, ChatSession $record): bool { return $user->isAdminPosition(); }
    public function forceDelete(User $user, ChatSession $record): bool { return $user->isAdminPosition(); }
}

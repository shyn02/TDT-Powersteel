<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    public function viewAny(User $user): bool { return $user->is_active && in_array($user->profile?->position, ['admin','sales_rep','manager','support'], true); }
    public function view(User $user, ChatMessage $record): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->is_active && in_array($user->profile?->position, ['admin','sales_rep','support','manager'], true); }
    public function update(User $user, ChatMessage $record): bool { return $user->isAdminPosition(); }
    public function delete(User $user, ChatMessage $record): bool { return $user->isAdminPosition(); }
    public function deleteAny(User $user): bool { return $user->isAdminPosition(); }
    public function restore(User $user, ChatMessage $record): bool { return $user->isAdminPosition(); }
    public function forceDelete(User $user, ChatMessage $record): bool { return $user->isAdminPosition(); }
}

<?php

namespace App\Policies;

use App\Models\QuoteRequest;
use App\Models\User;

class QuoteRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->profile?->position, ['admin','sales_rep','manager','support'], true);
    }
    public function view(User $user, QuoteRequest $record): bool
    {
        return $this->viewAny($user);
    }
    public function create(User $user): bool
    {
        return $user->isAdminPosition() || in_array($user->profile?->position, ['sales_rep','manager'], true);
    }
    public function update(User $user, QuoteRequest $record): bool
    {
        return $user->isAdminPosition() || in_array($user->profile?->position, ['sales_rep','manager','support'], true);
    }
    public function delete(User $user, QuoteRequest $record): bool
    {
        return $user->isAdminPosition();
    }
    public function deleteAny(User $user): bool
    {
        return $user->isAdminPosition();
    }
    public function restore(User $user, QuoteRequest $record): bool { return $user->isAdminPosition(); }
    public function forceDelete(User $user, QuoteRequest $record): bool { return $user->isAdminPosition(); }
}

<?php

namespace App\Policies;

use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    public function viewAny(User $user): bool { return $user->is_active && in_array($user->profile?->position, ['admin','sales_rep','manager','support'], true); }
    public function view(User $user, Referral $record): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->is_active; }
    public function update(User $user, Referral $record): bool { return $user->isAdminPosition() || in_array($user->profile?->position, ['sales_rep','manager','support'], true); }
    public function delete(User $user, Referral $record): bool { return $user->isAdminPosition(); }
    public function deleteAny(User $user): bool { return $user->isAdminPosition(); }
    public function restore(User $user, Referral $record): bool { return $user->isAdminPosition(); }
    public function forceDelete(User $user, Referral $record): bool { return $user->isAdminPosition(); }
}

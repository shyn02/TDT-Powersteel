<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->isAdminPosition(); }
    public function view(User $user, User $model): bool { return $user->isAdminPosition() || $user->id === $model->id; }
    public function create(User $user): bool { return $user->isAdminPosition(); }
    public function update(User $user, User $model): bool
    {
        // Non-admin cannot change position/is_active fields - enforced in form, but double-check here
        return $user->isAdminPosition();
    }
    public function delete(User $user, User $model): bool { return $user->isAdminPosition() && $user->id !== $model->id; }
    public function deleteAny(User $user): bool { return $user->isAdminPosition(); }
    public function restore(User $user, User $model): bool { return $user->isAdminPosition(); }
    public function forceDelete(User $user, User $model): bool { return $user->isAdminPosition(); }
}

<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserProfile;

// Replicates the Django post_save signal that auto-creates a UserProfile
// for every new User (including users made via `php artisan make:filament-user`
// or a seeder, mirroring Django's createsuperuser behavior).
class UserObserver
{
    public function created(User $user): void
    {
        UserProfile::firstOrCreate(['user_id' => $user->id]);
    }
}

// Register in app/Providers/AppServiceProvider.php boot():
//   User::observe(UserObserver::class);

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin account — position must be 'admin' to see Products,
        // Categories, Users, and the Settings/Data Management pages
        // (see App\Models\User::isAdminPosition()).
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@tdtpowersteel.test',
        ]);

        UserProfile::create([
            'user_id' => $admin->id,
            'position' => 'admin',
            'contact_number' => null,
        ]);
    }
}

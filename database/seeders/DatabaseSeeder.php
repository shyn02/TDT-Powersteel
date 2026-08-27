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
        // SECURITY: Never seed a privileged admin with the factory's known
        // default password ('password') into a reachable database (SEC-03).
        // In production this seeder now FAILS CLOSED unless explicit
        // bootstrap credentials are provided via env/secret manager.
        // For local/dev, set BOOTSTRAP_ADMIN_PASSWORD in .env or allow
        // non-production without it. Never commit a real password.
        $bootstrapEmail = env('BOOTSTRAP_ADMIN_EMAIL', 'admin@tdtpowersteel.test');
        $bootstrapPassword = env('BOOTSTRAP_ADMIN_PASSWORD');

        if (app()->environment('production') && blank($bootstrapPassword)) {
            throw new \RuntimeException(
                'Refusing to seed admin in production without BOOTSTRAP_ADMIN_PASSWORD. '.
                'Set a strong bootstrap password via secret manager/env and re-run.'
            );
        }

        // In production, use the provided strong password; in local/dev
        // generate a random one and display it (never hard-code 'password').
        $adminAttrs = [
            'name' => 'Admin User',
            'email' => $bootstrapEmail,
        ];
        $generatedPlain = null;
        if (! blank($bootstrapPassword)) {
            $adminAttrs['password'] = \Illuminate\Support\Facades\Hash::make($bootstrapPassword);
        } elseif (! app()->environment('production')) {
            $generatedPlain = \Illuminate\Support\Str::random(16);
            $adminAttrs['password'] = \Illuminate\Support\Facades\Hash::make($generatedPlain);
        }

        $admin = User::factory()->create($adminAttrs);

        UserProfile::updateOrCreate(
            ['user_id' => $admin->id],
            ['position' => 'admin', 'contact_number' => null]
        );

        // Force password rotation notice on first login if using bootstrap
        if (! blank($bootstrapPassword) && app()->environment('production')) {
            $this->command?->warn('Admin seeded with bootstrap password - force rotation on first login.');
        }
    }
}

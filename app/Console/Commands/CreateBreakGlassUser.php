<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateBreakGlassUser extends Command
{
    protected $signature = 'make:break-glass {email?} {--position=admin}';
    protected $description = 'SEC-04: Create audited break-glass admin account (for MFA lockout recovery)';

    public function handle(): int
    {
        $email = $this->argument('email') ?? 'breakglass@tdtpowersteel.local';
        if (User::where('email', $email)->exists()) {
            $this->error("User {$email} already exists.");
            return 1;
        }
        $password = Str::random(20);
        $user = User::create([
            'name' => 'Break Glass',
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $user->id,
            'position' => $this->option('position'),
            'full_name' => 'Break Glass Admin',
        ]);
        // Break-glass should NOT have MFA to allow recovery, but is audited
        \App\Models\ActivityLog::log($user, "Break-glass account created: {$email}");

        $this->info("Break-glass user created: {$email}");
        $this->warn("PASSWORD (store in vault, single use): {$password}");
        $this->info("Run: php artisan mfa:setup {$email} to enable MFA after recovery, or keep as emergency access.");
        return 0;
    }
}

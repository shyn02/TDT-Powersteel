<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MfaVerifyCommand extends Command
{
    protected $signature = 'mfa:verify {email} {code}';
    protected $description = 'Verify TOTP code and enable MFA';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user || ! $user->mfa_secret) {
            $this->error('User not found or no MFA secret. Run mfa:setup first.');
            return 1;
        }
        $code = $this->argument('code');
        if (! \App\Services\TotpService::verify($user->mfa_secret, $code)) {
            $this->error('Invalid code.');
            return 1;
        }
        $user->update(['mfa_enabled' => true, 'mfa_verified_at' => now()]);
        $this->info('MFA verified and enabled for ' . $user->email);
        return 0;
    }
}

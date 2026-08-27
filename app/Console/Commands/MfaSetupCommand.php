<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MfaSetupCommand extends Command
{
    protected $signature = 'mfa:setup {email : User email} {--disable : Disable MFA} {--show : Show existing secret}';
    protected $description = 'SEC-04: Setup or disable TOTP MFA for a user (admin). Generates QR and recovery codes.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("User {$email} not found.");
            return 1;
        }

        if ($this->option('disable')) {
            $user->update(['mfa_secret' => null, 'mfa_enabled' => false, 'mfa_recovery_codes' => null, 'mfa_verified_at' => null]);
            $this->info("MFA disabled for {$email}");
            return 0;
        }

        if ($this->option('show') && $user->hasMfaEnabled()) {
            $this->info("MFA enabled. Secret: {$user->mfa_secret}");
            $this->info("QR: " . TotpService::getQrUrl($user->email, $user->mfa_secret));
            return 0;
        }

        $secret = TotpService::generateSecret();
        $codes = TotpService::generateRecoveryCodes(8);
        $hashedCodes = array_map(fn($c) => Hash::make($c), $codes);

        $user->update([
            'mfa_secret' => $secret,
            'mfa_enabled' => false, // will be enabled after verification
            'mfa_recovery_codes' => $hashedCodes,
        ]);

        $this->info("MFA secret generated for {$email}");
        $this->warn("SECRET (store in authenticator now): {$secret}");
        $this->info("QR URL: " . TotpService::getQrUrl($user->email, $secret));
        $this->info("Or scan via: https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode(TotpService::getQrUrl($user->email, $secret)));
        $this->newLine();
        $this->warn("RECOVERY CODES (store securely, single-use):");
        foreach ($codes as $c) $this->line("  - {$c}");
        $this->newLine();
        $this->info("To verify and enable: php artisan mfa:verify {$email} <6-digit-code>");
        $this->info("Break-glass: keep these recovery codes offline. Use 'php artisan mfa:setup {$email} --disable' to disable if lost.");

        return 0;
    }
}

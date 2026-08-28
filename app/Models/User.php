<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_active', 'mfa_secret', 'mfa_enabled', 'mfa_recovery_codes', 'mfa_verified_at', 'password_expires_at', 'must_change_password'])]
#[Hidden(['password', 'remember_token', 'mfa_secret', 'mfa_recovery_codes'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'mfa_secret' => 'encrypted',
            'mfa_enabled' => 'boolean',
            'mfa_recovery_codes' => 'encrypted:array',
            'mfa_verified_at' => 'datetime',
            'password_expires_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Equivalent of Django's is_admin_position(): only true when the
     * account's UserProfile.position is 'admin'. Other staff positions
     * (sales rep, warehouse, manager, support) return false even though
     * they may still be able to log into the panel.
     */
    public function isAdminPosition(): bool
    {
        return $this->profile?->position === 'admin';
    }

    // SEC-04: MFA helpers
    public function hasMfaEnabled(): bool
    {
        return (bool) $this->mfa_enabled && ! empty($this->mfa_secret);
    }

    public function verifyMfaCode(string $code): bool
    {
        if (! $this->hasMfaEnabled()) return false;
        // Check recovery codes first (hashed comparison)
        $codes = $this->mfa_recovery_codes ?? [];
        foreach ($codes as $i => $hashed) {
            if (password_verify($code, $hashed)) {
                // Single-use: remove used code
                unset($codes[$i]);
                $this->mfa_recovery_codes = array_values($codes);
                $this->save();
                return true;
            }
        }
        return \App\Services\TotpService::verify($this->mfa_secret, $code);
    }

    // SEC-04: Temporary password expiry
    public function isPasswordExpired(): bool
    {
        if ($this->must_change_password) return true;
        if ($this->password_expires_at && $this->password_expires_at->isPast()) return true;
        return false;
    }
}

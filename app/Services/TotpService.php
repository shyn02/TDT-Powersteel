<?php

namespace App\Services;

/**
 * Minimal RFC 6238 TOTP (SHA1, 30s step, 6 digits) without external deps.
 * SEC-04: Provides MFA for privileged accounts. Recovery codes and audit logs
 * are handled by the caller (see User model and AdminPanelProvider).
 */
class TotpService
{
    public const STEP = 30;
    public const DIGITS = 6;
    public const WINDOW = 1; // allow +/-1 step

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function getQrUrl(string $email, string $secret, string $issuer = 'TDT Powersteel'): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        $issuerEnc = rawurlencode($issuer);
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEnc}&algorithm=SHA1&digits=6&period=30";
    }

    public static function verify(string $secret, string $code, int $window = self::WINDOW): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::DIGITS) return false;
        $secretBin = self::base32Decode($secret);
        if ($secretBin === false) return false;
        $time = (int) floor(time() / self::STEP);
        for ($i = -$window; $i <= $window; $i++) {
            $calc = self::hotp($secretBin, $time + $i);
            if (hash_equals($calc, $code)) return true;
        }
        return false;
    }

    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8) . '-' . substr(bin2hex(random_bytes(5)), 0, 8));
        }
        return $codes;
    }

    private static function hotp(string $key, int $counter): string
    {
        $counterBin = pack('J', $counter); // 64-bit big endian
        $hash = hash_hmac('sha1', $counterBin, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $bin = ((ord($hash[$offset]) & 0x7F) << 24)
             | ((ord($hash[$offset + 1]) & 0xFF) << 16)
             | ((ord($hash[$offset + 2]) & 0xFF) << 8)
             | (ord($hash[$offset + 3]) & 0xFF);
        $otp = $bin % (10 ** self::DIGITS);
        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $out = '';
        $bits = 0;
        $value = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $value = ($value << 8) | ord($data[$i]);
            $bits += 8;
            while ($bits >= 5) {
                $out .= $alphabet[($value >> ($bits - 5)) & 31];
                $bits -= 5;
            }
        }
        if ($bits > 0) {
            $out .= $alphabet[($value << (5 - $bits)) & 31];
        }
        return $out;
    }

    private static function base32Decode(string $b32): string|false
    {
        $b32 = strtoupper($b32);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $out = '';
        $bits = 0;
        $value = 0;
        for ($i = 0; $i < strlen($b32); $i++) {
            $c = $b32[$i];
            if ($c === '=' || $c === ' ') continue;
            $pos = strpos($alphabet, $c);
            if ($pos === false) return false;
            $value = ($value << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $out .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }
        return $out;
    }
}

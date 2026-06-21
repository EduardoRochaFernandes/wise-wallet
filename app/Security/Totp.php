<?php
/**
 * WiseWallet 2.0 — TOTP (RFC 6238) two-factor authentication, pure PHP.
 * Compatible with Google Authenticator, Authy, 1Password, etc.
 */

declare(strict_types=1);

final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;

    /** Generate a new random base32 secret. */
    public static function secret(int $bytes = 16): string
    {
        $raw = random_bytes($bytes);
        $bits = '';
        foreach (str_split($raw) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) { $chunk = str_pad($chunk, 5, '0'); }
            $out .= self::ALPHABET[bindec($chunk)];
        }
        return $out;
    }

    /** otpauth:// URI for QR codes / manual entry. */
    public static function uri(string $secret, string $account, string $issuer = 'WiseWallet'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&period=' . self::PERIOD . '&digits=6';
    }

    /** Current 6-digit code for a secret at a given time. */
    public static function code(string $secret, ?int $ts = null): string
    {
        $ts = $ts ?? time();
        $counter = (int) floor($ts / self::PERIOD);
        $bin = pack('N', 0) . pack('N', $counter); // 64-bit big-endian counter
        $hash = hash_hmac('sha1', $bin, self::base32decode($secret), true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
        $part = ((ord($hash[$offset]) & 0x7f) << 24)
              | ((ord($hash[$offset + 1]) & 0xff) << 16)
              | ((ord($hash[$offset + 2]) & 0xff) << 8)
              | (ord($hash[$offset + 3]) & 0xff);
        return str_pad((string) ($part % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /** Verify a user-entered code, allowing ±1 step of clock drift. */
    public static function verify(string $secret, string $input, int $window = 1): bool
    {
        $input = preg_replace('/\D/', '', $input);
        if (strlen($input) !== 6) { return false; }
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, time() + $i * self::PERIOD), $input)) {
                return true;
            }
        }
        return false;
    }

    private static function base32decode(string $b32): string
    {
        $b32 = strtoupper($b32);
        $bits = '';
        foreach (str_split($b32) as $c) {
            $v = strpos(self::ALPHABET, $c);
            if ($v === false) { continue; }
            $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) { $bytes .= chr(bindec($byte)); }
        }
        return $bytes;
    }
}

<?php
/**
 * WiseWallet 2.0 — breached-password check via HaveIBeenPwned's k-anonymity
 * range API (keyless). Only the first 5 chars of the SHA-1 hash leave the
 * server; the full password is never transmitted. Fails open if the API is down.
 */

declare(strict_types=1);

final class Pwned
{
    /** Returns true if the password appears in known breach corpora. */
    public static function isCompromised(string $password): bool
    {
        $sha1 = strtoupper(sha1($password));
        $prefix = substr($sha1, 0, 5);
        $suffix = substr($sha1, 5);

        $body = ApiClient::get("https://api.pwnedpasswords.com/range/{$prefix}", 4);
        if ($body === null) {
            return false; // fail open — never block account ops on an upstream outage
        }
        foreach (preg_split('/\r\n|\n/', $body) as $line) {
            $parts = explode(':', trim($line));
            if (isset($parts[0]) && hash_equals($suffix, $parts[0])) {
                return true;
            }
        }
        return false;
    }
}

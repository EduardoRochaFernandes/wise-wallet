<?php
/**
 * WiseWallet 2.0 — Brute-force protection for authentication.
 *
 * Failed login attempts are recorded per (identifier, ip). When the count in
 * the rolling window exceeds LOGIN_MAX_ATTEMPTS the account/ip is locked out
 * for LOGIN_LOCKOUT seconds. Successful logins clear the counter.
 */

declare(strict_types=1);

final class RateLimit
{
    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /** Number of seconds the caller must wait, or 0 if not locked out. */
    public static function lockedFor(string $identifier): int
    {
        $max      = (int) ww_config('LOGIN_MAX_ATTEMPTS', 5);
        $lockout  = (int) ww_config('LOGIN_LOCKOUT', 900);
        $ip       = self::clientIp();

        $row = Database::one(
            "SELECT COUNT(*) AS attempts, MAX(attempted_at) AS last_at
               FROM login_attempts
              WHERE success = 0
                AND (identifier = ? OR ip = ?)
                AND attempted_at > (NOW() - INTERVAL ? SECOND)",
            [$identifier, $ip, $lockout]
        );

        $attempts = (int) ($row['attempts'] ?? 0);
        if ($attempts < $max) {
            return 0;
        }
        $lastAt = strtotime((string) ($row['last_at'] ?? 'now'));
        $remaining = ($lastAt + $lockout) - time();
        return max($remaining, 0);
    }

    public static function record(string $identifier, bool $success): void
    {
        Database::run(
            "INSERT INTO login_attempts (identifier, ip, success, attempted_at)
             VALUES (?, ?, ?, NOW())",
            [$identifier, self::clientIp(), $success ? 1 : 0]
        );

        if ($success) {
            // Reset the counter for this identifier on a clean login.
            Database::run(
                "DELETE FROM login_attempts WHERE identifier = ? AND success = 0",
                [$identifier]
            );
        }
    }
}

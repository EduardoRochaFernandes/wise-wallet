<?php
/**
 * WiseWallet 2.0 — Security audit log.
 * Records security-relevant events (logins, failures, admin actions) for the
 * admin "Logs & auditoria" panel.
 */

declare(strict_types=1);

final class Audit
{
    public static function log(string $action, ?int $userId = null, array $meta = []): void
    {
        try {
            Database::run(
                "INSERT INTO audit_log (user_id, action, ip, user_agent, meta, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [
                    $userId,
                    $action,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                ]
            );
        } catch (Throwable $e) {
            // Auditing must never break the request.
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}

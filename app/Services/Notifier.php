<?php
/**
 * WiseWallet 2.0 — unified notification dispatch.
 * Writes an in-app notification and, when the user has email notifications
 * enabled, also emails it via Mailer. De-duplicates so the same notification
 * (same user + title) is not sent again within 24h — important for checks
 * that run on every page load (bill reminders, budget warnings).
 */

declare(strict_types=1);

final class Notifier
{
    public static function send(int $uid, string $type, string $title, string $body, string $icon = 'bell'): void
    {
        $dup = Database::scalar(
            "SELECT id FROM notifications WHERE user_id=? AND title=? AND created_at > (NOW() - INTERVAL 1 DAY) LIMIT 1",
            [$uid, $title]
        );
        if ($dup) { return; }

        Database::run(
            "INSERT INTO notifications (user_id,type,title,body,icon,created_at) VALUES (?,?,?,?,?,NOW())",
            [$uid, $type, $title, $body, $icon]
        );

        $wantsEmail = (int) (Database::scalar("SELECT email_notifications FROM users WHERE id=?", [$uid]) ?? 1) === 1;
        if (!$wantsEmail) { return; }

        $email = Database::scalar("SELECT email FROM users WHERE id=?", [$uid]);
        if ($email) {
            Mailer::send((string) $email, $title, $body . "\n\n— WiseWallet");
        }
    }
}

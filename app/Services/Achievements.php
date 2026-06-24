<?php
/**
 * WiseWallet 2.0 — Gamification engine.
 * Evaluates achievement rules against the user's real data and unlocks any
 * newly-earned ones (awarding points + a notification).
 */

declare(strict_types=1);

final class Achievements
{
    /** All achievements with the user's unlocked state. */
    public static function all(int $uid): array
    {
        return Database::all(
            "SELECT a.*, ua.unlocked_at,
                    CASE WHEN ua.id IS NULL THEN 0 ELSE 1 END AS unlocked
               FROM achievements a
               LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ?
              ORDER BY (ua.id IS NULL), FIELD(a.rarity,'legendary','epic','rare','common'), a.points DESC",
            [$uid]
        );
    }

    /** Unlock by code if not already held. Returns the achievement row if newly unlocked. */
    public static function unlock(int $uid, string $code): ?array
    {
        $a = Database::one("SELECT * FROM achievements WHERE code = ?", [$code]);
        if (!$a) { return null; }
        $has = Database::scalar("SELECT id FROM user_achievements WHERE user_id=? AND achievement_id=?", [$uid, $a['id']]);
        if ($has) { return null; }

        Database::run("INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?,?,NOW())", [$uid, $a['id']]);
        Database::run("UPDATE users SET points = points + ? WHERE id = ?", [(int) $a['points'], $uid]);
        Notifier::send($uid, 'achievement', 'Achievement unlocked: ' . $a['name'], $a['name'] . ' — ' . $a['description'] . ' (+' . (int) $a['points'] . ' points)', 'trophy');
        return $a;
    }

    /** Lightweight trigger used right after creating a transaction. */
    public static function checkTransactions(int $uid): void
    {
        self::evaluate($uid);
    }

    /** Full evaluation of all rules. Returns codes newly unlocked. */
    public static function evaluate(int $uid): array
    {
        $newly = [];
        $unlock = function (string $code) use ($uid, &$newly) {
            if (self::unlock($uid, $code)) { $newly[] = $code; }
        };

        $txCount = (int) Database::scalar("SELECT COUNT(*) FROM transactions WHERE user_id=?", [$uid]);
        if ($txCount >= 1)   { $unlock('first_transaction'); }
        if ($txCount >= 10)  { $unlock('ten_transactions'); }
        if ($txCount >= 100) { $unlock('hundred_transactions'); }

        if ((int) Database::scalar("SELECT COUNT(*) FROM accounts WHERE user_id=?", [$uid]) >= 1) { $unlock('first_account'); }
        if ((int) Database::scalar("SELECT COUNT(*) FROM budgets WHERE user_id=?", [$uid]) >= 1)  { $unlock('first_budget'); }
        if ((int) Database::scalar("SELECT COUNT(*) FROM goals WHERE user_id=?", [$uid]) >= 1)    { $unlock('first_goal'); }

        if ((int) Database::scalar("SELECT COUNT(*) FROM goals WHERE user_id=? AND (status='completed' OR current_amount>=target_amount)", [$uid]) >= 1) { $unlock('goal_achieved'); }
        if ((int) Database::scalar("SELECT COUNT(*) FROM goals WHERE user_id=? AND status='completed' AND (name LIKE '%emerg%' OR name LIKE '%Emerg%')", [$uid]) >= 1) { $unlock('emergency_fund'); }

        if ((int) Database::scalar("SELECT COUNT(*) FROM investments WHERE user_id=?", [$uid]) >= 1) { $unlock('first_investment'); }
        if ((int) Database::scalar("SELECT COUNT(DISTINCT type) FROM investments WHERE user_id=?", [$uid]) >= 4) { $unlock('diversified'); }

        if ((int) Database::scalar("SELECT COUNT(*) FROM subscriptions WHERE user_id=?", [$uid]) >= 1) { $unlock('first_subscription'); }

        $nw = Finance::netWorth($uid);
        if ($nw >= 10000) { $unlock('net_worth_10k'); }

        $pendingBills = (int) Database::scalar("SELECT COUNT(*) FROM bills WHERE user_id=? AND status<>'paid'", [$uid]);
        $billCount = (int) Database::scalar("SELECT COUNT(*) FROM bills WHERE user_id=?", [$uid]);
        if ($billCount > 0 && $pendingBills === 0) { $unlock('debt_free'); }

        $summary = Finance::summary($uid);
        if ($summary['savings_rate'] >= 25) { $unlock('saver_25'); }

        if ((int) Database::scalar("SELECT COUNT(*) FROM accounts WHERE user_id=?", [$uid]) >= 3) { $unlock('multi_account'); }
        if ($nw >= 50000) { $unlock('big_saver'); }
        if ((int) Database::scalar("SELECT COUNT(*) FROM goals WHERE user_id=? AND status='completed'", [$uid]) >= 3) { $unlock('goal_master'); }
        if ((int) Database::scalar("SELECT COUNT(*) FROM bills WHERE user_id=? AND status='paid'", [$uid]) >= 10) { $unlock('bill_payer'); }
        if (Database::scalar("SELECT id FROM audit_log WHERE user_id=? AND action='2fa_enabled' LIMIT 1", [$uid])) { $unlock('security_pro'); }
        if ((int) Database::scalar("SELECT COUNT(*) FROM subscriptions WHERE user_id=? AND is_active=0", [$uid]) >= 1) { $unlock('subscription_trimmed'); }
        if (Database::scalar("SELECT id FROM audit_log WHERE user_id=? AND action='export' LIMIT 1", [$uid])) { $unlock('exporter'); }
        if ((int) Database::scalar("SELECT COUNT(*) FROM investments WHERE user_id=?", [$uid]) >= 5) { $unlock('investor_5'); }

        return $newly;
    }
}

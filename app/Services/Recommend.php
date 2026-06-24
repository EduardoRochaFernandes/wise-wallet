<?php
/**
 * WiseWallet 2.0 — personalised article recommendations.
 *
 * Reads simple signals from the user's own financial data (income, savings
 * rate, overdue bills, investments, subscriptions...) and maps each one to a
 * relevant article category/topic. No ML — just transparent, explainable
 * rules, each with a short reason shown to the user.
 */

declare(strict_types=1);

final class Recommend
{
    /**
     * Returns up to $limit articles, each with an added 'reason' string,
     * most relevant first. Falls back to latest articles if no signal fires
     * or the user has too little data yet.
     */
    public static function articlesFor(int $uid, int $limit = 3): array
    {
        $signals = self::signals($uid);
        $picked = [];
        $usedSlugs = [];

        foreach ($signals as [$slugPattern, $reason]) {
            if (count($picked) >= $limit) { break; }
            $a = Database::one(
                "SELECT id, slug, title, excerpt, reading_minutes
                   FROM articles
                  WHERE status = 'published' AND slug LIKE ?
                  ORDER BY published_at DESC LIMIT 1",
                [$slugPattern]
            );
            if ($a && !in_array($a['slug'], $usedSlugs, true)) {
                $a['reason'] = $reason;
                $picked[] = $a;
                $usedSlugs[] = $a['slug'];
            }
        }

        if (count($picked) < $limit) {
            $more = Database::all(
                "SELECT id, slug, title, excerpt, reading_minutes FROM articles
                  WHERE status = 'published'" . ($usedSlugs ? " AND slug NOT IN (" . implode(',', array_fill(0, count($usedSlugs), '?')) . ")" : "") . "
                  ORDER BY published_at DESC LIMIT ?",
                array_merge($usedSlugs, [$limit - count($picked)])
            );
            foreach ($more as $a) {
                $a['reason'] = 'Recently published';
                $picked[] = $a;
            }
        }

        return $picked;
    }

    /** Ordered list of [slug LIKE pattern, human reason] — first match wins per article. */
    private static function signals(int $uid): array
    {
        $s = Finance::summary($uid);
        $out = [];

        $overdue = (int) Database::scalar("SELECT COUNT(*) FROM bills WHERE user_id=? AND status='overdue'", [$uid]);
        if ($overdue > 0) {
            $out[] = ['%snowball-vs-avalanche%', "You have {$overdue} overdue bill(s) — a payoff strategy can help."];
        }

        if ($s['savings_rate'] < 10) {
            $out[] = ['%orcamento-base-zero%', 'Your savings rate is under 10% this month — a stricter budget can free up room.'];
            $out[] = ['%regra-50-30-20%', 'A simple framework to rebuild your savings rate.'];
        } elseif ($s['savings_rate'] < 20) {
            $out[] = ['%inflacao-de-estilo-de-vida%', 'You are saving, but lifestyle inflation is the most common reason progress stalls.'];
        }

        $emergencyGoal = Database::scalar(
            "SELECT id FROM goals WHERE user_id=? AND status='active' AND (name LIKE '%emerg%' OR name LIKE '%Emerg%') LIMIT 1", [$uid]);
        $hasEmergencyFund = Database::scalar(
            "SELECT id FROM goals WHERE user_id=? AND (name LIKE '%emerg%' OR name LIKE '%Emerg%') AND status='completed' LIMIT 1", [$uid]);
        if (!$hasEmergencyFund) {
            $out[] = ['%fundo-de-emergencia%', $emergencyGoal ? 'You have an emergency-fund goal in progress — here is where to keep it.' : "You don't have an emergency fund goal yet."];
            $out[] = ['%onde-guardar-fundo-emergencia%', 'Practical guidance on where to actually hold that fund.'];
        }

        $invCount = (int) Database::scalar("SELECT COUNT(*) FROM investments WHERE user_id=?", [$uid]);
        $invTypes = (int) Database::scalar("SELECT COUNT(DISTINCT type) FROM investments WHERE user_id=?", [$uid]);
        if ($invCount === 0) {
            $out[] = ['%poder-juros-compostos%', "You haven't recorded any investments yet — here is why starting early matters most."];
            $out[] = ['%etf-vs-acoes-individuais%', 'A beginner-friendly comparison to help you take the first step.'];
        } elseif ($invTypes < 3) {
            $out[] = ['%diversificacao-sem-jargao%', 'Your portfolio is concentrated in few asset types — diversification, explained simply.'];
        }

        $subTotal = (float) (Database::scalar(
            "SELECT COALESCE(SUM(CASE WHEN billing_cycle='yearly' THEN amount/12 ELSE amount END),0)
               FROM subscriptions WHERE user_id=? AND is_active=1", [$uid]) ?? 0);
        if ($subTotal > 40) {
            $out[] = ['%orcamento-base-zero%', 'Your subscriptions add up to ' . number_format($subTotal, 0) . '€/month — zero-based budgeting catches costs like this.'];
        }

        $hasCreditCard = Database::scalar("SELECT id FROM accounts WHERE user_id=? AND type='credit' AND balance < 0 AND deleted_at IS NULL LIMIT 1", [$uid]);
        if ($hasCreditCard) {
            $out[] = ['%como-funciona-credit-score%', 'You are carrying a credit card balance — understanding your score helps you manage it.'];
        }

        $out[] = ['%inflacao-poupanca%', 'A reminder that idle cash quietly loses value over time.'];

        return $out;
    }
}

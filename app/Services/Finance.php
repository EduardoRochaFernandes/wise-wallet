<?php
/**
 * WiseWallet 2.0 — Finance domain service.
 * Central, reusable read/write queries used by pages and the JSON API.
 * Every method is scoped to a user id (ownership enforced at the query level).
 */

declare(strict_types=1);

final class Finance
{
    /* ── Accounts ─────────────────────────────────────────────── */
    public static function accounts(int $uid, bool $includeArchived = false): array
    {
        $sql = "SELECT * FROM accounts WHERE user_id = ?" . ($includeArchived ? '' : " AND is_archived = 0") . " ORDER BY id";
        return Database::all($sql, [$uid]);
    }

    public static function netWorth(int $uid): float
    {
        return (float) (Database::scalar("SELECT COALESCE(SUM(balance),0) FROM accounts WHERE user_id = ? AND is_archived = 0", [$uid]) ?? 0);
    }

    /* ── Categories (system + user) ───────────────────────────── */
    public static function categories(int $uid, ?string $type = null): array
    {
        $params = [$uid];
        $sql = "SELECT * FROM categories WHERE (user_id IS NULL OR user_id = ?)";
        if ($type) { $sql .= " AND type = ?"; $params[] = $type; }
        $sql .= " ORDER BY type, name";
        return Database::all($sql, $params);
    }

    /* ── Transactions ─────────────────────────────────────────── */
    public static function transactions(int $uid, array $f = [], int $limit = 25, int $offset = 0): array
    {
        [$where, $params] = self::txFilters($uid, $f);
        $params[] = $limit; $params[] = $offset;
        return Database::all(
            "SELECT t.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                    a.name AS account_name, a2.name AS to_account_name
               FROM transactions t
               LEFT JOIN categories c ON c.id = t.category_id
               LEFT JOIN accounts a  ON a.id = t.account_id
               LEFT JOIN accounts a2 ON a2.id = t.to_account_id
              WHERE $where
              ORDER BY t.occurred_on DESC, t.id DESC
              LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function transactionsCount(int $uid, array $f = []): int
    {
        [$where, $params] = self::txFilters($uid, $f);
        return (int) Database::scalar("SELECT COUNT(*) FROM transactions t WHERE $where", $params);
    }

    private static function txFilters(int $uid, array $f): array
    {
        $where = "t.user_id = ?"; $params = [$uid];
        if (!empty($f['type']))        { $where .= " AND t.type = ?";        $params[] = $f['type']; }
        if (!empty($f['account_id']))  { $where .= " AND t.account_id = ?";  $params[] = (int) $f['account_id']; }
        if (!empty($f['category_id'])) { $where .= " AND t.category_id = ?"; $params[] = (int) $f['category_id']; }
        if (!empty($f['from']))        { $where .= " AND t.occurred_on >= ?"; $params[] = $f['from']; }
        if (!empty($f['to']))          { $where .= " AND t.occurred_on <= ?"; $params[] = $f['to']; }
        if (!empty($f['q']))           { $where .= " AND (t.description LIKE ? OR t.notes LIKE ?)"; $params[] = '%' . $f['q'] . '%'; $params[] = '%' . $f['q'] . '%'; }
        return [$where, $params];
    }

    public static function createTransaction(int $uid, array $d): int
    {
        $type = $d['type'] ?? 'expense';
        $amount = round((float) ($d['amount'] ?? 0), 2);
        $accId = (int) ($d['account_id'] ?? 0);
        $toAcc = $type === 'transfer' ? (int) ($d['to_account_id'] ?? 0) : null;
        $catId = $type === 'transfer' ? null : (((int) ($d['category_id'] ?? 0)) ?: null);

        // Ownership of referenced accounts.
        self::assertOwnsAccount($uid, $accId);
        if ($toAcc) { self::assertOwnsAccount($uid, $toAcc); }

        Database::begin();
        try {
            $id = Database::insert(
                "INSERT INTO transactions (user_id, account_id, to_account_id, category_id, type, amount, description, notes, occurred_on, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,NOW())",
                [$uid, $accId, $toAcc, $catId, $type, $amount,
                 mb_substr((string) ($d['description'] ?? ''), 0, 255),
                 ($d['notes'] ?? '') !== '' ? $d['notes'] : null,
                 $d['occurred_on'] ?? date('Y-m-d')]
            );
            self::applyBalance($accId, $toAcc, $type, $amount, +1);
            Database::commit();
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
        Achievements::checkTransactions($uid);
        return $id;
    }

    public static function deleteTransaction(int $uid, int $id): bool
    {
        $t = Database::one("SELECT * FROM transactions WHERE id = ? AND user_id = ?", [$id, $uid]);
        if (!$t) { return false; }
        Database::begin();
        try {
            self::applyBalance((int) $t['account_id'], $t['to_account_id'] ? (int) $t['to_account_id'] : null, $t['type'], (float) $t['amount'], -1);
            Database::run("DELETE FROM transactions WHERE id = ? AND user_id = ?", [$id, $uid]);
            Database::commit();
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
        return true;
    }

    /** Apply (dir=+1) or reverse (dir=-1) a transaction's effect on balances. */
    private static function applyBalance(int $accId, ?int $toAcc, string $type, float $amount, int $dir): void
    {
        $delta = $amount * $dir;
        if ($type === 'income') {
            Database::run("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$delta, $accId]);
        } elseif ($type === 'expense') {
            Database::run("UPDATE accounts SET balance = balance - ? WHERE id = ?", [$delta, $accId]);
        } else { // transfer
            Database::run("UPDATE accounts SET balance = balance - ? WHERE id = ?", [$delta, $accId]);
            if ($toAcc) { Database::run("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$delta, $toAcc]); }
        }
    }

    private static function assertOwnsAccount(int $uid, int $accId): void
    {
        $ok = Database::scalar("SELECT id FROM accounts WHERE id = ? AND user_id = ?", [$accId, $uid]);
        if (!$ok) { throw new RuntimeException('Conta inválida.'); }
    }

    /* ── Dashboard summary ────────────────────────────────────── */
    public static function summary(int $uid): array
    {
        $monthStart = date('Y-m-01');
        $lastStart = date('Y-m-01', strtotime('first day of last month'));
        $lastEnd = date('Y-m-t', strtotime('last day of last month'));

        $sum = fn(string $type, string $a, string $b) => (float) Database::scalar(
            "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type=? AND occurred_on BETWEEN ? AND ?",
            [$uid, $type, $a, $b]
        );
        $incThis = $sum('income', $monthStart, date('Y-m-d'));
        $expThis = $sum('expense', $monthStart, date('Y-m-d'));
        $incLast = $sum('income', $lastStart, $lastEnd);
        $expLast = $sum('expense', $lastStart, $lastEnd);

        $savings = $incThis > 0 ? max(0, ($incThis - $expThis) / $incThis) * 100 : 0;
        $pct = fn($now, $prev) => $prev > 0 ? (($now - $prev) / $prev) * 100 : ($now > 0 ? 100 : 0);

        $upcoming = Database::all(
            "SELECT name, amount, due_date, status FROM bills
              WHERE user_id=? AND status<>'paid' ORDER BY due_date ASC LIMIT 5", [$uid]);

        return [
            'net_worth'    => self::netWorth($uid),
            'income_month' => $incThis,
            'expense_month'=> $expThis,
            'income_delta' => round($pct($incThis, $incLast), 1),
            'expense_delta'=> round($pct($expThis, $expLast), 1),
            'savings_rate' => round($savings, 1),
            'upcoming'     => $upcoming,
        ];
    }

    /* ── Analytics datasets ───────────────────────────────────── */
    public static function monthlyCashflow(int $uid, int $months = 12): array
    {
        $rows = Database::all(
            "SELECT DATE_FORMAT(occurred_on,'%Y-%m') ym,
                    SUM(CASE WHEN type='income' THEN amount ELSE 0 END) inc,
                    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) exp
               FROM transactions
              WHERE user_id=? AND occurred_on >= (CURDATE() - INTERVAL ? MONTH)
              GROUP BY ym ORDER BY ym", [$uid, $months]);
        $map = [];
        foreach ($rows as $r) { $map[$r['ym']] = $r; }
        $labels = []; $inc = []; $exp = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $labels[] = date('M', strtotime($ym . '-01'));
            $inc[] = round((float) ($map[$ym]['inc'] ?? 0), 2);
            $exp[] = round((float) ($map[$ym]['exp'] ?? 0), 2);
        }
        return ['labels' => $labels, 'income' => $inc, 'expense' => $exp];
    }

    public static function byCategory(int $uid, string $type, int $days = 30): array
    {
        $rows = Database::all(
            "SELECT COALESCE(c.name,'Sem categoria') name, c.color, SUM(t.amount) total
               FROM transactions t LEFT JOIN categories c ON c.id=t.category_id
              WHERE t.user_id=? AND t.type=? AND t.occurred_on >= (CURDATE() - INTERVAL ? DAY)
              GROUP BY t.category_id ORDER BY total DESC", [$uid, $type, $days]);
        return [
            'labels' => array_map(fn($r) => $r['name'], $rows),
            'values' => array_map(fn($r) => round((float) $r['total'], 2), $rows),
            'colors' => array_map(fn($r) => $r['color'] ?? '#64748b', $rows),
        ];
    }

    public static function spendByWeekday(int $uid, int $days = 90): array
    {
        $rows = Database::all(
            "SELECT DAYOFWEEK(occurred_on) dow, SUM(amount) total FROM transactions
              WHERE user_id=? AND type='expense' AND occurred_on >= (CURDATE() - INTERVAL ? DAY)
              GROUP BY dow", [$uid, $days]);
        $byDow = array_fill(1, 7, 0.0);
        foreach ($rows as $r) { $byDow[(int) $r['dow']] = round((float) $r['total'], 2); }
        // MySQL DAYOFWEEK: 1=Sun..7=Sat → reorder to Mon..Sun
        $order = [2, 3, 4, 5, 6, 7, 1];
        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        return ['labels' => $labels, 'values' => array_map(fn($d) => $byDow[$d], $order)];
    }

    public static function topExpenses(int $uid, int $limit = 5, int $days = 30): array
    {
        return Database::all(
            "SELECT t.description, t.amount, t.occurred_on, c.name category_name, c.color
               FROM transactions t LEFT JOIN categories c ON c.id=t.category_id
              WHERE t.user_id=? AND t.type='expense' AND t.occurred_on >= (CURDATE() - INTERVAL ? DAY)
              ORDER BY t.amount DESC LIMIT ?", [$uid, $days, $limit]);
    }

    /* ── Budgets with usage ───────────────────────────────────── */
    public static function budgets(int $uid): array
    {
        $rows = Database::all(
            "SELECT b.*, c.name category_name, c.color, c.icon
               FROM budgets b JOIN categories c ON c.id=b.category_id
              WHERE b.user_id=? ORDER BY c.name", [$uid]);
        $monthStart = date('Y-m-01');
        foreach ($rows as &$b) {
            $spent = (float) Database::scalar(
                "SELECT COALESCE(SUM(amount),0) FROM transactions
                  WHERE user_id=? AND category_id=? AND type='expense' AND occurred_on >= ?",
                [$uid, $b['category_id'], $monthStart]);
            $b['spent'] = round($spent, 2);
            $b['pct'] = $b['amount'] > 0 ? min(100, round($spent / $b['amount'] * 100)) : 0;
            $b['status'] = $b['pct'] >= 100 ? 'over' : ($b['pct'] >= 80 ? 'warn' : 'ok');
        }
        return $rows;
    }

    /* ── Goals ────────────────────────────────────────────────── */
    public static function goals(int $uid): array
    {
        $rows = Database::all("SELECT * FROM goals WHERE user_id=? ORDER BY status, deadline IS NULL, deadline", [$uid]);
        foreach ($rows as &$g) {
            $g['pct'] = $g['target_amount'] > 0 ? min(100, round($g['current_amount'] / $g['target_amount'] * 100)) : 0;
            $g['days_left'] = $g['deadline'] ? (int) floor((strtotime($g['deadline']) - time()) / 86400) : null;
        }
        return $rows;
    }

    /* ── Bills (auto-flag overdue) ────────────────────────────── */
    public static function bills(int $uid): array
    {
        Database::run("UPDATE bills SET status='overdue' WHERE user_id=? AND status='pending' AND due_date < CURDATE()", [$uid]);
        return Database::all(
            "SELECT b.*, c.name category_name FROM bills b LEFT JOIN categories c ON c.id=b.category_id
              WHERE b.user_id=? ORDER BY FIELD(b.status,'overdue','pending','paid'), b.due_date", [$uid]);
    }

    /* ── Subscriptions (normalized monthly/yearly) ────────────── */
    public static function subscriptions(int $uid): array
    {
        $rows = Database::all("SELECT * FROM subscriptions WHERE user_id=? ORDER BY is_active DESC, next_renewal", [$uid]);
        $monthly = 0.0; $yearly = 0.0;
        foreach ($rows as $s) {
            if (!$s['is_active']) { continue; }
            $m = $s['billing_cycle'] === 'yearly' ? $s['amount'] / 12 : (float) $s['amount'];
            $monthly += $m; $yearly += $m * 12;
        }
        return ['items' => $rows, 'monthly' => round($monthly, 2), 'yearly' => round($yearly, 2)];
    }

    /* ── Investments (gain/loss, ROI, diversification) ────────── */
    public static function investments(int $uid): array
    {
        $rows = Database::all("SELECT * FROM investments WHERE user_id=? ORDER BY type, name", [$uid]);
        $cost = 0.0; $value = 0.0; $byType = [];
        foreach ($rows as &$i) {
            $cur = $i['current_price'] !== null ? (float) $i['current_price'] : (float) $i['buy_price'];
            $c = (float) $i['quantity'] * (float) $i['buy_price'];
            $v = (float) $i['quantity'] * $cur;
            $i['cost'] = round($c, 2);
            $i['value'] = round($v, 2);
            $i['gain'] = round($v - $c, 2);
            $i['roi'] = $c > 0 ? round(($v - $c) / $c * 100, 2) : 0;
            $cost += $c; $value += $v;
            $byType[$i['type']] = ($byType[$i['type']] ?? 0) + $v;
        }
        return [
            'items' => $rows,
            'cost' => round($cost, 2),
            'value' => round($value, 2),
            'gain' => round($value - $cost, 2),
            'roi' => $cost > 0 ? round(($value - $cost) / $cost * 100, 2) : 0,
            'by_type' => $byType,
        ];
    }

    public static function netWorthSeries(int $uid): array
    {
        $rows = Database::all("SELECT captured_on, net_worth FROM net_worth_snapshots WHERE user_id=? ORDER BY captured_on", [$uid]);
        return [
            'labels' => array_map(fn($r) => date('M', strtotime($r['captured_on'])), $rows),
            'values' => array_map(fn($r) => round((float) $r['net_worth'], 2), $rows),
        ];
    }

    /* ── Financial Health Score (0..100) ──────────────────────── */
    public static function healthScore(int $uid): array
    {
        $s = self::summary($uid);
        // 1) Savings rate → 30 pts (25%+ = full)
        $savings = min(30, ($s['savings_rate'] / 25) * 30);

        // 2) Budgets within limit → 25 pts
        $budgets = self::budgets($uid);
        $within = array_filter($budgets, fn($b) => $b['status'] !== 'over');
        $budgetScore = count($budgets) ? (count($within) / count($budgets)) * 25 : 15;

        // 3) Active goals progress → 20 pts
        $goals = array_filter(self::goals($uid), fn($g) => $g['status'] === 'active');
        $goalScore = count($goals) ? (array_sum(array_map(fn($g) => $g['pct'], $goals)) / count($goals)) / 100 * 20 : 10;

        // 4) Diversification → 15 pts (5 distinct types = full)
        $inv = self::investments($uid);
        $divScore = min(15, count($inv['by_type']) / 5 * 15);

        // 5) Net worth positive & no overdue bills → 10 pts
        $overdue = (int) Database::scalar("SELECT COUNT(*) FROM bills WHERE user_id=? AND status='overdue'", [$uid]);
        $stability = ($s['net_worth'] > 0 ? 6 : 0) + ($overdue === 0 ? 4 : 0);

        $components = [
            ['label' => 'Savings rate',    'value' => round($savings, 1), 'max' => 30],
            ['label' => 'Budgets',         'value' => round($budgetScore, 1), 'max' => 25],
            ['label' => 'Goals',           'value' => round($goalScore, 1), 'max' => 20],
            ['label' => 'Diversification', 'value' => round($divScore, 1), 'max' => 15],
            ['label' => 'Stability',       'value' => round($stability, 1), 'max' => 10],
        ];
        $score = (int) round($savings + $budgetScore + $goalScore + $divScore + $stability);
        return ['score' => max(0, min(100, $score)), 'components' => $components];
    }
}

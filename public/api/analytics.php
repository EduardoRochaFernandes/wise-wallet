<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();

$health = Finance::healthScore($uid);
if ($health['score'] >= 90) { Achievements::unlock($uid, 'score_90'); }
$inv = Finance::investments($uid);

json_out([
    'cashflow'      => Finance::monthlyCashflow($uid, 12),
    'expense_cat'   => Finance::byCategory($uid, 'expense', 30),
    'income_cat'    => Finance::byCategory($uid, 'income', 90),
    'weekday'       => Finance::spendByWeekday($uid, 90),
    'top_expenses'  => Finance::topExpenses($uid, 5, 30),
    'health'        => $health,
    'net_worth'     => Finance::netWorthSeries($uid),
    'diversification' => [
        'labels' => array_keys($inv['by_type']),
        'values' => array_map(fn($v) => round((float) $v, 2), array_values($inv['by_type'])),
    ],
]);

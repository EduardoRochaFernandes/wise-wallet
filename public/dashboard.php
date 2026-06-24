<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();

$s = Finance::summary($uid);
$health = Finance::healthScore($uid);
$cashflow = Finance::monthlyCashflow($uid, 12);
$expenseCat = Finance::byCategory($uid, 'expense', 30);
$recent = Finance::transactions($uid, [], 6, 0);
$budgets = array_slice(Finance::budgets($uid), 0, 4);
$goals = array_slice(array_filter(Finance::goals($uid), fn($g) => $g['status'] === 'active'), 0, 3);
$txCount = Finance::transactionsCount($uid);

$payload = ['cashflow' => $cashflow, 'expenseCat' => $expenseCat, 'health' => $health['score'], 'empty' => $txCount === 0];

$title = 'Dashboard';
$nav = 'dashboard';
require __DIR__ . '/../app/views/partials/app_head.php';

$delta = function (float $v): string {
    $cls = $v >= 0 ? 'text-pos' : 'text-neg';
    $sign = $v >= 0 ? '+' : '−';
    return "<span class=\"$cls text-sm font-medium\">$sign" . number_format(abs($v), 1, '.', ',') . '%</span>';
};
?>
<!-- Stat row -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-px mb-6 rounded-lg overflow-hidden border" style="border-color:rgb(var(--line))">
  <div class="card-pad" style="background:rgb(var(--surface))">
    <div class="text-soft text-xs uppercase tracking-wide">Net worth</div>
    <div class="font-display text-2xl font-semibold mt-1 amount sensitive"><?= money($s['net_worth']) ?></div>
    <div class="text-soft text-xs mt-1">Across all accounts</div>
  </div>
  <div class="card-pad" style="background:rgb(var(--surface))">
    <div class="text-soft text-xs uppercase tracking-wide">Income · month</div>
    <div class="font-display text-2xl font-semibold mt-1 text-pos amount sensitive"><?= money($s['income_month']) ?></div>
    <div class="mt-1"><?= $delta($s['income_delta']) ?> <span class="text-soft text-xs">vs last month</span></div>
  </div>
  <div class="card-pad" style="background:rgb(var(--surface))">
    <div class="text-soft text-xs uppercase tracking-wide">Expenses · month</div>
    <div class="font-display text-2xl font-semibold mt-1 text-neg amount sensitive"><?= money($s['expense_month']) ?></div>
    <div class="mt-1"><?= $delta(-$s['expense_delta']) ?> <span class="text-soft text-xs">vs last month</span></div>
  </div>
  <div class="card-pad" style="background:rgb(var(--surface))">
    <div class="text-soft text-xs uppercase tracking-wide">Savings rate</div>
    <div class="font-display text-2xl font-semibold mt-1"><?= number_format($s['savings_rate'], 1, '.', ',') ?>%</div>
    <div class="progress mt-3"><span style="width:<?= min(100, $s['savings_rate']) ?>%"></span></div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
  <div class="card card-pad lg:col-span-2">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-display font-semibold text-lg">Cash flow · 12 months</h2>
      <a href="/analytics" class="btn-ghost btn-sm">View insights</a>
    </div>
    <div id="chart-cashflow"></div>
  </div>
  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-1">Financial health</h2>
    <div id="chart-health"></div>
    <div class="space-y-2 mt-2" <?= $txCount === 0 ? 'hidden' : '' ?>>
      <?php foreach ($health['components'] as $c): ?>
        <div>
          <div class="flex justify-between text-xs mb-1"><span class="text-soft"><?= e($c['label']) ?></span><span class="amount"><?= $c['value'] ?>/<?= $c['max'] ?></span></div>
          <div class="progress"><span style="width:<?= $c['max'] ? ($c['value'] / $c['max'] * 100) : 0 ?>%"></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
  <div class="card card-pad lg:col-span-2">
    <div class="flex items-center justify-between mb-3">
      <h2 class="font-display font-semibold text-lg">Recent transactions</h2>
      <a href="/transactions" class="btn-ghost btn-sm">View all</a>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <tbody>
        <?php foreach ($recent as $t): $exp = $t['type'] === 'expense'; ?>
          <tr>
            <td>
              <div class="font-medium"><?= e($t['description'] ?: ($t['category_name'] ?? 'Transaction')) ?></div>
              <div class="text-xs text-soft"><?= e($t['category_name'] ?? ($t['type'] === 'transfer' ? 'Transfer' : '—')) ?> · <?= e($t['account_name']) ?></div>
            </td>
            <td class="text-right text-xs text-soft whitespace-nowrap"><?= date('d M', strtotime($t['occurred_on'])) ?></td>
            <td class="text-right font-medium whitespace-nowrap amount <?= $exp ? 'text-neg' : ($t['type'] === 'income' ? 'text-pos' : '') ?>">
              <span class="sensitive"><?= ($exp ? '−' : ($t['type'] === 'income' ? '+' : '')) . money($t['amount']) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td class="text-soft text-center py-8">No transactions yet — use the <strong>Add</strong> button to record one.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-2">Spending by category</h2>
    <div id="chart-expense"></div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
  <div class="card card-pad">
    <div class="flex items-center justify-between mb-3"><h2 class="font-display font-semibold text-lg">Budgets</h2><a href="/budgets" class="btn-ghost btn-sm">Manage</a></div>
    <?php foreach ($budgets as $b): $col = $b['status'] === 'over' ? 'var(--neg)' : ($b['status'] === 'warn' ? 'var(--warn)' : 'var(--accent)'); ?>
      <div class="mb-3">
        <div class="flex justify-between text-sm mb-1"><span><?= e($b['category_name']) ?></span><span class="text-soft amount sensitive"><?= money($b['spent']) ?> / <?= money($b['amount']) ?></span></div>
        <div class="progress"><span style="width:<?= $b['pct'] ?>%;background:rgb(<?= $col ?>)"></span></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$budgets): ?><p class="text-soft text-sm py-4">No budgets yet. <a href="/budgets" class="text-accent">Create your first</a>.</p><?php endif; ?>
  </div>
  <div class="card card-pad">
    <div class="flex items-center justify-between mb-3"><h2 class="font-display font-semibold text-lg">Goals</h2><a href="/goals" class="btn-ghost btn-sm">Manage</a></div>
    <?php foreach ($goals as $g): ?>
      <div class="mb-3">
        <div class="flex justify-between text-sm mb-1"><span><?= e($g['name']) ?></span><span class="text-soft amount"><?= $g['pct'] ?>%</span></div>
        <div class="progress"><span style="width:<?= $g['pct'] ?>%"></span></div>
        <div class="text-xs text-soft mt-1 sensitive"><?= money($g['current_amount']) ?> of <?= money($g['target_amount']) ?><?= $g['days_left'] !== null ? ' · ' . max(0, $g['days_left']) . ' days left' : '' ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$goals): ?><p class="text-soft text-sm py-4">No active goals. <a href="/goals" class="text-accent">Set one</a>.</p><?php endif; ?>
  </div>
</div>

<script type="application/json" id="dash-data" <?= nonce_attr() ?>><?= json_encode($payload, JSON_UNESCAPED_UNICODE) ?></script>
<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function () {
  const D = JSON.parse(document.getElementById('dash-data').textContent);
  if (D.empty) {
    WW.emptyState('#chart-cashflow', { title: 'No activity yet', text: 'Record your first transaction to see a 12-month cash flow.', cta: 'Add a transaction', href: '/transactions.php' });
    WW.emptyState('#chart-health', { title: 'Score pending', text: 'Add transactions, budgets and goals to compute your Financial Health Score.', cta: 'Get started', href: '/transactions.php' });
    WW.emptyState('#chart-expense', { title: 'No spending', text: 'Your spending by category will appear here.', cta: 'Add an expense', href: '/transactions.php' });
    return;
  }
  WW.areaChart('#chart-cashflow', D.cashflow.labels, [
    { name: 'Income', data: D.cashflow.income },
    { name: 'Expenses', data: D.cashflow.expense },
  ]);
  WW.gaugeChart('#chart-health', D.health);
  if (D.expenseCat.values.length) WW.donutChart('#chart-expense', D.expenseCat.labels, D.expenseCat.values);
  else WW.emptyState('#chart-expense', { title: 'No spending this month', text: 'Record expenses to see the category breakdown.', cta: 'Add an expense', href: '/transactions.php' });
});
</script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

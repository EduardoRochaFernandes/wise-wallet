<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();

$data = [
    'cashflow'  => Finance::monthlyCashflow($uid, 12),
    'expense'   => Finance::byCategory($uid, 'expense', 30),
    'income'    => Finance::byCategory($uid, 'income', 90),
    'weekday'   => Finance::spendByWeekday($uid, 90),
    'networth'  => Finance::netWorthSeries($uid),
];
$health = Finance::healthScore($uid);
if ($health['score'] >= 90) { Achievements::unlock($uid, 'score_90'); }
$top = Finance::topExpenses($uid, 5, 30);

$title = 'Insights';
$nav = 'analytics';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid lg:grid-cols-3 gap-5 mb-5">
  <div class="card card-pad lg:col-span-2">
    <h2 class="font-display font-semibold text-lg mb-3">Cash flow · 12 months</h2>
    <div id="c-cash"></div>
  </div>
  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-1">Financial Health Score</h2>
    <div id="c-health"></div>
    <div class="space-y-2 mt-2">
      <?php foreach ($health['components'] as $c): ?>
        <div><div class="flex justify-between text-xs mb-1"><span class="text-soft"><?= e($c['label']) ?></span><span class="amount"><?= $c['value'] ?>/<?= $c['max'] ?></span></div>
        <div class="progress"><span style="width:<?= $c['max'] ? ($c['value'] / $c['max'] * 100) : 0 ?>%"></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-5">
  <div class="card card-pad"><h2 class="font-display font-semibold text-lg mb-3">Spending by category · 30 days</h2><div id="c-exp"></div></div>
  <div class="card card-pad"><h2 class="font-display font-semibold text-lg mb-3">Income by category · 90 days</h2><div id="c-inc"></div></div>
</div>

<div class="grid lg:grid-cols-2 gap-5">
  <div class="card card-pad"><h2 class="font-display font-semibold text-lg mb-3">Spending by weekday</h2><div id="c-week"></div></div>
  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-3">Top 5 expenses · 30 days</h2>
    <div class="space-y-2">
      <?php foreach ($top as $t): ?>
        <div class="flex items-center justify-between p-2.5 rounded" style="background:rgb(var(--surface-2))">
          <div><div class="font-medium text-sm"><?= e($t['description'] ?: 'Expense') ?></div><div class="text-xs text-soft"><?= e($t['category_name'] ?? '—') ?> · <?= date('d M', strtotime($t['occurred_on'])) ?></div></div>
          <div class="font-medium text-neg amount sensitive"><?= money($t['amount']) ?></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$top): ?><p class="text-soft text-sm py-6 text-center">No recent expenses.</p><?php endif; ?>
    </div>
  </div>
</div>

<div class="card card-pad mt-5"><h2 class="font-display font-semibold text-lg mb-3">Net worth over time</h2><div id="c-nw"></div></div>

<script type="application/json" id="an-data" <?= nonce_attr() ?>><?= json_encode($data + ['health' => $health['score'], 'empty' => Finance::transactionsCount($uid) === 0], JSON_UNESCAPED_UNICODE) ?></script>
<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function(){ const D = JSON.parse(document.getElementById('an-data').textContent);
  if (D.empty) {
    [['#c-cash','No cash flow'],['#c-health','Score pending'],['#c-exp','No spending'],['#c-inc','No income'],['#c-week','No spending'],['#c-nw','No history']].forEach(([sel,t]) =>
      WW.emptyState(sel, { title: t, text: 'Record transactions to unlock this analysis.', cta: 'Add a transaction', href: '/transactions.php' }));
    return;
  }
  WW.areaChart('#c-cash', D.cashflow.labels, [{name:'Income',data:D.cashflow.income},{name:'Expenses',data:D.cashflow.expense}]);
  WW.gaugeChart('#c-health', D.health);
  D.expense.values.length ? WW.donutChart('#c-exp', D.expense.labels, D.expense.values) : document.getElementById('c-exp').innerHTML='<p class="text-soft text-sm py-8 text-center">No data.</p>';
  D.income.values.length ? WW.barChart('#c-inc', D.income.labels, [{name:'Income',data:D.income.values}]) : document.getElementById('c-inc').innerHTML='<p class="text-soft text-sm py-8 text-center">No data.</p>';
  WW.barChart('#c-week', D.weekday.labels, [{name:'Spending',data:D.weekday.values}]);
  D.networth.values.length ? WW.areaChart('#c-nw', D.networth.labels, [{name:'Net worth',data:D.networth.values}]) : document.getElementById('c-nw').innerHTML='<p class="text-soft text-sm py-8 text-center">No data.</p>';
});
</script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

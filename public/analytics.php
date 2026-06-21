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

$title = 'Análise';
$nav = 'analytics';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid lg:grid-cols-3 gap-4 mb-4">
  <div class="card card-pad lg:col-span-2">
    <h2 class="font-bold text-lg mb-3">Fluxo de caixa · 12 meses</h2>
    <div id="c-cash"></div>
  </div>
  <div class="card card-pad">
    <h2 class="font-bold text-lg mb-1">Score de Saúde Financeira</h2>
    <div id="c-health"></div>
    <div class="space-y-2 mt-2">
      <?php foreach ($health['components'] as $c): ?>
        <div><div class="flex justify-between text-xs mb-1"><span class="text-soft"><?= e($c['label']) ?></span><span><?= $c['value'] ?>/<?= $c['max'] ?></span></div>
        <div class="progress"><span style="width:<?= $c['max'] ? ($c['value'] / $c['max'] * 100) : 0 ?>%;background:rgb(var(--brand))"></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mb-4">
  <div class="card card-pad"><h2 class="font-bold text-lg mb-3">Despesas por categoria · 30 dias</h2><div id="c-exp"></div></div>
  <div class="card card-pad"><h2 class="font-bold text-lg mb-3">Receitas por categoria · 90 dias</h2><div id="c-inc"></div></div>
</div>

<div class="grid lg:grid-cols-2 gap-4">
  <div class="card card-pad"><h2 class="font-bold text-lg mb-3">Gastos por dia da semana</h2><div id="c-week"></div></div>
  <div class="card card-pad">
    <h2 class="font-bold text-lg mb-3">Top 5 maiores despesas · 30 dias</h2>
    <div class="space-y-2">
      <?php foreach ($top as $t): ?>
        <div class="flex items-center justify-between p-2.5 rounded-xl" style="background:rgb(var(--surface-2))">
          <div><div class="font-medium text-sm"><?= e($t['description'] ?: 'Despesa') ?></div><div class="text-xs text-soft"><?= e($t['category_name'] ?? '—') ?> · <?= date('d/m', strtotime($t['occurred_on'])) ?></div></div>
          <div class="font-bold text-neg sensitive"><?= money($t['amount']) ?></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$top): ?><p class="text-soft text-sm py-6 text-center">Sem despesas recentes.</p><?php endif; ?>
    </div>
  </div>
</div>

<div class="card card-pad mt-4"><h2 class="font-bold text-lg mb-3">Evolução do património líquido</h2><div id="c-nw"></div></div>

<script type="application/json" id="an-data" <?= nonce_attr() ?>><?= json_encode($data + ['health' => $health['score']], JSON_UNESCAPED_UNICODE) ?></script>
<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function(){ const D = JSON.parse(document.getElementById('an-data').textContent);
  WW.areaChart('#c-cash', D.cashflow.labels, [{name:'Receitas',data:D.cashflow.income},{name:'Despesas',data:D.cashflow.expense}]);
  WW.gaugeChart('#c-health', D.health);
  D.expense.values.length ? WW.donutChart('#c-exp', D.expense.labels, D.expense.values) : document.getElementById('c-exp').innerHTML='<p class="text-soft text-sm py-8 text-center">Sem dados.</p>';
  D.income.values.length ? WW.barChart('#c-inc', D.income.labels, [{name:'Receitas',data:D.income.values}]) : document.getElementById('c-inc').innerHTML='<p class="text-soft text-sm py-8 text-center">Sem dados.</p>';
  WW.barChart('#c-week', D.weekday.labels, [{name:'Gastos',data:D.weekday.values}]);
  D.networth.values.length ? WW.areaChart('#c-nw', D.networth.labels, [{name:'Património',data:D.networth.values}]) : document.getElementById('c-nw').innerHTML='<p class="text-soft text-sm py-8 text-center">Sem dados.</p>';
});
</script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

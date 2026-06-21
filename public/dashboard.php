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

$payload = ['cashflow' => $cashflow, 'expenseCat' => $expenseCat, 'health' => $health['score']];

$title = 'Dashboard';
$nav = 'dashboard';
require __DIR__ . '/../app/views/partials/app_head.php';

$delta = function (float $v): string {
    $cls = $v >= 0 ? 'text-pos' : 'text-neg';
    $sign = $v >= 0 ? '↑' : '↓';
    return "<span class=\"$cls text-sm font-semibold\">$sign " . number_format(abs($v), 1, ',', ' ') . '%</span>';
};
?>
<!-- Stat cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
  <div class="stat-card">
    <div class="text-soft text-sm">Património líquido</div>
    <div class="text-3xl font-extrabold mt-1 sensitive"><?= money($s['net_worth']) ?></div>
    <div class="text-soft text-xs mt-2">Soma de todas as contas</div>
  </div>
  <div class="stat-card">
    <div class="text-soft text-sm">Receitas (mês)</div>
    <div class="text-3xl font-extrabold mt-1 text-pos sensitive"><?= money($s['income_month']) ?></div>
    <div class="mt-2"><?= $delta($s['income_delta']) ?> <span class="text-soft text-xs">vs mês anterior</span></div>
  </div>
  <div class="stat-card">
    <div class="text-soft text-sm">Despesas (mês)</div>
    <div class="text-3xl font-extrabold mt-1 text-neg sensitive"><?= money($s['expense_month']) ?></div>
    <div class="mt-2"><?= $delta(-$s['expense_delta']) ?> <span class="text-soft text-xs">vs mês anterior</span></div>
  </div>
  <div class="stat-card">
    <div class="text-soft text-sm">Taxa de poupança</div>
    <div class="text-3xl font-extrabold mt-1 text-brand-400"><?= number_format($s['savings_rate'], 1, ',', ' ') ?>%</div>
    <div class="progress mt-3"><span style="width:<?= min(100, $s['savings_rate']) ?>%;background:rgb(var(--brand))"></span></div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
  <!-- Cashflow -->
  <div class="card card-pad lg:col-span-2">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-bold text-lg">Fluxo de caixa · 12 meses</h2>
      <a href="/analytics.php" class="btn-ghost btn-sm">Ver análise</a>
    </div>
    <div id="chart-cashflow"></div>
  </div>
  <!-- Health score -->
  <div class="card card-pad">
    <h2 class="font-bold text-lg mb-1">Saúde Financeira</h2>
    <div id="chart-health"></div>
    <div class="space-y-2 mt-2">
      <?php foreach ($health['components'] as $c): ?>
        <div>
          <div class="flex justify-between text-xs mb-1"><span class="text-soft"><?= e($c['label']) ?></span><span><?= $c['value'] ?>/<?= $c['max'] ?></span></div>
          <div class="progress"><span style="width:<?= $c['max'] ? ($c['value'] / $c['max'] * 100) : 0 ?>%;background:rgb(var(--brand))"></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <!-- Recent transactions -->
  <div class="card card-pad lg:col-span-2">
    <div class="flex items-center justify-between mb-3">
      <h2 class="font-bold text-lg">Transações recentes</h2>
      <a href="/transactions.php" class="btn-ghost btn-sm">Ver todas</a>
    </div>
    <div class="overflow-x-auto">
      <table class="table">
        <tbody>
        <?php foreach ($recent as $t): $exp = $t['type'] === 'expense'; ?>
          <tr>
            <td>
              <div class="font-medium"><?= e($t['description'] ?: ($t['category_name'] ?? 'Transação')) ?></div>
              <div class="text-xs text-soft"><?= e($t['category_name'] ?? ($t['type'] === 'transfer' ? 'Transferência' : '—')) ?> · <?= e($t['account_name']) ?></div>
            </td>
            <td class="text-right text-xs text-soft whitespace-nowrap"><?= date('d/m', strtotime($t['occurred_on'])) ?></td>
            <td class="text-right font-semibold whitespace-nowrap <?= $exp ? 'text-neg' : ($t['type'] === 'income' ? 'text-pos' : '') ?>">
              <span class="sensitive"><?= ($exp ? '−' : ($t['type'] === 'income' ? '+' : '')) . money($t['amount']) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td class="text-soft text-center py-8">Sem transações ainda. Usa o botão <strong>Adicionar</strong>.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Side: expense donut + budgets + goals -->
  <div class="space-y-4">
    <div class="card card-pad">
      <h2 class="font-bold text-lg mb-2">Despesas por categoria</h2>
      <div id="chart-expense"></div>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
  <!-- Budgets -->
  <div class="card card-pad">
    <div class="flex items-center justify-between mb-3"><h2 class="font-bold text-lg">Orçamentos</h2><a href="/budgets.php" class="btn-ghost btn-sm">Gerir</a></div>
    <?php foreach ($budgets as $b): $col = $b['status'] === 'over' ? 'var(--neg)' : ($b['status'] === 'warn' ? 'var(--warn)' : 'var(--pos)'); ?>
      <div class="mb-3">
        <div class="flex justify-between text-sm mb-1"><span><?= e($b['category_name']) ?></span><span class="text-soft sensitive"><?= money($b['spent']) ?> / <?= money($b['amount']) ?></span></div>
        <div class="progress"><span style="width:<?= $b['pct'] ?>%;background:rgb(<?= $col ?>)"></span></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$budgets): ?><p class="text-soft text-sm py-4">Sem orçamentos. <a href="/budgets.php" class="text-brand-400">Cria o primeiro</a>.</p><?php endif; ?>
  </div>
  <!-- Goals -->
  <div class="card card-pad">
    <div class="flex items-center justify-between mb-3"><h2 class="font-bold text-lg">Objetivos</h2><a href="/goals.php" class="btn-ghost btn-sm">Gerir</a></div>
    <?php foreach ($goals as $g): ?>
      <div class="mb-3">
        <div class="flex justify-between text-sm mb-1"><span><?= e($g['name']) ?></span><span class="text-soft"><?= $g['pct'] ?>%</span></div>
        <div class="progress"><span style="width:<?= $g['pct'] ?>%;background:<?= e($g['color']) ?>"></span></div>
        <div class="text-xs text-soft mt-1 sensitive"><?= money($g['current_amount']) ?> de <?= money($g['target_amount']) ?><?= $g['days_left'] !== null ? ' · ' . max(0, $g['days_left']) . ' dias' : '' ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$goals): ?><p class="text-soft text-sm py-4">Sem objetivos ativos. <a href="/goals.php" class="text-brand-400">Define um</a>.</p><?php endif; ?>
  </div>
</div>

<script type="application/json" id="dash-data" <?= nonce_attr() ?>><?= json_encode($payload, JSON_UNESCAPED_UNICODE) ?></script>
<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function () {
  const D = JSON.parse(document.getElementById('dash-data').textContent);
  WW.areaChart('#chart-cashflow', D.cashflow.labels, [
    { name: 'Receitas', data: D.cashflow.income },
    { name: 'Despesas', data: D.cashflow.expense },
  ]);
  WW.gaugeChart('#chart-health', D.health);
  if (D.expenseCat.values.length) WW.donutChart('#chart-expense', D.expenseCat.labels, D.expenseCat.values);
  else document.getElementById('chart-expense').innerHTML = '<p class="text-soft text-sm py-8 text-center">Sem despesas este mês.</p>';
});
</script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

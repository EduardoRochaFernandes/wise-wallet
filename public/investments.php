<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$p = Finance::investments($uid);
$typeLabels = ['stock' => 'Ações', 'etf' => 'ETF', 'crypto' => 'Cripto', 'bond' => 'Obrigações', 'real_estate' => 'Imobiliário', 'retirement' => 'PPR/Reforma'];
$div = ['labels' => array_map(fn($t) => $typeLabels[$t] ?? $t, array_keys($p['by_type'])), 'values' => array_map(fn($v) => round((float) $v, 2), array_values($p['by_type']))];

$title = 'Investimentos';
$nav = 'investments';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
  <div class="stat-card"><div class="text-soft text-sm">Valor atual</div><div class="text-2xl font-extrabold mt-1 sensitive"><?= money($p['value']) ?></div></div>
  <div class="stat-card"><div class="text-soft text-sm">Investido</div><div class="text-2xl font-extrabold mt-1 sensitive"><?= money($p['cost']) ?></div></div>
  <div class="stat-card"><div class="text-soft text-sm">Ganho/Perda</div><div class="text-2xl font-extrabold mt-1 sensitive <?= $p['gain'] >= 0 ? 'text-pos' : 'text-neg' ?>"><?= ($p['gain'] >= 0 ? '+' : '') . money($p['gain']) ?></div></div>
  <div class="stat-card"><div class="text-soft text-sm">ROI</div><div class="text-2xl font-extrabold mt-1 <?= $p['roi'] >= 0 ? 'text-pos' : 'text-neg' ?>"><?= ($p['roi'] >= 0 ? '+' : '') . number_format($p['roi'], 2, ',', ' ') ?>%</div></div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
  <div class="card card-pad lg:col-span-2">
    <div class="flex justify-between items-center mb-3"><h2 class="font-bold text-lg">Carteira</h2><button class="btn-primary btn-sm" data-modal-open="#m-inv"><?= icon('plus','w-4 h-4') ?> Novo ativo</button></div>
    <div class="overflow-x-auto"><table class="table">
      <thead><tr><th>Ativo</th><th>Tipo</th><th class="text-right">Qtд.</th><th class="text-right">Valor</th><th class="text-right">G/P</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($p['items'] as $i): ?>
        <tr>
          <td><div class="font-medium"><?= e($i['name']) ?></div><?php if ($i['symbol']): ?><div class="text-xs text-soft"><?= e($i['symbol']) ?></div><?php endif; ?></td>
          <td><span class="badge-brand"><?= e($typeLabels[$i['type']] ?? $i['type']) ?></span></td>
          <td class="text-right text-soft"><?= rtrim(rtrim(number_format((float) $i['quantity'], 4, ',', ' '), '0'), ',') ?></td>
          <td class="text-right font-semibold sensitive"><?= money($i['value']) ?></td>
          <td class="text-right <?= $i['gain'] >= 0 ? 'text-pos' : 'text-neg' ?>"><?= ($i['gain'] >= 0 ? '+' : '') . number_format($i['roi'], 1, ',', ' ') ?>%</td>
          <td class="text-right"><button class="btn-ghost btn-sm p-1.5" data-del="/api/investments.php" data-id="<?= $i['id'] ?>">✕</button></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$p['items']): ?><tr><td colspan="6" class="text-center text-soft py-10">Carteira vazia. Adiciona o teu primeiro ativo.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card card-pad">
    <h2 class="font-bold text-lg mb-2">Diversificação</h2>
    <div id="chart-div"></div>
  </div>
</div>

<div id="m-inv" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold">Novo ativo</h3><button class="btn-ghost btn-sm p-2" data-modal-close>✕</button></div>
    <form data-api-form="/api/investments.php" class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Nome</label><input name="name" required class="input" placeholder="Ex.: Apple"></div>
        <div><label class="label">Símbolo</label><input name="symbol" class="input" placeholder="AAPL"></div>
      </div>
      <div><label class="label">Tipo</label><select name="type" class="select"><?php foreach ($typeLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></div>
      <div class="grid grid-cols-3 gap-3">
        <div><label class="label">Quantidade</label><input name="quantity" type="number" step="0.00000001" min="0" required class="input"></div>
        <div><label class="label">Compra (€)</label><input name="buy_price" type="number" step="0.00000001" min="0" required class="input"></div>
        <div><label class="label">Atual (€)</label><input name="current_price" type="number" step="0.00000001" min="0" class="input"></div>
      </div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Adicionar</button><button type="button" class="btn-ghost" data-modal-close>Cancelar</button></div>
    </form>
  </div>
</div>

<script type="application/json" id="div-data" <?= nonce_attr() ?>><?= json_encode($div, JSON_UNESCAPED_UNICODE) ?></script>
<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function(){ const D = JSON.parse(document.getElementById('div-data').textContent);
  if (D.values.length) WW.donutChart('#chart-div', D.labels, D.values);
  else document.getElementById('chart-div').innerHTML='<p class="text-soft text-sm py-8 text-center">Sem ativos.</p>';
});
</script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

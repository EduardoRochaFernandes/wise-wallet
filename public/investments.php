<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$p = Finance::investments($uid);
$typeLabels = ['stock' => 'Stocks', 'etf' => 'ETF', 'crypto' => 'Crypto', 'bond' => 'Bonds', 'real_estate' => 'Real estate', 'retirement' => 'Retirement'];
$div = ['labels' => array_map(fn($t) => $typeLabels[$t] ?? $t, array_keys($p['by_type'])), 'values' => array_map(fn($v) => round((float) $v, 2), array_values($p['by_type']))];

$title = 'Investments';
$nav = 'investments';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-px mb-5 rounded-lg overflow-hidden border" style="border-color:rgb(var(--line))">
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Current value</div><div class="font-display text-xl font-semibold mt-1 amount sensitive"><?= money($p['value']) ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Invested</div><div class="font-display text-xl font-semibold mt-1 amount sensitive"><?= money($p['cost']) ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Gain / loss</div><div class="font-display text-xl font-semibold mt-1 amount sensitive <?= $p['gain'] >= 0 ? 'text-pos' : 'text-neg' ?>"><?= ($p['gain'] >= 0 ? '+' : '') . money($p['gain']) ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">ROI</div><div class="font-display text-xl font-semibold mt-1 <?= $p['roi'] >= 0 ? 'text-pos' : 'text-neg' ?>"><?= ($p['roi'] >= 0 ? '+' : '') . number_format($p['roi'], 2, '.', ',') ?>%</div></div>
</div>

<div class="grid lg:grid-cols-3 gap-5">
  <div class="card card-pad lg:col-span-2">
    <div class="flex justify-between items-center mb-3"><h2 class="font-display font-semibold text-lg">Portfolio</h2><button class="btn-primary btn-sm" data-modal-open="#m-inv"><?= icon('plus','w-4 h-4') ?> New asset</button></div>
    <div class="overflow-x-auto"><table class="table">
      <thead><tr><th>Asset</th><th>Type</th><th class="text-right">Qty</th><th class="text-right">Value</th><th class="text-right">G/L</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($p['items'] as $i): ?>
        <tr>
          <td><div class="font-medium"><?= e($i['name']) ?></div><?php if ($i['symbol']): ?><div class="text-xs text-soft"><?= e($i['symbol']) ?></div><?php endif; ?></td>
          <td><span class="badge"><?= e($typeLabels[$i['type']] ?? $i['type']) ?></span></td>
          <td class="text-right text-soft amount"><?= rtrim(rtrim(number_format((float) $i['quantity'], 4, '.', ','), '0'), '.') ?></td>
          <td class="text-right font-medium amount sensitive"><?= money($i['value']) ?></td>
          <td class="text-right amount <?= $i['gain'] >= 0 ? 'text-pos' : 'text-neg' ?>"><?= ($i['gain'] >= 0 ? '+' : '') . number_format($i['roi'], 1, '.', ',') ?>%</td>
          <td class="text-right"><button class="btn-ghost btn-sm p-1.5" data-del="/api/investments.php" data-id="<?= $i['id'] ?>">&times;</button></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$p['items']): ?><tr><td colspan="6" class="text-center text-soft py-10">Empty portfolio. Add your first asset.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-2">Diversification</h2>
    <div id="chart-div"></div>
  </div>
</div>

<div id="m-inv" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="font-display text-lg font-semibold">New asset</h3><button class="btn-ghost btn-sm p-2" data-modal-close>&times;</button></div>
    <form data-api-form="/api/investments.php" class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Name</label><input name="name" required class="input" placeholder="e.g. Apple"></div>
        <div><label class="label">Symbol</label><input name="symbol" class="input" placeholder="AAPL"></div>
      </div>
      <div><label class="label">Type</label><select name="type" class="select"><?php foreach ($typeLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></div>
      <div class="grid grid-cols-3 gap-3">
        <div><label class="label">Quantity</label><input name="quantity" type="number" step="0.00000001" min="0" required class="input"></div>
        <div><label class="label">Buy (&euro;)</label><input name="buy_price" type="number" step="0.00000001" min="0" required class="input"></div>
        <div><label class="label">Current (&euro;)</label><input name="current_price" type="number" step="0.00000001" min="0" class="input"></div>
      </div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Add</button><button type="button" class="btn-ghost" data-modal-close>Cancel</button></div>
    </form>
  </div>
</div>

<script type="application/json" id="div-data" <?= nonce_attr() ?>><?= json_encode($div, JSON_UNESCAPED_UNICODE) ?></script>
<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function(){ const D = JSON.parse(document.getElementById('div-data').textContent);
  if (D.values.length) WW.donutChart('#chart-div', D.labels, D.values);
  else WW.emptyState('#chart-div', { title: 'Empty portfolio', text: 'Add your first asset to see diversification.', cta: 'Add an asset', modal: '#m-inv' });
});
</script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

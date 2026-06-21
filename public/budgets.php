<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$budgets = Finance::budgets($uid);
$expenseCats = Finance::categories($uid, 'expense');
$totalBudget = array_sum(array_map(fn($b) => (float) $b['amount'], $budgets));
$totalSpent = array_sum(array_map(fn($b) => (float) $b['spent'], $budgets));

$title = 'Orçamentos';
$nav = 'budgets';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid sm:grid-cols-3 gap-4 mb-5">
  <div class="stat-card"><div class="text-soft text-sm">Orçamento total (mês)</div><div class="text-2xl font-extrabold mt-1 sensitive"><?= money($totalBudget) ?></div></div>
  <div class="stat-card"><div class="text-soft text-sm">Gasto até agora</div><div class="text-2xl font-extrabold mt-1 text-neg sensitive"><?= money($totalSpent) ?></div></div>
  <div class="stat-card"><div class="text-soft text-sm">Disponível</div><div class="text-2xl font-extrabold mt-1 text-pos sensitive"><?= money(max(0, $totalBudget - $totalSpent)) ?></div></div>
</div>

<div class="flex justify-between items-center mb-4">
  <h2 class="font-bold text-lg">Por categoria</h2>
  <button class="btn-primary" data-modal-open="#m-budget"><?= icon('plus','w-4 h-4') ?> Novo orçamento</button>
</div>

<div class="grid gap-4 sm:grid-cols-2">
  <?php foreach ($budgets as $b): $col = $b['status'] === 'over' ? 'var(--neg)' : ($b['status'] === 'warn' ? 'var(--warn)' : 'var(--pos)'); ?>
    <div class="card card-pad">
      <div class="flex items-center justify-between mb-2">
        <div class="font-bold"><?= e($b['category_name']) ?></div>
        <div class="flex items-center gap-2">
          <?php if ($b['status'] === 'over'): ?><span class="badge-neg">Excedido</span><?php elseif ($b['status'] === 'warn'): ?><span class="badge-warn">Atenção</span><?php else: ?><span class="badge-pos">No bom caminho</span><?php endif; ?>
          <button class="btn-ghost btn-sm p-1.5" data-del="/api/budgets.php" data-id="<?= $b['id'] ?>">✕</button>
        </div>
      </div>
      <div class="flex justify-between text-sm mb-1 text-soft"><span class="sensitive"><?= money($b['spent']) ?> gasto</span><span class="sensitive">de <?= money($b['amount']) ?></span></div>
      <div class="progress"><span style="width:<?= $b['pct'] ?>%;background:rgb(<?= $col ?>)"></span></div>
      <div class="text-xs text-soft mt-2"><?= $b['pct'] ?>% utilizado</div>
    </div>
  <?php endforeach; ?>
  <?php if (!$budgets): ?><div class="card card-pad text-soft col-span-2 text-center py-10">Ainda não tens orçamentos. Cria o primeiro para receberes alertas automáticos.</div><?php endif; ?>
</div>

<div id="m-budget" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold">Novo orçamento</h3><button class="btn-ghost btn-sm p-2" data-modal-close>✕</button></div>
    <form data-api-form="/api/budgets.php" class="space-y-3">
      <div><label class="label">Categoria de despesa</label><select name="category_id" required class="select">
        <?php foreach ($expenseCats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Limite (€)</label><input name="amount" type="number" step="0.01" min="0" required class="input" placeholder="300"></div>
        <div><label class="label">Período</label><select name="period" class="select"><option value="monthly">Mensal</option><option value="weekly">Semanal</option><option value="yearly">Anual</option></select></div>
      </div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Criar</button><button type="button" class="btn-ghost" data-modal-close>Cancelar</button></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

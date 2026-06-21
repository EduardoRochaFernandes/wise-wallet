<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$sub = Finance::subscriptions($uid);
$cats = Finance::categories($uid, 'expense');

$title = 'Subscrições';
$nav = 'subscriptions';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid sm:grid-cols-2 gap-4 mb-5">
  <div class="stat-card"><div class="text-soft text-sm">Custo mensal (ativas)</div><div class="text-3xl font-extrabold mt-1 sensitive"><?= money($sub['monthly']) ?></div></div>
  <div class="stat-card"><div class="text-soft text-sm">Custo anual normalizado</div><div class="text-3xl font-extrabold mt-1 text-neg sensitive"><?= money($sub['yearly']) ?></div><div class="text-xs text-soft mt-1">O "vampiro silencioso" das tuas finanças</div></div>
</div>

<div class="flex justify-between items-center mb-4">
  <h2 class="font-bold text-lg">As tuas subscrições</h2>
  <button class="btn-primary" data-modal-open="#m-sub"><?= icon('plus','w-4 h-4') ?> Nova subscrição</button>
</div>

<div class="card overflow-hidden">
  <table class="table">
    <thead><tr><th>Serviço</th><th>Ciclo</th><th class="text-right">Valor</th><th class="text-right">Mensal eq.</th><th>Próxima</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($sub['items'] as $s): $m = $s['billing_cycle'] === 'yearly' ? $s['amount'] / 12 : $s['amount']; ?>
      <tr class="<?= $s['is_active'] ? '' : 'opacity-50' ?>">
        <td class="font-medium"><?= e($s['name']) ?> <?php if (!$s['is_active']): ?><span class="badge ml-1" style="background:rgb(var(--line))">pausada</span><?php endif; ?></td>
        <td class="text-soft text-sm"><?= $s['billing_cycle'] === 'yearly' ? 'Anual' : 'Mensal' ?></td>
        <td class="text-right font-semibold sensitive"><?= money($s['amount']) ?></td>
        <td class="text-right text-soft sensitive"><?= money($m) ?></td>
        <td class="text-soft text-sm"><?= $s['next_renewal'] ? date('d/m/Y', strtotime($s['next_renewal'])) : '—' ?></td>
        <td class="text-right whitespace-nowrap">
          <form data-api-form="/api/subscriptions.php" data-method="PATCH" class="inline">
            <input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="is_active" value="<?= $s['is_active'] ? 0 : 1 ?>">
            <button class="btn-ghost btn-sm"><?= $s['is_active'] ? 'Pausar' : 'Ativar' ?></button>
          </form>
          <button class="btn-ghost btn-sm p-1.5" data-del="/api/subscriptions.php" data-id="<?= $s['id'] ?>">✕</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$sub['items']): ?><tr><td colspan="6" class="text-center text-soft py-10">Sem subscrições registadas.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<div id="m-sub" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold">Nova subscrição</h3><button class="btn-ghost btn-sm p-2" data-modal-close>✕</button></div>
    <form data-api-form="/api/subscriptions.php" class="space-y-3">
      <div><label class="label">Serviço</label><input name="name" required class="input" placeholder="Ex.: Netflix"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Valor (€)</label><input name="amount" type="number" step="0.01" min="0" required class="input"></div>
        <div><label class="label">Ciclo</label><select name="billing_cycle" class="select"><option value="monthly">Mensal</option><option value="yearly">Anual</option></select></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Próxima renovação</label><input name="next_renewal" type="date" class="input"></div>
        <div><label class="label">Categoria</label><select name="category_id" class="select"><option value="">—</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Criar</button><button type="button" class="btn-ghost" data-modal-close>Cancelar</button></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

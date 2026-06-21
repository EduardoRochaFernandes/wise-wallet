<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$bills = Finance::bills($uid);
$cats = Finance::categories($uid, 'expense');
$accounts = Finance::accounts($uid);
$groups = ['overdue' => [], 'pending' => [], 'paid' => []];
foreach ($bills as $b) { $groups[$b['status']][] = $b; }
$labels = ['overdue' => 'Em atraso', 'pending' => 'A pagar', 'paid' => 'Pagas'];
$badge = ['overdue' => 'badge-neg', 'pending' => 'badge-warn', 'paid' => 'badge-pos'];

$title = 'Faturas';
$nav = 'bills';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="flex justify-between items-center mb-5">
  <h2 class="font-bold text-lg">Calendário de pagamentos</h2>
  <button class="btn-primary" data-modal-open="#m-bill"><?= icon('plus','w-4 h-4') ?> Nova fatura</button>
</div>

<div class="grid gap-5 lg:grid-cols-3">
  <?php foreach (['overdue', 'pending', 'paid'] as $st): ?>
    <div>
      <div class="flex items-center gap-2 mb-3"><span class="<?= $badge[$st] ?>"><?= $labels[$st] ?></span><span class="text-soft text-sm"><?= count($groups[$st]) ?></span></div>
      <div class="space-y-3">
        <?php foreach ($groups[$st] as $b): ?>
          <div class="card card-pad">
            <div class="flex justify-between items-start">
              <div>
                <div class="font-bold"><?= e($b['name']) ?></div>
                <div class="text-xs text-soft">Vence <?= date('d/m/Y', strtotime($b['due_date'])) ?> · <?= e($b['category_name'] ?? 'Geral') ?></div>
              </div>
              <div class="text-right font-extrabold sensitive"><?= money($b['amount']) ?></div>
            </div>
            <div class="flex gap-2 mt-3">
              <?php if ($st !== 'paid'): ?>
                <form data-api-form="/api/bills.php" data-method="PATCH" class="flex-1">
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <input type="hidden" name="create_expense" value="1">
                  <button class="btn-primary btn-sm w-full"><?= icon('check','w-4 h-4') ?> Marcar paga</button>
                </form>
              <?php endif; ?>
              <button class="btn-ghost btn-sm p-1.5" data-del="/api/bills.php" data-id="<?= $b['id'] ?>">✕</button>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$groups[$st]): ?><div class="text-soft text-sm py-4">—</div><?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div id="m-bill" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold">Nova fatura</h3><button class="btn-ghost btn-sm p-2" data-modal-close>✕</button></div>
    <form data-api-form="/api/bills.php" class="space-y-3">
      <div><label class="label">Nome</label><input name="name" required class="input" placeholder="Ex.: Renda"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Valor (€)</label><input name="amount" type="number" step="0.01" min="0" required class="input"></div>
        <div><label class="label">Vencimento</label><input name="due_date" type="date" required class="input" value="<?= date('Y-m-d') ?>"></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Recorrência</label><select name="recurrence" class="select"><option value="monthly">Mensal</option><option value="once">Única</option><option value="weekly">Semanal</option><option value="quarterly">Trimestral</option><option value="yearly">Anual</option></select></div>
        <div><label class="label">Categoria</label><select name="category_id" class="select"><option value="">—</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div><label class="label">Conta a debitar</label><select name="account_id" class="select"><option value="">—</option><?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?></select></div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Criar</button><button type="button" class="btn-ghost" data-modal-close>Cancelar</button></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

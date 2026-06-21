<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$accounts = Finance::accounts($uid);
$total = Finance::netWorth($uid);
$typeLabels = ['checking' => 'À ordem', 'savings' => 'Poupança', 'credit' => 'Cartão de crédito', 'cash' => 'Dinheiro', 'crypto' => 'Cripto', 'investment' => 'Investimento'];

$title = 'Contas';
$nav = 'accounts';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="flex items-center justify-between mb-5">
  <div>
    <div class="text-soft text-sm">Património líquido total</div>
    <div class="text-3xl font-extrabold sensitive"><?= money($total) ?></div>
  </div>
  <button class="btn-primary" data-modal-open="#m-account"><?= icon('plus','w-4 h-4') ?> Nova conta</button>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
  <?php foreach ($accounts as $a): $neg = (float) $a['balance'] < 0; ?>
    <div class="card card-pad relative">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl grid place-items-center text-white" style="background:<?= e($a['color'] ?: '#1f5a3f') ?>"><?= icon('landmark') ?></div>
        <div class="flex-1 min-w-0">
          <div class="font-bold truncate"><?= e($a['name']) ?></div>
          <div class="text-xs text-soft"><?= e($typeLabels[$a['type']] ?? $a['type']) ?></div>
        </div>
        <button class="btn-ghost btn-sm p-1.5" data-del="/api/accounts.php" data-id="<?= $a['id'] ?>" data-confirm="Eliminar esta conta e as suas transações?" title="Eliminar">✕</button>
      </div>
      <div class="text-2xl font-extrabold mt-4 sensitive <?= $neg ? 'text-neg' : '' ?>"><?= money($a['balance']) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- New account modal -->
<div id="m-account" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold">Nova conta</h3><button class="btn-ghost btn-sm p-2" data-modal-close>✕</button></div>
    <form data-api-form="/api/accounts.php" data-method="POST" class="space-y-3">
      <div><label class="label">Nome</label><input name="name" required class="input" placeholder="Ex.: Conta Revolut"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Tipo</label><select name="type" class="select">
          <?php foreach ($typeLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
        </select></div>
        <div><label class="label">Saldo inicial (€)</label><input name="balance" type="number" step="0.01" value="0" class="input"></div>
      </div>
      <div><label class="label">Cor</label><input name="color" type="color" value="#1f5a3f" class="input h-11 p-1"></div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Criar conta</button><button type="button" class="btn-ghost" data-modal-close>Cancelar</button></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

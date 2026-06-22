<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$accounts = Finance::accounts($uid);
$total = Finance::netWorth($uid);
$typeLabels = ['checking' => 'Checking', 'savings' => 'Savings', 'credit' => 'Credit card', 'cash' => 'Cash', 'crypto' => 'Crypto', 'investment' => 'Investment'];

$title = 'Accounts';
$nav = 'accounts';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="flex items-center justify-between mb-5">
  <div>
    <div class="text-soft text-xs uppercase tracking-wide">Total net worth</div>
    <div class="font-display text-3xl font-semibold amount sensitive"><?= money($total) ?></div>
  </div>
  <button class="btn-primary" data-modal-open="#m-account"><?= icon('plus','w-4 h-4') ?> New account</button>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
  <?php foreach ($accounts as $a): $neg = (float) $a['balance'] < 0; ?>
    <div class="card card-pad relative">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded grid place-items-center text-white" style="background:<?= e($a['color'] ?: '#1f5a3f') ?>"><?= icon('landmark', 'w-5 h-5') ?></div>
        <div class="flex-1 min-w-0">
          <div class="font-medium truncate"><?= e($a['name']) ?></div>
          <div class="text-xs text-soft"><?= e($typeLabels[$a['type']] ?? $a['type']) ?></div>
        </div>
        <button class="btn-ghost btn-sm p-1.5" data-del="/api/accounts.php" data-id="<?= $a['id'] ?>" data-confirm="Delete this account and its transactions?" title="Delete">&times;</button>
      </div>
      <div class="font-display text-2xl font-semibold mt-4 amount sensitive <?= $neg ? 'text-neg' : '' ?>"><?= money($a['balance']) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div id="m-account" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="font-display text-lg font-semibold">New account</h3><button class="btn-ghost btn-sm p-2" data-modal-close>&times;</button></div>
    <form data-api-form="/api/accounts.php" data-method="POST" class="space-y-3">
      <div><label class="label">Name</label><input name="name" required class="input" placeholder="e.g. Revolut"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Type</label><select name="type" class="select">
          <?php foreach ($typeLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
        </select></div>
        <div><label class="label">Initial balance (&euro;)</label><input name="balance" type="number" step="0.01" value="0" class="input"></div>
      </div>
      <div><label class="label">Color</label><input name="color" type="color" value="#1f5a3f" class="input h-11 p-1"></div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Create account</button><button type="button" class="btn-ghost" data-modal-close>Cancel</button></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

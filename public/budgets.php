<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$budgets = Finance::budgets($uid);
$expenseCats = Finance::categories($uid, 'expense');
$totalBudget = array_sum(array_map(fn($b) => (float) $b['amount'], $budgets));
$totalSpent = array_sum(array_map(fn($b) => (float) $b['spent'], $budgets));

$title = 'Budgets';
$nav = 'budgets';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid sm:grid-cols-3 gap-px mb-5 rounded-lg overflow-hidden border" style="border-color:rgb(var(--line))">
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Total budget · month</div><div class="font-display text-xl font-semibold mt-1 amount sensitive"><?= money($totalBudget) ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Spent so far</div><div class="font-display text-xl font-semibold mt-1 text-neg amount sensitive"><?= money($totalSpent) ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Available</div><div class="font-display text-xl font-semibold mt-1 text-pos amount sensitive"><?= money(max(0, $totalBudget - $totalSpent)) ?></div></div>
</div>

<div class="flex justify-between items-center mb-4">
  <h2 class="font-display font-semibold text-lg">By category</h2>
  <button class="btn-primary" data-modal-open="#m-budget"><?= icon('plus','w-4 h-4') ?> New budget</button>
</div>

<div class="grid gap-4 sm:grid-cols-2">
  <?php foreach ($budgets as $b): $col = $b['status'] === 'over' ? 'var(--neg)' : ($b['status'] === 'warn' ? 'var(--warn)' : 'var(--accent)'); ?>
    <div class="card card-pad">
      <div class="flex items-center justify-between mb-2">
        <div class="font-medium"><?= e($b['category_name']) ?></div>
        <div class="flex items-center gap-2">
          <?php if ($b['status'] === 'over'): ?><span class="badge-neg">Over</span><?php elseif ($b['status'] === 'warn'): ?><span class="badge-warn">Warning</span><?php else: ?><span class="badge-pos">On track</span><?php endif; ?>
          <button class="btn-ghost btn-sm p-1.5" data-del="/api/budgets.php" data-id="<?= $b['id'] ?>">&times;</button>
        </div>
      </div>
      <div class="flex justify-between text-sm mb-1 text-soft"><span class="amount sensitive"><?= money($b['spent']) ?> spent</span><span class="amount sensitive">of <?= money($b['amount']) ?></span></div>
      <div class="progress"><span style="width:<?= $b['pct'] ?>%;background:rgb(<?= $col ?>)"></span></div>
      <div class="text-xs text-soft mt-2"><?= $b['pct'] ?>% used</div>
    </div>
  <?php endforeach; ?>
  <?php if (!$budgets): ?><div class="card card-pad text-soft col-span-2 text-center py-10">No budgets yet. Create one to get automatic warnings as you spend.</div><?php endif; ?>
</div>

<div id="m-budget" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="font-display text-lg font-semibold">New budget</h3><button class="btn-ghost btn-sm p-2" data-modal-close>&times;</button></div>
    <form data-api-form="/api/budgets.php" class="space-y-3">
      <div><label class="label">Expense category</label><select name="category_id" required class="select">
        <?php foreach ($expenseCats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Limit (&euro;)</label><input name="amount" type="number" step="0.01" min="0" required class="input" placeholder="300"></div>
        <div><label class="label">Period</label><select name="period" class="select"><option value="monthly">Monthly</option><option value="weekly">Weekly</option><option value="yearly">Yearly</option></select></div>
      </div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Create</button><button type="button" class="btn-ghost" data-modal-close>Cancel</button></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

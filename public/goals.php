<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$goals = Finance::goals($uid);

$title = 'Goals';
$nav = 'goals';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="flex justify-between items-center mb-5">
  <h2 class="font-display font-semibold text-lg">Your goals</h2>
  <button class="btn-primary" data-modal-open="#m-goal"><?= icon('plus','w-4 h-4') ?> New goal</button>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
  <?php foreach ($goals as $g): $done = $g['status'] === 'completed' || $g['pct'] >= 100; ?>
    <div class="card card-pad">
      <div class="flex items-start justify-between">
        <div class="w-10 h-10 rounded grid place-items-center text-white" style="background:<?= e($g['color']) ?>"><?= icon('star', 'w-5 h-5') ?></div>
        <button class="btn-ghost btn-sm p-1.5" data-del="/api/goals.php" data-id="<?= $g['id'] ?>">&times;</button>
      </div>
      <h3 class="font-medium mt-3"><?= e($g['name']) ?></h3>
      <div class="text-sm text-soft amount sensitive"><?= money($g['current_amount']) ?> of <?= money($g['target_amount']) ?></div>
      <div class="progress mt-3"><span style="width:<?= $g['pct'] ?>%;background:<?= e($g['color']) ?>"></span></div>
      <div class="flex items-center justify-between mt-2">
        <div class="flex gap-1">
          <?php foreach ([25, 50, 75, 100] as $m): ?>
            <span class="text-xs px-1.5 py-0.5 rounded-sm <?= $g['pct'] >= $m ? 'text-white' : 'text-soft' ?>" style="<?= $g['pct'] >= $m ? 'background:' . e($g['color']) : 'background:rgb(var(--surface-2))' ?>"><?= $m ?></span>
          <?php endforeach; ?>
        </div>
        <span class="text-xs text-soft"><?= $g['days_left'] !== null ? max(0, $g['days_left']) . ' days' : 'no deadline' ?></span>
      </div>
      <?php if ($done): ?>
        <div class="badge-pos mt-3 w-full justify-center">Completed</div>
      <?php else: ?>
        <button class="btn-ghost btn-sm w-full mt-3 js-contrib" data-id="<?= $g['id'] ?>" data-name="<?= e($g['name']) ?>">Contribute</button>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$goals): ?><div class="card card-pad text-soft col-span-3 text-center py-10">Set your first financial goal.</div><?php endif; ?>
</div>

<div id="m-goal" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="font-display text-lg font-semibold">New goal</h3><button class="btn-ghost btn-sm p-2" data-modal-close>&times;</button></div>
    <form data-api-form="/api/goals.php" class="space-y-3">
      <div><label class="label">Name</label><input name="name" required class="input" placeholder="e.g. Emergency fund"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Target (&euro;)</label><input name="target_amount" type="number" step="0.01" min="0" required class="input" placeholder="6000"></div>
        <div><label class="label">Already saved (&euro;)</label><input name="current_amount" type="number" step="0.01" min="0" value="0" class="input"></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Deadline</label><input name="deadline" type="date" class="input"></div>
        <div><label class="label">Color</label><input name="color" type="color" value="#1f5a3f" class="input h-11 p-1"></div>
      </div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Create</button><button type="button" class="btn-ghost" data-modal-close>Cancel</button></div>
    </form>
  </div>
</div>

<div id="m-contrib" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="font-display text-lg font-semibold">Contribute to <span id="contrib-name"></span></h3><button class="btn-ghost btn-sm p-2" data-modal-close>&times;</button></div>
    <form data-api-form="/api/goals.php" data-method="PATCH" class="space-y-3">
      <input type="hidden" name="id" id="contrib-id">
      <div><label class="label">Amount (&euro;)</label><input name="amount" type="number" step="0.01" min="0" required class="input" placeholder="100"></div>
      <div><label class="label">Note <span class="text-soft normal-case">(optional)</span></label><input name="note" class="input" placeholder="e.g. monthly saving"></div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Add</button><button type="button" class="btn-ghost" data-modal-close>Cancel</button></div>
    </form>
  </div>
</div>

<script <?= nonce_attr() ?>>
document.querySelectorAll('.js-contrib').forEach((b) => b.addEventListener('click', () => {
  document.getElementById('contrib-id').value = b.dataset.id;
  document.getElementById('contrib-name').textContent = b.dataset.name;
  document.getElementById('m-contrib').classList.remove('hidden');
}));
</script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

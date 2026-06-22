<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();

Achievements::evaluate($uid); // unlock anything newly earned
$list = Achievements::all($uid);
$points = (int) (Database::scalar("SELECT points FROM users WHERE id=?", [$uid]) ?? 0);
$unlocked = count(array_filter($list, fn($a) => $a['unlocked']));
Database::run("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);

$rarityLabel = ['common' => 'Common', 'rare' => 'Rare', 'epic' => 'Epic', 'legendary' => 'Legendary'];

$title = 'Achievements';
$nav = 'achievements';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid sm:grid-cols-3 gap-px mb-6 rounded-lg overflow-hidden border" style="border-color:rgb(var(--line))">
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Points</div><div class="font-display text-3xl font-semibold mt-1 text-accent"><?= $points ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Unlocked</div><div class="font-display text-3xl font-semibold mt-1"><?= $unlocked ?> / <?= count($list) ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Progress</div><div class="font-display text-3xl font-semibold mt-1"><?= count($list) ? round($unlocked / count($list) * 100) : 0 ?>%</div>
    <div class="progress mt-2"><span style="width:<?= count($list) ? ($unlocked / count($list) * 100) : 0 ?>%"></span></div></div>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
  <?php foreach ($list as $a): $on = (int) $a['unlocked'] === 1; ?>
    <div class="card card-pad flex gap-3 items-start <?= $on ? '' : 'opacity-60' ?>">
      <div class="w-11 h-11 rounded grid place-items-center border rarity-<?= e($a['rarity']) ?>" style="background:rgb(var(--surface-2))">
        <?= $on ? icon('trophy', 'w-5 h-5') : icon('lock', 'w-4 h-4') ?>
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="font-medium truncate"><?= e($a['name']) ?></h3>
        <p class="text-soft text-sm mt-0.5"><?= e($a['description']) ?></p>
        <div class="flex items-center gap-2 mt-2">
          <span class="text-xs font-medium rarity-<?= e($a['rarity']) ?>"><?= $rarityLabel[$a['rarity']] ?? $a['rarity'] ?></span>
          <span class="text-xs text-soft">· <?= (int) $a['points'] ?> pts</span>
          <?php if ($on): ?><span class="badge-pos ml-auto text-xs"><?= date('d M Y', strtotime($a['unlocked_at'])) ?></span><?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

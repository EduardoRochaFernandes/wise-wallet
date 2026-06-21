<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();

Achievements::evaluate($uid); // unlock anything newly earned
$list = Achievements::all($uid);
$points = (int) (Database::scalar("SELECT points FROM users WHERE id=?", [$uid]) ?? 0);
$unlocked = count(array_filter($list, fn($a) => $a['unlocked']));
$notifs = Database::all("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$uid]);
Database::run("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);

$rarityLabel = ['common' => 'Comum', 'rare' => 'Rara', 'epic' => 'Épica', 'legendary' => 'Lendária'];

$title = 'Conquistas';
$nav = 'achievements';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="grid sm:grid-cols-3 gap-4 mb-6">
  <div class="stat-card"><div class="text-soft text-sm">Pontos</div><div class="text-3xl font-extrabold mt-1 text-brand-400"><?= $points ?></div></div>
  <div class="stat-card"><div class="text-soft text-sm">Desbloqueadas</div><div class="text-3xl font-extrabold mt-1"><?= $unlocked ?> / <?= count($list) ?></div></div>
  <div class="stat-card"><div class="text-soft text-sm">Progresso</div><div class="text-3xl font-extrabold mt-1 text-pos"><?= count($list) ? round($unlocked / count($list) * 100) : 0 ?>%</div>
    <div class="progress mt-2"><span style="width:<?= count($list) ? ($unlocked / count($list) * 100) : 0 ?>%;background:rgb(var(--brand))"></span></div></div>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
  <?php foreach ($list as $a): $on = (int) $a['unlocked'] === 1; ?>
    <div class="card card-pad flex gap-3 items-start <?= $on ? '' : 'opacity-60' ?>">
      <div class="w-12 h-12 rounded-xl grid place-items-center border-2 rarity-<?= e($a['rarity']) ?>" style="background:rgb(var(--surface-2))">
        <?= $on ? icon('trophy', 'w-6 h-6') : icon('lock', 'w-5 h-5') ?>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2"><h3 class="font-bold truncate"><?= e($a['name']) ?></h3></div>
        <p class="text-soft text-sm mt-0.5"><?= e($a['description']) ?></p>
        <div class="flex items-center gap-2 mt-2">
          <span class="text-xs font-semibold rarity-<?= e($a['rarity']) ?>"><?= $rarityLabel[$a['rarity']] ?? $a['rarity'] ?></span>
          <span class="text-xs text-soft">· <?= (int) $a['points'] ?> pts</span>
          <?php if ($on): ?><span class="badge-pos ml-auto text-xs">✓ <?= date('d/m/Y', strtotime($a['unlocked_at'])) ?></span><?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

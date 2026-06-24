<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAdmin();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (($_POST['settings'] ?? []) as $key => $value) {
        Database::run("UPDATE settings SET `value`=? WHERE `key`=?", [substr((string) $value, 0, 5000), (string) $key]);
    }
    Audit::log('admin_settings', Auth::id());
    $msg = 'Global settings updated.';
}

$settings = Database::all("SELECT * FROM settings ORDER BY `key`");
$title = 'Admin'; $nav = 'admin'; $adminPage = 'settings';
require __DIR__ . '/../../app/views/partials/app_head.php';
require __DIR__ . '/../../app/views/partials/admin_nav.php';
?>
<?php if ($msg): ?><div class="badge-pos rounded px-4 py-3 mb-4"><?= e($msg) ?></div><?php endif; ?>
<div class="card card-pad max-w-2xl">
  <h2 class="font-display font-semibold text-lg mb-4">Global platform settings</h2>
  <form method="post" class="space-y-3">
    <?= Csrf::field() ?>
    <?php foreach ($settings as $s): ?>
      <div>
        <label class="label"><?= e($s['key']) ?></label>
        <input name="settings[<?= e($s['key']) ?>]" class="input" value="<?= e($s['value']) ?>">
      </div>
    <?php endforeach; ?>
    <button class="btn-primary">Save settings</button>
  </form>
</div>
<?php require __DIR__ . '/../../app/views/partials/app_foot.php';

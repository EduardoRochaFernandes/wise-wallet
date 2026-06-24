<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAdmin();

$stats = [
    'users'    => (int) Database::scalar("SELECT COUNT(*) FROM users"),
    'active'   => (int) Database::scalar("SELECT COUNT(*) FROM users WHERE is_active=1"),
    'tx'       => (int) Database::scalar("SELECT COUNT(*) FROM transactions"),
    'volume'   => (float) Database::scalar("SELECT COALESCE(SUM(amount),0) FROM transactions"),
    'articles' => (int) Database::scalar("SELECT COUNT(*) FROM articles"),
    'failed24' => (int) Database::scalar("SELECT COUNT(*) FROM login_attempts WHERE success=0 AND attempted_at > (NOW() - INTERVAL 1 DAY)"),
];
$recentUsers = Database::all("SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC LIMIT 6");
$recentAudit = Database::all("SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 10");

$title = 'Admin'; $nav = 'admin';
require __DIR__ . '/../../app/views/partials/app_head.php';
require __DIR__ . '/../../app/views/partials/admin_nav.php';
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-px mb-6 rounded-lg overflow-hidden border" style="border-color:rgb(var(--line))">
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Users</div><div class="font-display text-3xl font-semibold mt-1"><?= $stats['users'] ?></div><div class="text-xs text-soft mt-1"><?= $stats['active'] ?> active</div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Transactions</div><div class="font-display text-3xl font-semibold mt-1"><?= $stats['tx'] ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Total volume</div><div class="font-display text-2xl font-semibold mt-1 amount"><?= money($stats['volume']) ?></div></div>
  <div class="card-pad" style="background:rgb(var(--surface))"><div class="text-soft text-xs uppercase tracking-wide">Failed logins · 24h</div><div class="font-display text-3xl font-semibold mt-1 <?= $stats['failed24'] > 0 ? 'text-warn' : '' ?>"><?= $stats['failed24'] ?></div></div>
</div>

<div class="grid lg:grid-cols-2 gap-5">
  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-3">New users</h2>
    <table class="table"><tbody>
      <?php foreach ($recentUsers as $u): ?>
        <tr><td><div class="font-medium"><?= e($u['name']) ?></div><div class="text-xs text-soft"><?= e($u['email']) ?></div></td>
        <td class="text-right"><span class="badge-brand"><?= e($u['role']) ?></span></td>
        <td class="text-right text-xs text-soft"><?= date('d M Y', strtotime($u['created_at'])) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-3 flex items-center gap-2"><?= icon('shield','w-4 h-4') ?> Recent audit</h2>
    <div class="space-y-2 text-sm">
      <?php foreach ($recentAudit as $a): ?>
        <div class="flex items-center justify-between p-2 rounded" style="background:rgb(var(--surface-2))">
          <div><span class="font-mono text-xs badge-brand"><?= e($a['action']) ?></span> <span class="text-soft"><?= e($a['email'] ?? 'system') ?></span></div>
          <div class="text-xs text-soft"><?= date('d M H:i', strtotime($a['created_at'])) ?></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$recentAudit): ?><p class="text-soft py-4">No events.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/partials/app_foot.php';

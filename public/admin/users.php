<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAdmin();
$me = Auth::id();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) input('id');
    $action = input('action');
    if ($id === $me) {
        $msg = 'Não podes alterar a tua própria conta de administrador aqui.';
    } elseif ($id > 0) {
        if ($action === 'toggle') {
            Database::run("UPDATE users SET is_active = 1 - is_active WHERE id=?", [$id]);
        } elseif ($action === 'role') {
            Database::run("UPDATE users SET role = IF(role='admin','user','admin') WHERE id=?", [$id]);
        } elseif ($action === 'delete') {
            Database::run("DELETE FROM users WHERE id=?", [$id]);
        }
        Audit::log('admin_user_' . $action, $me, ['target' => $id]);
        $msg = 'Ação aplicada.';
    }
}

$users = Database::all("SELECT id,name,email,role,is_active,points,last_login_at,created_at FROM users ORDER BY id");
$title = 'Administração'; $nav = 'admin'; $adminPage = 'users';
require __DIR__ . '/../../app/views/partials/app_head.php';
require __DIR__ . '/../../app/views/partials/admin_nav.php';
?>
<?php if ($msg): ?><div class="badge-brand rounded-xl px-4 py-3 mb-4"><?= e($msg) ?></div><?php endif; ?>
<div class="card overflow-hidden">
  <table class="table">
    <thead><tr><th>#</th><th>Utilizador</th><th>Função</th><th>Estado</th><th>Último login</th><th class="text-right">Ações</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td class="text-soft"><?= $u['id'] ?></td>
        <td><div class="font-medium"><?= e($u['name']) ?></div><div class="text-xs text-soft"><?= e($u['email']) ?></div></td>
        <td><span class="badge-brand"><?= e($u['role']) ?></span></td>
        <td><?= $u['is_active'] ? '<span class="badge-pos">ativo</span>' : '<span class="badge-neg">inativo</span>' ?></td>
        <td class="text-xs text-soft"><?= $u['last_login_at'] ? date('d/m/Y H:i', strtotime($u['last_login_at'])) : '—' ?></td>
        <td class="text-right">
          <?php if ($u['id'] !== $me): ?>
            <div class="inline-flex gap-1">
              <form method="post" class="inline"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn-ghost btn-sm"><?= $u['is_active'] ? 'Desativar' : 'Ativar' ?></button></form>
              <form method="post" class="inline"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="role"><button class="btn-ghost btn-sm"><?= $u['role'] === 'admin' ? '↓ user' : '↑ admin' ?></button></form>
              <form method="post" class="inline" onsubmit="return confirm('Eliminar este utilizador e todos os seus dados?')"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn-ghost btn-sm text-neg">✕</button></form>
            </div>
          <?php else: ?><span class="text-xs text-soft">(tu)</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../../app/views/partials/app_foot.php';

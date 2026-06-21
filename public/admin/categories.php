<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAdmin();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        Database::run("DELETE FROM categories WHERE id=? AND is_system=1", [(int) input('id')]);
        $msg = 'Categoria eliminada.';
    } else {
        $v = new Validator($_POST);
        $v->required('name', 'Nome')->max('name', 80, 'Nome');
        $v->required('type', 'Tipo')->in('type', ['income', 'expense', 'transfer'], 'Tipo');
        if ($v->passes()) {
            Database::run("INSERT INTO categories (user_id,name,type,icon,color,is_system,created_at) VALUES (NULL,?,?,?,?,1,NOW())",
                [$v->get('name'), input('type'), input('icon', 'tag'), input('color', '#64748b')]);
            $msg = 'Categoria de sistema criada.';
        } else { $msg = $v->firstError(); }
    }
    Audit::log('admin_category', Auth::id());
}

$cats = Database::all("SELECT * FROM categories WHERE is_system=1 ORDER BY type, name");
$title = 'Administração'; $nav = 'admin'; $adminPage = 'categories';
require __DIR__ . '/../../app/views/partials/app_head.php';
require __DIR__ . '/../../app/views/partials/admin_nav.php';
?>
<?php if ($msg): ?><div class="badge-brand rounded-xl px-4 py-3 mb-4"><?= e($msg) ?></div><?php endif; ?>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="card card-pad h-fit">
    <h2 class="font-bold text-lg mb-3">Nova categoria de sistema</h2>
    <form method="post" class="space-y-3">
      <?= Csrf::field() ?>
      <div><label class="label">Nome</label><input name="name" required class="input"></div>
      <div><label class="label">Tipo</label><select name="type" class="select"><option value="expense">Despesa</option><option value="income">Receita</option></select></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Ícone</label><input name="icon" class="input" value="tag" placeholder="lucide name"></div>
        <div><label class="label">Cor</label><input name="color" type="color" value="#1f5a3f" class="input h-11 p-1"></div>
      </div>
      <button class="btn-primary w-full">Criar</button>
    </form>
  </div>
  <div class="card overflow-hidden lg:col-span-2">
    <table class="table">
      <thead><tr><th>Nome</th><th>Tipo</th><th>Cor</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td class="font-medium"><?= e($c['name']) ?></td>
          <td><span class="badge <?= $c['type'] === 'income' ? 'badge-pos' : 'badge-warn' ?>"><?= e($c['type']) ?></span></td>
          <td><span class="inline-block w-5 h-5 rounded" style="background:<?= e($c['color']) ?>"></span></td>
          <td class="text-right"><form method="post" class="inline" onsubmit="return confirm('Eliminar?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn-ghost btn-sm text-neg">✕</button></form></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/partials/app_foot.php';

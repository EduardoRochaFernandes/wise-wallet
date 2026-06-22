<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAdmin();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        Database::run("DELETE FROM categories WHERE id=? AND is_system=1", [(int) input('id')]);
        $msg = 'Category deleted.';
    } else {
        $v = new Validator($_POST);
        $v->required('name', 'Name')->max('name', 80, 'Name');
        $v->required('type', 'Type')->in('type', ['income', 'expense', 'transfer'], 'Type');
        if ($v->passes()) {
            Database::run("INSERT INTO categories (user_id,name,type,icon,color,is_system,created_at) VALUES (NULL,?,?,?,?,1,NOW())",
                [$v->get('name'), input('type'), input('icon', 'tag'), input('color', '#1f5a3f')]);
            $msg = 'System category created.';
        } else { $msg = $v->firstError(); }
    }
    Audit::log('admin_category', Auth::id());
}

$cats = Database::all("SELECT * FROM categories WHERE is_system=1 ORDER BY type, name");
$title = 'Admin'; $nav = 'admin'; $adminPage = 'categories';
require __DIR__ . '/../../app/views/partials/app_head.php';
require __DIR__ . '/../../app/views/partials/admin_nav.php';
?>
<?php if ($msg): ?><div class="badge-brand rounded px-4 py-3 mb-4"><?= e($msg) ?></div><?php endif; ?>
<div class="grid lg:grid-cols-3 gap-5">
  <div class="card card-pad h-fit">
    <h2 class="font-display font-semibold text-lg mb-3">New system category</h2>
    <form method="post" class="space-y-3">
      <?= Csrf::field() ?>
      <div><label class="label">Name</label><input name="name" required class="input"></div>
      <div><label class="label">Type</label><select name="type" class="select"><option value="expense">Expense</option><option value="income">Income</option></select></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Icon</label><input name="icon" class="input" value="tag" placeholder="lucide name"></div>
        <div><label class="label">Color</label><input name="color" type="color" value="#1f5a3f" class="input h-11 p-1"></div>
      </div>
      <button class="btn-primary w-full">Create</button>
    </form>
  </div>
  <div class="card overflow-hidden lg:col-span-2">
    <table class="table">
      <thead><tr><th>Name</th><th>Type</th><th>Color</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td class="font-medium"><?= e($c['name']) ?></td>
          <td><span class="badge <?= $c['type'] === 'income' ? 'badge-pos' : 'badge-warn' ?>"><?= e($c['type']) ?></span></td>
          <td><span class="inline-block w-5 h-5 rounded-sm" style="background:<?= e($c['color']) ?>"></span></td>
          <td class="text-right"><form method="post" class="inline" onsubmit="return confirm('Delete?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn-ghost btn-sm text-neg">&times;</button></form></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/partials/app_foot.php';

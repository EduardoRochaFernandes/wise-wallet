<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAdmin();
$msg = null;

function slugify(string $s): string
{
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $s));
    return trim($s, '-') ?: 'artigo-' . time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        Database::run("DELETE FROM articles WHERE id=?", [(int) input('id')]);
        $msg = 'Artigo eliminado.';
    } else {
        $v = new Validator($_POST);
        $v->required('title', 'Título')->max('title', 200, 'Título');
        $v->required('body', 'Corpo');
        if ($v->passes()) {
            $id = (int) input('id');
            $slug = trim((string) input('slug')) !== '' ? slugify((string) input('slug')) : slugify((string) input('title'));
            $status = input('status') === 'draft' ? 'draft' : 'published';
            $catId = (int) input('category_id') ?: null;
            $mins = max(1, (int) input('reading_minutes', 5));
            if ($id > 0) {
                Database::run("UPDATE articles SET title=?, slug=?, category_id=?, excerpt=?, body=?, status=?, reading_minutes=?, published_at=COALESCE(published_at, IF(?='published',NOW(),NULL)) WHERE id=?",
                    [input('title'), $slug, $catId, mb_substr((string) input('excerpt'), 0, 300), input('body'), $status, $mins, $status, $id]);
                $msg = 'Artigo atualizado.';
            } else {
                Database::run("INSERT INTO articles (author_id,category_id,slug,title,excerpt,body,status,reading_minutes,published_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())",
                    [Auth::id(), $catId, $slug, input('title'), mb_substr((string) input('excerpt'), 0, 300), input('body'), $status, $mins, $status === 'published' ? date('Y-m-d H:i:s') : null]);
                $msg = 'Artigo criado.';
            }
        } else { $msg = $v->firstError(); }
    }
    Audit::log('admin_article', Auth::id());
}

$edit = null;
if (!empty($_GET['edit'])) { $edit = Database::one("SELECT * FROM articles WHERE id=?", [(int) $_GET['edit']]); }
$articles = Database::all("SELECT a.*, ac.name cat FROM articles a LEFT JOIN article_categories ac ON ac.id=a.category_id ORDER BY a.created_at DESC");
$cats = Database::all("SELECT * FROM article_categories ORDER BY name");

$title = 'Administração'; $nav = 'admin'; $adminPage = 'articles';
require __DIR__ . '/../../app/views/partials/app_head.php';
require __DIR__ . '/../../app/views/partials/admin_nav.php';
?>
<?php if ($msg): ?><div class="badge-brand rounded-xl px-4 py-3 mb-4"><?= e($msg) ?></div><?php endif; ?>
<div class="grid lg:grid-cols-2 gap-4">
  <div class="card card-pad h-fit">
    <h2 class="font-bold text-lg mb-3"><?= $edit ? 'Editar artigo' : 'Novo artigo' ?></h2>
    <form method="post" class="space-y-3">
      <?= Csrf::field() ?>
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
      <div><label class="label">Título</label><input name="title" required class="input" value="<?= e($edit['title'] ?? '') ?>"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Slug <span class="text-soft">(auto)</span></label><input name="slug" class="input" value="<?= e($edit['slug'] ?? '') ?>"></div>
        <div><label class="label">Categoria</label><select name="category_id" class="select"><option value="">—</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($edit['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div><label class="label">Resumo</label><input name="excerpt" class="input" maxlength="300" value="<?= e($edit['excerpt'] ?? '') ?>"></div>
      <div><label class="label">Corpo (HTML)</label><textarea name="body" rows="8" class="textarea" required><?= e($edit['body'] ?? '') ?></textarea></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Estado</label><select name="status" class="select"><option value="published" <?= ($edit['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option><option value="draft" <?= ($edit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option></select></div>
        <div><label class="label">Min. leitura</label><input name="reading_minutes" type="number" min="1" class="input" value="<?= e((string) ($edit['reading_minutes'] ?? 5)) ?>"></div>
      </div>
      <div class="flex gap-2"><button class="btn-primary flex-1"><?= $edit ? 'Guardar alterações' : 'Criar artigo' ?></button><?php if ($edit): ?><a href="/admin/articles.php" class="btn-ghost">Cancelar</a><?php endif; ?></div>
    </form>
  </div>
  <div class="card overflow-hidden">
    <table class="table">
      <thead><tr><th>Título</th><th>Estado</th><th>Views</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($articles as $a): ?>
        <tr>
          <td><div class="font-medium"><?= e($a['title']) ?></div><div class="text-xs text-soft"><?= e($a['cat'] ?? '—') ?></div></td>
          <td><span class="badge <?= $a['status'] === 'published' ? 'badge-pos' : 'badge-warn' ?>"><?= e($a['status']) ?></span></td>
          <td class="text-soft"><?= (int) $a['views'] ?></td>
          <td class="text-right whitespace-nowrap">
            <a href="/admin/articles.php?edit=<?= $a['id'] ?>" class="btn-ghost btn-sm">Editar</a>
            <form method="post" class="inline" onsubmit="return confirm('Eliminar?')"><?= Csrf::field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn-ghost btn-sm text-neg">✕</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/partials/app_foot.php';

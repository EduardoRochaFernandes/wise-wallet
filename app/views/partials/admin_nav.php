<?php
/** Admin sub-navigation. $adminPage = current key. */
$adminPage = $adminPage ?? '';
$items = [
    ['', 'Métricas', '/admin/index.php'],
    ['users', 'Utilizadores', '/admin/users.php'],
    ['categories', 'Categorias', '/admin/categories.php'],
    ['articles', 'Blog', '/admin/articles.php'],
    ['settings', 'Definições globais', '/admin/settings.php'],
    ['logs', 'Logs & auditoria', '/admin/logs.php'],
];
?>
<div class="flex flex-wrap gap-2 mb-6">
  <?php foreach ($items as [$key, $label, $href]): ?>
    <a href="<?= $href ?>" class="btn-sm <?= $adminPage === $key ? 'btn-primary' : 'btn-ghost' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

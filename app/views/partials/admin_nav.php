<?php
/** Admin sub-navigation. $adminPage = current key. */
$adminPage = $adminPage ?? '';
$items = [
    ['', 'Metrics', '/admin/index.php'],
    ['users', 'Users', '/admin/users.php'],
    ['categories', 'Categories', '/admin/categories.php'],
    ['articles', 'Guides', '/admin/articles.php'],
    ['settings', 'Global settings', '/admin/settings.php'],
    ['logs', 'Logs & audit', '/admin/logs.php'],
];
?>
<div class="flex flex-wrap gap-2 mb-6">
  <?php foreach ($items as [$key, $label, $href]): ?>
    <a href="<?= $href ?>" class="btn-sm <?= $adminPage === $key ? 'btn-primary' : 'btn-ghost' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

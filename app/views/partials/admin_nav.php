<?php
/** Admin sub-navigation. $adminPage = current key. */
$adminPage = $adminPage ?? '';
$items = [
    ['', 'Metrics', '/admin'],
    ['users', 'Users', '/admin/users'],
    ['categories', 'Categories', '/admin/categories'],
    ['articles', 'Guides', '/admin/articles'],
    ['settings', 'Global settings', '/admin/settings'],
    ['logs', 'Logs & audit', '/admin/logs'],
];
?>
<div class="flex flex-wrap gap-2 mb-6">
  <?php foreach ($items as [$key, $label, $href]): ?>
    <a href="<?= $href ?>" class="btn-sm <?= $adminPage === $key ? 'btn-primary' : 'btn-ghost' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

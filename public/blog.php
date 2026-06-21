<?php
require __DIR__ . '/../app/bootstrap.php';
$inApp = Auth::check();

$catSlug = trim($_GET['cat'] ?? '');
$params = [];
$sql = "SELECT a.*, ac.name cat_name, ac.slug cat_slug, u.name author
          FROM articles a
          LEFT JOIN article_categories ac ON ac.id = a.category_id
          LEFT JOIN users u ON u.id = a.author_id
         WHERE a.status='published'";
if ($catSlug !== '') { $sql .= " AND ac.slug = ?"; $params[] = $catSlug; }
$sql .= " ORDER BY a.published_at DESC";
$articles = Database::all($sql, $params);
$cats = Database::all("SELECT * FROM article_categories ORDER BY name");

if ($inApp) {
    $title = 'Blog'; $nav = 'blog';
    require __DIR__ . '/../app/views/partials/app_head.php';
} else {
    $seo = ['title' => 'Blog · Educação financeira · WiseWallet', 'description' => 'Artigos práticos sobre orçamentos, poupança, investimento e crédito.'];
    $activeNav = 'blog';
    require __DIR__ . '/../app/views/partials/public_head.php';
}
?>
<section class="max-w-6xl mx-auto <?= $inApp ? '' : 'px-4 sm:px-6 py-12' ?>">
  <?php if (!$inApp): ?>
    <div class="text-center mb-10">
      <h1 class="text-4xl font-extrabold">Aprende sobre o teu dinheiro</h1>
      <p class="text-soft mt-3">Educação financeira prática. Quem entende, decide melhor.</p>
    </div>
  <?php endif; ?>

  <div class="flex flex-wrap gap-2 mb-6">
    <a href="/blog.php" class="btn-sm <?= $catSlug === '' ? 'btn-primary' : 'btn-ghost' ?>">Todos</a>
    <?php foreach ($cats as $c): ?>
      <a href="/blog.php?cat=<?= e($c['slug']) ?>" class="btn-sm <?= $catSlug === $c['slug'] ? 'btn-primary' : 'btn-ghost' ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($articles as $a): ?>
      <a href="/article.php?slug=<?= e($a['slug']) ?>" class="card card-pad hover:shadow-glow transition-shadow block">
        <div class="flex items-center gap-2 mb-3">
          <?php if ($a['cat_name']): ?><span class="badge-brand text-xs"><?= e($a['cat_name']) ?></span><?php endif; ?>
          <span class="text-xs text-soft"><?= (int) $a['reading_minutes'] ?> min</span>
        </div>
        <h2 class="font-bold text-lg leading-snug"><?= e($a['title']) ?></h2>
        <p class="text-soft text-sm mt-2"><?= e($a['excerpt']) ?></p>
        <div class="text-xs text-soft mt-4"><?= e($a['author'] ?? 'WiseWallet') ?> · <?= $a['published_at'] ? date('d/m/Y', strtotime($a['published_at'])) : '' ?></div>
      </a>
    <?php endforeach; ?>
    <?php if (!$articles): ?><p class="text-soft col-span-3 text-center py-10">Sem artigos nesta categoria.</p><?php endif; ?>
  </div>
</section>
<?php
if ($inApp) { require __DIR__ . '/../app/views/partials/app_foot.php'; }
else { require __DIR__ . '/../app/views/partials/public_foot.php'; }

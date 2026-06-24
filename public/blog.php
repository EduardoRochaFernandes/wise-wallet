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
$recommended = $inApp && $catSlug === '' ? Recommend::articlesFor(Auth::id(), 3) : [];

if ($inApp) {
    $title = 'Guides'; $nav = 'blog';
    require __DIR__ . '/../app/views/partials/app_head.php';
} else {
    $seo = ['title' => 'Guides · Financial education · WiseWallet', 'description' => 'Practical guides on budgeting, saving, investing and credit.'];
    $activeNav = 'blog';
    require __DIR__ . '/../app/views/partials/public_head.php';
}
?>
<section class="max-w-5xl mx-auto <?= $inApp ? '' : 'px-4 sm:px-6 py-14' ?>">
  <?php if (!$inApp): ?>
    <div class="mb-10">
      <p class="eyebrow mb-3">Learn as you go</p>
      <h1 class="font-display text-4xl font-semibold">Money, explained simply.</h1>
      <p class="text-soft mt-3 max-w-xl">Practical financial education — the more you understand, the better you decide.</p>
    </div>
  <?php endif; ?>

  <?php if ($recommended): ?>
    <div class="mb-10">
      <p class="eyebrow mb-4">Recommended for you</p>
      <div class="grid sm:grid-cols-3 gap-4">
        <?php foreach ($recommended as $r): ?>
          <a href="/article?slug=<?= e($r['slug']) ?>" class="card card-pad lift block">
            <h3 class="font-display font-semibold leading-snug"><?= e($r['title']) ?></h3>
            <p class="text-soft text-xs mt-2"><?= e($r['reason']) ?></p>
            <span class="text-accent text-xs font-medium mt-3 inline-block"><?= (int) $r['reading_minutes'] ?> min read</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="flex flex-wrap gap-2 mb-6">
    <a href="/blog" class="btn-sm <?= $catSlug === '' ? 'btn-primary' : 'btn-ghost' ?>">All</a>
    <?php foreach ($cats as $c): ?>
      <a href="/blog?cat=<?= e($c['slug']) ?>" class="btn-sm <?= $catSlug === $c['slug'] ? 'btn-primary' : 'btn-ghost' ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="divide-y" style="border-color:rgb(var(--line))">
    <?php foreach ($articles as $a): ?>
      <a href="/article?slug=<?= e($a['slug']) ?>" class="grid sm:grid-cols-[1fr_auto] gap-2 sm:gap-8 py-6 group" style="border-color:rgb(var(--line))">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <?php if ($a['cat_name']): ?><span class="badge-brand text-xs"><?= e($a['cat_name']) ?></span><?php endif; ?>
          </div>
          <h2 class="font-display text-xl font-semibold group-hover:text-accent"><?= e($a['title']) ?></h2>
          <p class="text-soft text-sm mt-1.5 max-w-2xl"><?= e($a['excerpt']) ?></p>
        </div>
        <div class="text-soft text-xs sm:text-right pt-1 whitespace-nowrap"><?= e($a['author'] ?? 'WiseWallet') ?> · <?= (int) $a['reading_minutes'] ?> min read</div>
      </a>
    <?php endforeach; ?>
    <?php if (!$articles): ?><p class="text-soft py-10 text-center">No articles in this category.</p><?php endif; ?>
  </div>
</section>
<?php
if ($inApp) { require __DIR__ . '/../app/views/partials/app_foot.php'; }
else { require __DIR__ . '/../app/views/partials/public_foot.php'; }

<?php
require __DIR__ . '/../app/bootstrap.php';
$inApp = Auth::check();

$slug = trim($_GET['slug'] ?? '');
$a = Database::one(
    "SELECT a.*, ac.name cat_name, ac.slug cat_slug, u.name author
       FROM articles a
       LEFT JOIN article_categories ac ON ac.id=a.category_id
       LEFT JOIN users u ON u.id=a.author_id
      WHERE a.slug=? AND a.status='published' LIMIT 1",
    [$slug]
);
if (!$a) {
    http_response_code(404);
    $a = null;
}
if ($a) { Database::run("UPDATE articles SET views = views + 1 WHERE id=?", [$a['id']]); }

if ($inApp) {
    $title = $a ? $a['title'] : 'Article'; $nav = 'blog';
    require __DIR__ . '/../app/views/partials/app_head.php';
} else {
    $seo = $a ? [
        'title' => $a['title'] . ' · WiseWallet',
        'description' => $a['excerpt'],
        'type' => 'article',
        'jsonld' => [
            '@context' => 'https://schema.org', '@type' => 'Article',
            'headline' => $a['title'], 'description' => $a['excerpt'],
            'author' => ['@type' => 'Person', 'name' => $a['author'] ?? 'WiseWallet'],
            'datePublished' => $a['published_at'], 'publisher' => ['@type' => 'Organization', 'name' => 'WiseWallet'],
        ],
    ] : ['title' => 'Article not found', 'noindex' => true];
    require __DIR__ . '/../app/views/partials/public_head.php';
}
?>
<article class="max-w-2xl mx-auto <?= $inApp ? '' : 'px-4 sm:px-6 py-14' ?>">
  <?php if (!$a): ?>
    <div class="text-center py-20">
      <h1 class="font-display text-3xl font-semibold">Article not found</h1>
      <a href="/blog" class="btn-primary mt-6 inline-flex">Back to guides</a>
    </div>
  <?php else: ?>
    <a href="/blog" class="text-accent text-sm">&larr; Back to guides</a>
    <div class="flex items-center gap-2 mt-4 mb-3">
      <?php if ($a['cat_name']): ?><span class="badge-brand"><?= e($a['cat_name']) ?></span><?php endif; ?>
      <span class="text-xs text-soft"><?= (int) $a['reading_minutes'] ?> min read · <?= (int) $a['views'] ?> views</span>
    </div>
    <h1 class="font-display text-3xl sm:text-4xl font-semibold leading-tight"><?= e($a['title']) ?></h1>
    <div class="text-soft text-sm mt-3 mb-8">By <?= e($a['author'] ?? 'WiseWallet') ?> · <?= $a['published_at'] ? date('d M Y', strtotime($a['published_at'])) : '' ?></div>
    <div class="prose-ww space-y-4 leading-relaxed text-[15px]"><?= $a['body'] /* trusted admin-authored HTML */ ?></div>
    <div class="mt-12 card card-pad flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="font-display text-lg font-semibold">Put it into practice</h3>
        <p class="text-soft text-sm mt-1">Budgets, goals and simulators in one place.</p>
      </div>
      <a href="<?= $inApp ? '/dashboard.php' : '/register.php' ?>" class="btn-primary whitespace-nowrap"><?= $inApp ? 'Open dashboard' : 'Get started' ?></a>
    </div>
  <?php endif; ?>
</article>
<?php
if ($inApp) { require __DIR__ . '/../app/views/partials/app_foot.php'; }
else { require __DIR__ . '/../app/views/partials/public_foot.php'; }

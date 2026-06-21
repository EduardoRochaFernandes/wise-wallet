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
    $title = $a ? $a['title'] : 'Artigo'; $nav = 'blog';
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
    ] : ['title' => 'Artigo não encontrado', 'noindex' => true];
    require __DIR__ . '/../app/views/partials/public_head.php';
}
?>
<article class="max-w-3xl mx-auto <?= $inApp ? '' : 'px-4 sm:px-6 py-12' ?>">
  <?php if (!$a): ?>
    <div class="text-center py-20">
      <h1 class="text-3xl font-bold">Artigo não encontrado</h1>
      <a href="/blog.php" class="btn-primary mt-6 inline-flex">Voltar ao blog</a>
    </div>
  <?php else: ?>
    <a href="/blog.php" class="text-brand-400 text-sm">← Voltar ao blog</a>
    <div class="flex items-center gap-2 mt-4 mb-3">
      <?php if ($a['cat_name']): ?><span class="badge-brand"><?= e($a['cat_name']) ?></span><?php endif; ?>
      <span class="text-xs text-soft"><?= (int) $a['reading_minutes'] ?> min de leitura · <?= (int) $a['views'] ?> visualizações</span>
    </div>
    <h1 class="text-3xl sm:text-4xl font-extrabold leading-tight"><?= e($a['title']) ?></h1>
    <div class="text-soft text-sm mt-3 mb-8">Por <?= e($a['author'] ?? 'WiseWallet') ?> · <?= $a['published_at'] ? date('d/m/Y', strtotime($a['published_at'])) : '' ?></div>
    <div class="prose-ww space-y-4 leading-relaxed"><?= $a['body'] /* trusted admin-authored HTML */ ?></div>
    <div class="mt-12 card card-pad text-center">
      <h3 class="font-bold text-lg">Põe em prática com a WiseWallet</h3>
      <p class="text-soft text-sm mt-1">Orçamentos, objetivos e simuladores num só lugar.</p>
      <a href="<?= $inApp ? '/dashboard.php' : '/register.php' ?>" class="btn-primary mt-4 inline-flex"><?= $inApp ? 'Abrir dashboard' : 'Começar grátis' ?></a>
    </div>
  <?php endif; ?>
</article>
<?php
if ($inApp) { require __DIR__ . '/../app/views/partials/app_foot.php'; }
else { require __DIR__ . '/../app/views/partials/public_foot.php'; }

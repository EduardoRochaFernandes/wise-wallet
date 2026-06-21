<?php
require __DIR__ . '/../app/bootstrap.php';
$inApp = Auth::check();

$news = News::latest(14);
$items = $news['data'] ?? [];

if ($inApp) {
    $title = 'Notícias'; $nav = 'news';
    require __DIR__ . '/../app/views/partials/app_head.php';
} else {
    $seo = ['title' => 'Notícias financeiras · WiseWallet', 'description' => 'Mercados e economia em tempo real, de fontes públicas.'];
    $activeNav = 'news';
    require __DIR__ . '/../app/views/partials/public_head.php';
}
?>
<section class="max-w-6xl mx-auto <?= $inApp ? '' : 'px-4 sm:px-6 py-12' ?>">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold">Notícias financeiras</h1>
      <p class="text-soft text-sm mt-1">Fontes públicas (RSS) · <?= !empty($news['stale']) ? 'dados em cache' : 'atualizado agora' ?></p>
    </div>
  </div>

  <?php if ($inApp): ?>
    <div class="grid sm:grid-cols-2 gap-4 mb-6">
      <div class="card card-pad"><h2 class="font-bold mb-3 flex items-center gap-2"><?= icon('trending-up','w-4 h-4') ?> Criptomoedas (EUR)</h2><div id="crypto-widget" class="space-y-2 text-sm text-soft">A carregar…</div></div>
      <div class="card card-pad"><h2 class="font-bold mb-3 flex items-center gap-2"><?= icon('landmark','w-4 h-4') ?> Câmbios (base EUR)</h2><div id="fx-widget" class="grid grid-cols-2 gap-2 text-sm text-soft">A carregar…</div></div>
    </div>
  <?php endif; ?>

  <div class="grid gap-4 md:grid-cols-2">
    <?php foreach ($items as $n): ?>
      <a href="<?= e($n['url']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="card card-pad hover:shadow-glow transition-shadow block">
        <div class="text-xs text-soft mb-2"><?= e($n['source']) ?><?= $n['published_at'] ? ' · ' . e(date('d/m H:i', strtotime($n['published_at']))) : '' ?></div>
        <h3 class="font-semibold leading-snug"><?= e($n['title']) ?></h3>
        <?php if (!empty($n['summary'])): ?><p class="text-soft text-sm mt-2"><?= e($n['summary']) ?>…</p><?php endif; ?>
      </a>
    <?php endforeach; ?>
    <?php if (!$items): ?><div class="card card-pad text-soft col-span-2 text-center py-10">Notícias indisponíveis de momento. Tenta novamente mais tarde.</div><?php endif; ?>
  </div>
</section>

<?php if ($inApp): ?>
<script <?= nonce_attr() ?>>
(async function(){
  try {
    const c = await WW.api('/api/crypto.php');
    document.getElementById('crypto-widget').innerHTML = (c.coins||[]).map(x =>
      `<div class="flex justify-between"><span>${x.name}</span><span><strong class="text-ink">${(x.price||0).toLocaleString('pt-PT')} €</strong> <span class="${x.change>=0?'text-pos':'text-neg'}">${x.change>=0?'+':''}${x.change}%</span></span></div>`).join('');
  } catch(e){ document.getElementById('crypto-widget').textContent='Indisponível.'; }
  try {
    const f = await WW.api('/api/fx.php');
    document.getElementById('fx-widget').innerHTML = Object.entries(f.rates||{}).map(([k,v]) =>
      `<div class="flex justify-between"><span>${k}</span><strong class="text-ink">${(+v).toFixed(2)}</strong></div>`).join('');
  } catch(e){ document.getElementById('fx-widget').textContent='Indisponível.'; }
})();
</script>
<?php endif; ?>
<?php
if ($inApp) { require __DIR__ . '/../app/views/partials/app_foot.php'; }
else { require __DIR__ . '/../app/views/partials/public_foot.php'; }

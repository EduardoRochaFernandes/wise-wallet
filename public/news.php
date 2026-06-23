<?php
require __DIR__ . '/../app/bootstrap.php';
$inApp = Auth::check();

$news = MarketNews::latest(14);
$items = $news['data'] ?? [];

if ($inApp) {
    $title = 'Market news'; $nav = 'news';
    require __DIR__ . '/../app/views/partials/app_head.php';
} else {
    $seo = ['title' => 'Market news · WiseWallet', 'description' => 'Markets and economy in near real time, from public sources.'];
    $activeNav = 'news';
    require __DIR__ . '/../app/views/partials/public_head.php';
}
?>
<section class="max-w-5xl mx-auto <?= $inApp ? '' : 'px-4 sm:px-6 py-14' ?>">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="font-display text-2xl font-semibold">Market news</h1>
      <p class="text-soft text-sm mt-1">Public RSS sources · <?= !empty($news['stale']) ? 'showing cached data' : 'updated just now' ?></p>
    </div>
  </div>

  <?php if ($inApp): ?>
    <div class="grid sm:grid-cols-2 gap-5 mb-6">
      <div class="card card-pad"><h2 class="font-display font-semibold mb-3 flex items-center gap-2"><?= icon('trending-up','w-4 h-4') ?> Cryptocurrencies (EUR)</h2><div id="crypto-widget" class="space-y-2 text-sm text-soft">Loading…</div></div>
      <div class="card card-pad"><h2 class="font-display font-semibold mb-3 flex items-center gap-2"><?= icon('landmark','w-4 h-4') ?> Exchange rates (EUR base)</h2><div id="fx-widget" class="grid grid-cols-2 gap-2 text-sm text-soft">Loading…</div></div>
    </div>
  <?php endif; ?>

  <div class="divide-y" style="border-color:rgb(var(--line))">
    <?php foreach ($items as $n): $url = $n['url'] ?? ''; if ($url === '') continue; ?>
      <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer nofollow" class="block py-5 group" style="border-color:rgb(var(--line))">
        <div class="text-xs text-soft mb-1"><?= e($n['source'] ?? 'News') ?><?= !empty($n['published_at']) ? ' · ' . e(date('d M H:i', strtotime($n['published_at']))) : '' ?></div>
        <h3 class="font-medium group-hover:text-accent"><?= e($n['title'] ?? '') ?></h3>
        <?php if (!empty($n['summary'])): ?><p class="text-soft text-sm mt-1"><?= e($n['summary']) ?>…</p><?php endif; ?>
      </a>
    <?php endforeach; ?>
    <?php if (!$items): ?><div class="text-soft text-center py-10">News is unavailable right now. Please try again later.</div><?php endif; ?>
  </div>
</section>

<?php if ($inApp): ?>
<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', async function(){
  try {
    const c = await WW.api('/api/crypto.php');
    document.getElementById('crypto-widget').innerHTML = (c.coins||[]).map(x =>
      `<div class="flex justify-between"><span>${x.name}</span><span><strong class="text-ink">${(x.price||0).toLocaleString('en-GB')} €</strong> <span class="${x.change>=0?'text-pos':'text-neg'}">${x.change>=0?'+':''}${x.change}%</span></span></div>`).join('');
  } catch(e){ document.getElementById('crypto-widget').textContent='Unavailable.'; }
  try {
    const f = await WW.api('/api/fx.php');
    document.getElementById('fx-widget').innerHTML = Object.entries(f.rates||{}).map(([k,v]) =>
      `<div class="flex justify-between"><span>${k}</span><strong class="text-ink">${(+v).toFixed(2)}</strong></div>`).join('');
  } catch(e){ document.getElementById('fx-widget').textContent='Unavailable.'; }
});
</script>
<?php endif; ?>
<?php
if ($inApp) { require __DIR__ . '/../app/views/partials/app_foot.php'; }
else { require __DIR__ . '/../app/views/partials/public_foot.php'; }

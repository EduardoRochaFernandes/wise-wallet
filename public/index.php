<?php
require __DIR__ . '/../app/bootstrap.php';

$articles = Database::all(
    "SELECT slug, title, excerpt, reading_minutes FROM articles WHERE status='published' ORDER BY published_at DESC LIMIT 3"
);

$seo = [
    'title'       => 'WiseWallet — Finanças pessoais inteligentes do euro à reforma',
    'description' => 'Regista, entende, planeia, simula e aprende sobre o teu dinheiro num só lugar. Orçamentos, objetivos, investimentos, 8 simuladores e educação financeira. Grátis e seguro.',
    'jsonld'      => [
        '@context' => 'https://schema.org',
        '@type'    => 'SoftwareApplication',
        'name'     => 'WiseWallet',
        'applicationCategory' => 'FinanceApplication',
        'operatingSystem'     => 'Web',
        'offers'   => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
        'description' => 'Plataforma de finanças pessoais: transações, orçamentos, objetivos, investimentos, simuladores e educação financeira.',
    ],
];
require __DIR__ . '/../app/views/partials/public_head.php';

$features = [
    ['arrow-left-right', 'Transações unificadas', 'Receitas, despesas e transferências com categoria, conta, etiquetas e notas. Adição em 3 segundos.'],
    ['landmark', 'Contas múltiplas', 'À ordem, poupança, cartão, dinheiro, cripto e investimento — saldo sempre vivo.'],
    ['target', 'Orçamentos vivos', 'Limites por categoria com alertas verde → laranja → vermelho.'],
    ['star', 'Objetivos com marcos', 'Metas com marcos automáticos aos 25/50/75% e contagem de dias.'],
    ['trending-up', 'Investimentos reais', 'Ações, ETF, cripto e mais, com ganho/perda, ROI e diversificação.'],
    ['brain', 'Score de Saúde Financeira', 'Um número de 0 a 100 que resume a tua vida financeira inteira.'],
];
$cycle = [
    ['Registar', 'arrow-left-right', 'Capturas a tua realidade financeira.'],
    ['Entender', 'brain', 'Os dados viram padrões e insight.'],
    ['Planear', 'target', 'Controlas orçamentos, metas e faturas.'],
    ['Simular', 'calculator', 'Testas decisões antes de as tomares.'],
    ['Aprender', 'book-open', 'A educação fecha o ciclo.'],
];
?>
<!-- ── HERO ─────────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-media">
    <div class="hero-aurora"></div>
    <?php /* Drop a theme-matched loop at public/assets/video/hero.mp4 and it appears automatically; until then the animated aurora is the background. */ ?>
    <?php if (is_file(WW_PUBLIC . '/assets/video/hero.mp4')): ?>
    <video autoplay muted loop playsinline preload="auto">
      <source src="<?= asset('/assets/video/hero.mp4') ?>" type="video/mp4">
    </video>
    <?php endif; ?>
  </div>
  <div class="hero-veil"></div>

  <div class="relative max-w-5xl mx-auto px-4 sm:px-6 text-center py-24">
    <span class="badge-brand mb-6 inline-flex reveal"><?= icon('sparkles','w-4 h-4') ?> A tua vida financeira, num só lugar</span>
    <h1 class="text-5xl sm:text-7xl font-extrabold leading-[1.02] reveal">
      Vê o teu dinheiro<br>como <span class="text-gradient">nunca o viste</span>
    </h1>
    <p class="text-lg sm:text-xl text-soft mt-7 max-w-2xl mx-auto reveal">
      Do café de hoje à reforma de amanhã. Regista, entende, planeia, simula e aprende —
      num produto único onde cada peça alimenta a seguinte.
    </p>
    <div class="flex flex-wrap gap-3 justify-center mt-9 reveal">
      <a href="/register.php" class="btn-primary text-base px-6 py-3">Começar grátis <?= icon('chevron-right','w-4 h-4') ?></a>
      <a href="/login.php" class="btn-ghost text-base px-6 py-3">Ver demonstração</a>
    </div>
    <div class="mt-20 flex justify-center text-soft scroll-cue reveal"><?= icon('arrow-down','w-6 h-6') ?></div>
  </div>
</section>

<!-- ── Marquee ──────────────────────────────────────────────── -->
<div class="marquee py-6 border-y" style="border-color:rgb(var(--line))">
  <div class="marquee-track text-soft font-semibold uppercase tracking-widest text-sm">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <span>Transações</span><span>•</span><span>Orçamentos</span><span>•</span><span>Objetivos</span><span>•</span><span>Investimentos</span><span>•</span><span>8 Simuladores</span><span>•</span><span>Score de Saúde</span><span>•</span><span>Educação</span><span>•</span><span>Segurança Argon2id</span><span>•</span>
    <?php endfor; ?>
  </div>
</div>

<!-- ── Cycle ────────────────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
  <div class="text-center max-w-2xl mx-auto mb-12 reveal">
    <h2 class="text-3xl sm:text-4xl font-bold">Um ciclo, não uma lista de features</h2>
    <p class="text-soft mt-3">Registar → Entender → Planear → Simular → Aprender. E voltas ao início mais capaz.</p>
  </div>
  <div class="grid grid-cols-2 lg:grid-cols-5 gap-3" data-reveal-stagger>
    <?php foreach ($cycle as [$t, $ic, $d]): ?>
      <div class="card card-pad lift text-left">
        <div class="w-11 h-11 rounded-xl grid place-items-center mb-3 text-white" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"><?= icon($ic) ?></div>
        <div class="font-bold"><?= $t ?></div>
        <div class="text-xs text-soft mt-1"><?= $d ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Stats (count-up) ─────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 pb-8">
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4" data-reveal-stagger>
    <?php foreach ([['8','','Simuladores'],['17','','Conquistas'],['100','%','Open-source'],['0','€','Para sempre']] as [$n,$suf,$l]): ?>
      <div class="stat-card text-center lift">
        <div class="text-4xl font-extrabold text-brand-400"><span data-count="<?= $n ?>" data-suffix="<?= $suf ?>">0<?= $suf ?></span></div>
        <div class="text-sm text-soft mt-1"><?= $l ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Features ─────────────────────────────────────────────── -->
<section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
  <div class="text-center max-w-2xl mx-auto mb-12 reveal">
    <h2 class="text-3xl sm:text-4xl font-bold">Tudo o que precisas, ligado entre si</h2>
    <p class="text-soft mt-3">Os teus dados tornam-se compreensão, planos e melhores decisões.</p>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5" data-reveal-stagger>
    <?php foreach ($features as [$ic, $t, $d]): ?>
      <div class="card card-pad lift">
        <div class="w-12 h-12 rounded-xl grid place-items-center mb-4 text-brand-400" style="background:rgb(var(--brand) / .15)"><?= icon($ic,'w-6 h-6') ?></div>
        <h3 class="font-bold text-lg"><?= $t ?></h3>
        <p class="text-soft text-sm mt-2"><?= $d ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Simulators ───────────────────────────────────────────── -->
<section id="simuladores" class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
  <div class="card card-pad sm:p-12 relative overflow-hidden bg-grid reveal-scale reveal">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(50% 80% at 100% 0%, rgb(168 85 247 / .2), transparent 70%)"></div>
    <div class="relative grid lg:grid-cols-2 gap-8 items-center">
      <div>
        <span class="badge-brand mb-4 inline-flex"><?= icon('calculator','w-4 h-4') ?> 8 simuladores</span>
        <h2 class="text-3xl sm:text-4xl font-bold">Testa o "e se?" com números honestos</h2>
        <p class="text-soft mt-3">Crédito habitação, pessoal, poupança, reforma, investimento, IRS (escalões PT), leasing e fundo de emergência — fórmulas reais e gráficos.</p>
        <a href="/simulators.php" class="btn-primary mt-6">Explorar simuladores <?= icon('chevron-right','w-4 h-4') ?></a>
      </div>
      <div class="grid grid-cols-2 gap-3" data-reveal-stagger>
        <?php foreach ([['home','Crédito habitação'],['piggy-bank','Poupança'],['umbrella','Fundo emergência'],['landmark','IRS Portugal']] as [$ic,$t]): ?>
          <div class="rounded-xl p-4 lift" style="background:rgb(var(--surface-2))">
            <div class="text-brand-400 mb-2"><?= icon($ic) ?></div>
            <div class="text-sm font-semibold"><?= $t ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── Education ────────────────────────────────────────────── -->
<section id="educacao" class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
  <div class="flex items-end justify-between mb-8 reveal">
    <div>
      <h2 class="text-3xl sm:text-4xl font-bold">Aprende enquanto geres</h2>
      <p class="text-soft mt-2">Quem entende, decide melhor.</p>
    </div>
    <a href="/blog.php" class="btn-ghost btn-sm">Ver blog</a>
  </div>
  <div class="grid md:grid-cols-3 gap-5" data-reveal-stagger>
    <?php foreach ($articles as $a): ?>
      <a href="/article.php?slug=<?= e($a['slug']) ?>" class="card card-pad lift block">
        <div class="badge-brand text-xs mb-3"><?= (int) $a['reading_minutes'] ?> min de leitura</div>
        <h3 class="font-bold text-lg leading-snug"><?= e($a['title']) ?></h3>
        <p class="text-soft text-sm mt-2"><?= e($a['excerpt']) ?></p>
        <span class="text-brand-400 text-sm font-medium mt-3 inline-flex items-center gap-1">Ler artigo <?= icon('chevron-right','w-4 h-4') ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Final CTA ────────────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
  <div class="card card-pad sm:p-16 text-center relative overflow-hidden reveal">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(60% 120% at 50% 100%, rgb(99 102 241 / .28), transparent 70%)"></div>
    <div class="relative">
      <h2 class="text-3xl sm:text-5xl font-extrabold">Pronto para dominar o teu dinheiro?</h2>
      <p class="text-soft mt-4 max-w-xl mx-auto">Junta-te à WiseWallet e transforma a forma como vês as tuas finanças. Grátis, para sempre.</p>
      <a href="/register.php" class="btn-primary mt-8 inline-flex text-base px-6 py-3">Criar a minha conta <?= icon('chevron-right','w-4 h-4') ?></a>
    </div>
  </div>
</section>

<script src="<?= asset('/assets/js/landing.js') ?>"></script>
<?php require __DIR__ . '/../app/views/partials/public_foot.php';

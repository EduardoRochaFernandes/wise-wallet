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
    ['arrow-left-right', 'Transações unificadas', 'Receitas, despesas e transferências com categoria, conta, etiquetas e notas. Adição rápida em 3 segundos.'],
    ['landmark', 'Contas múltiplas', 'À ordem, poupança, cartão, dinheiro, cripto e investimento — saldo sempre atualizado.'],
    ['target', 'Orçamentos vivos', 'Limites por categoria com alertas automáticos verde → laranja → vermelho.'],
    ['star', 'Objetivos com marcos', 'Define metas, contribui ao teu ritmo e celebra marcos automáticos aos 25/50/75%.'],
    ['trending-up', 'Investimentos reais', 'Ações, ETF, cripto e mais, com ganho/perda, ROI e diversificação. Preços via API.'],
    ['brain', 'Score de Saúde Financeira', 'Um número de 0 a 100 que combina poupança, orçamentos, objetivos e diversificação.'],
];
$cycle = [
    ['Registar', 'arrow-left-right', 'Capturas a tua vida financeira real.'],
    ['Entender', 'brain', 'Transformas dados em padrões e insight.'],
    ['Planear', 'target', 'Controlas orçamentos, objetivos e faturas.'],
    ['Simular', 'calculator', 'Testas decisões antes de as tomares.'],
    ['Aprender', 'book-open', 'Educação financeira que fecha o ciclo.'],
];
?>
<!-- ── Hero ─────────────────────────────────────────────────── -->
<section class="relative overflow-hidden bg-grid">
  <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(60% 50% at 50% 0%, rgb(99 102 241 / .22), transparent 70%)"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 pt-20 pb-16 text-center">
    <span class="badge-brand mb-5 inline-flex"><?= icon('sparkles','w-4 h-4') ?> A tua vida financeira, num só lugar</span>
    <h1 class="text-4xl sm:text-6xl font-extrabold leading-[1.05] max-w-4xl mx-auto">
      Domina o teu dinheiro —<br><span class="text-transparent bg-clip-text" style="background-image:linear-gradient(135deg,#818cf8,#c084fc)">do euro de hoje à reforma de amanhã</span>
    </h1>
    <p class="text-lg text-soft mt-6 max-w-2xl mx-auto">
      WiseWallet une o ciclo completo das finanças pessoais: registas, entendes, planeias, simulas e aprendes. Moderno, gratuito e seguro por desenho.
    </p>
    <div class="flex flex-wrap gap-3 justify-center mt-8">
      <a href="/register.php" class="btn-primary">Começar grátis <?= icon('chevron-right','w-4 h-4') ?></a>
      <a href="/login.php" class="btn-ghost">Ver demonstração</a>
    </div>

    <!-- Cycle -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 max-w-4xl mx-auto mt-14">
      <?php foreach ($cycle as $i => [$t, $ic, $d]): ?>
        <div class="card card-pad text-left animate-fade-up" style="animation-delay:<?= $i * 80 ?>ms">
          <div class="w-10 h-10 rounded-xl grid place-items-center mb-3 text-white" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"><?= icon($ic) ?></div>
          <div class="font-bold"><?= $t ?></div>
          <div class="text-xs text-soft mt-1"><?= $d ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Stats ────────────────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach ([['8','Simuladores financeiros'],['17','Conquistas para desbloquear'],['100%','Open-source & seguro'],['0€','Para sempre grátis']] as [$n,$l]): ?>
      <div class="stat-card text-center">
        <div class="text-3xl font-extrabold text-brand-400"><?= $n ?></div>
        <div class="text-sm text-soft mt-1"><?= $l ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Features ─────────────────────────────────────────────── -->
<section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <h2 class="text-3xl font-bold">Tudo o que precisas, ligado entre si</h2>
    <p class="text-soft mt-3">Cada módulo alimenta o seguinte. Os teus dados tornam-se compreensão, planos e melhores decisões.</p>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($features as [$ic, $t, $d]): ?>
      <div class="card card-pad hover:shadow-glow transition-shadow">
        <div class="w-11 h-11 rounded-xl grid place-items-center mb-4 text-brand-400" style="background:rgb(var(--brand) / .15)"><?= icon($ic,'w-6 h-6') ?></div>
        <h3 class="font-bold text-lg"><?= $t ?></h3>
        <p class="text-soft text-sm mt-2"><?= $d ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Simulators ───────────────────────────────────────────── -->
<section id="simuladores" class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
  <div class="card card-pad sm:p-10 relative overflow-hidden bg-grid">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(50% 80% at 100% 0%, rgb(168 85 247 / .18), transparent 70%)"></div>
    <div class="relative grid lg:grid-cols-2 gap-8 items-center">
      <div>
        <span class="badge-brand mb-4 inline-flex"><?= icon('calculator','w-4 h-4') ?> 8 simuladores</span>
        <h2 class="text-3xl font-bold">Testa decisões antes de as tomares</h2>
        <p class="text-soft mt-3">Crédito habitação, crédito pessoal, poupança, reforma, investimento, IRS (escalões PT), leasing e fundo de emergência — com fórmulas reais e gráficos.</p>
        <a href="/simulators.php" class="btn-primary mt-6">Explorar simuladores <?= icon('chevron-right','w-4 h-4') ?></a>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <?php foreach ([['home','Crédito habitação'],['piggy-bank','Poupança'],['umbrella','Fundo emergência'],['landmark','IRS Portugal']] as [$ic,$t]): ?>
          <div class="rounded-xl p-4" style="background:rgb(var(--surface-2))">
            <div class="text-brand-400 mb-2"><?= icon($ic) ?></div>
            <div class="text-sm font-semibold"><?= $t ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── Education / blog ─────────────────────────────────────── -->
<section id="educacao" class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
  <div class="flex items-end justify-between mb-8">
    <div>
      <h2 class="text-3xl font-bold">Aprende enquanto geres</h2>
      <p class="text-soft mt-2">Educação financeira integrada. Quem entende, decide melhor.</p>
    </div>
    <a href="/blog.php" class="btn-ghost btn-sm">Ver blog</a>
  </div>
  <div class="grid md:grid-cols-3 gap-5">
    <?php foreach ($articles as $a): ?>
      <a href="/article.php?slug=<?= e($a['slug']) ?>" class="card card-pad hover:shadow-glow transition-shadow block">
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
  <div class="card card-pad sm:p-12 text-center relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(60% 100% at 50% 100%, rgb(99 102 241 / .25), transparent 70%)"></div>
    <div class="relative">
      <h2 class="text-3xl sm:text-4xl font-extrabold">Pronto para dominar o teu dinheiro?</h2>
      <p class="text-soft mt-3 max-w-xl mx-auto">Junta-te à WiseWallet e transforma a forma como vês as tuas finanças. Grátis, para sempre.</p>
      <a href="/register.php" class="btn-primary mt-7 inline-flex">Criar a minha conta <?= icon('chevron-right','w-4 h-4') ?></a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../app/views/partials/public_foot.php';

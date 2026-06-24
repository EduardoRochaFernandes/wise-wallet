<?php
require __DIR__ . '/../app/bootstrap.php';

$articles = Database::all(
    "SELECT slug, title, excerpt, reading_minutes FROM articles WHERE status='published' ORDER BY published_at DESC LIMIT 3"
);

$seo = [
    'title'       => 'WiseWallet — a clear ledger for your whole financial life',
    'description' => 'Record income and spending, set budgets and goals, track investments, and run real simulations — in one fast, private place. Free and secure.',
    'jsonld'      => [
        '@context' => 'https://schema.org',
        '@type'    => 'SoftwareApplication',
        'name'     => 'WiseWallet',
        'applicationCategory' => 'FinanceApplication',
        'operatingSystem'     => 'Web',
        'offers'   => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
        'description' => 'Personal-finance platform: transactions, budgets, goals, investments, simulators and financial education.',
    ],
];
require __DIR__ . '/../app/views/partials/public_head.php';

// One repeated primitive: the "ledger row" (label + meaning).
$features = [
    ['Transactions', 'Income, expenses and transfers with categories, accounts, tags and notes. Balances stay in sync automatically.'],
    ['Accounts', 'Current, savings, card, cash, crypto and investment accounts — each balance updated on every entry.'],
    ['Budgets', 'A monthly limit per category, with quiet warnings as you approach it.'],
    ['Goals', 'Targets with deadlines and automatic 25 / 50 / 75% milestones.'],
    ['Investments', 'Stocks, ETFs, crypto, bonds and more — with gain/loss, ROI and diversification.'],
    ['Insights', 'Twelve-month cash flow, spend by category and weekday, and a Financial Health Score from 0 to 100.'],
    ['Achievements', 'Twenty-five milestones that unlock as you actually use the platform — not just for opening the app.'],
    ['Notifications', 'Email and in-app alerts for new sign-ins, bills due, budget warnings, and unlocked achievements.'],
];
$tools = [
    'Mortgage', 'Personal loan', 'Savings', 'Retirement',
    'Investment', 'Portuguese income tax', 'Car leasing', 'Emergency fund',
];
?>
<!-- ── Hero (left-aligned, with a real statement preview) ─────── -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 pt-16 pb-20 grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
  <div class="reveal">
    <p class="eyebrow mb-5">Personal finance, kept honestly</p>
    <h1 class="text-4xl sm:text-5xl lg:text-6xl leading-[1.05]">A clear ledger for your whole financial life.</h1>
    <p class="text-lg text-soft mt-6 max-w-xl">
      Record income and spending, set budgets and goals, track investments and run
      real simulations — in one fast, private place. Free, and yours.
    </p>
    <div class="flex flex-wrap items-center gap-4 mt-8">
      <a href="/register" class="btn-primary px-5 py-2.5">Start keeping the books</a>
      <a href="/login" class="text-sm font-medium text-accent hover:underline">Try the demo account &rarr;</a>
    </div>
    <p class="text-xs text-soft mt-6">Eight simulators · twelve-month analysis · Argon2id, CSRF &amp; CSP security.</p>
  </div>

  <!-- Statement preview -->
  <div class="reveal">
    <div class="card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-3 border-b" style="border-color:rgb(var(--line))">
        <span class="font-display font-semibold">This month</span>
        <span class="badge-brand">Checking</span>
      </div>
      <div class="divide-y text-sm" style="--tw-divide-opacity:1">
        <?php
        $rows = [
            ['Salary', '+1,850.00', 'pos'],
            ['Rent', '−650.00', 'neg'],
            ['Groceries', '−213.75', 'neg'],
            ['Freelance', '+320.00', 'pos'],
            ['Subscriptions', '−50.97', 'neg'],
        ];
        foreach ($rows as [$label, $amt, $cls]): ?>
          <div class="flex items-center justify-between px-5 py-3" style="border-color:rgb(var(--line))">
            <span><?= e($label) ?></span>
            <span class="amount font-medium text-<?= $cls ?>"><?= e($amt) ?> &euro;</span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="flex items-center justify-between px-5 py-3 border-t" style="border-color:rgb(var(--line));background:rgb(var(--surface-2))">
        <span class="text-soft">Net this month</span>
        <span class="amount font-display font-semibold text-pos">+1,255.28 &euro;</span>
      </div>
    </div>
    <div class="grid grid-cols-3 gap-px mt-px text-center text-xs">
      <div class="card-pad py-3"><div class="font-display text-xl font-semibold">41%</div><div class="text-soft mt-0.5">savings rate</div></div>
      <div class="card-pad py-3"><div class="font-display text-xl font-semibold">78</div><div class="text-soft mt-0.5">health score</div></div>
      <div class="card-pad py-3"><div class="font-display text-xl font-semibold">5</div><div class="text-soft mt-0.5">accounts</div></div>
    </div>
  </div>
</section>

<!-- ── Scenic band ──────────────────────────────────────────── -->
<section class="hero-band reveal">
  <div class="hero-band-media">
    <?php if (is_file(WW_PUBLIC . '/assets/video/hero.mp4')): ?>
      <video autoplay muted loop playsinline preload="auto">
        <source src="<?= asset('/assets/video/hero.mp4') ?>" type="video/mp4">
      </video>
    <?php else: ?>
      <img src="https://images.unsplash.com/photo-1507709364617-197d5065ed49?fm=jpg&amp;q=70&amp;w=2000&amp;auto=format&amp;fit=crop"
           alt="The Ribeira riverside in Porto, Portugal" loading="lazy">
    <?php endif; ?>
  </div>
  <div class="hero-band-veil"></div>
  <div class="hero-band-copy">
    <p class="eyebrow mb-3" style="color:#cbd9cf">Built in Portugal</p>
    <p class="font-display text-2xl sm:text-3xl text-white max-w-md">A ledger as honest and unhurried as a walk along the Douro.</p>
  </div>
</section>

<!-- ── Features as a ledger of capabilities ──────────────────── -->
<section id="features" class="max-w-6xl mx-auto px-4 sm:px-6 py-20">
  <div class="grid lg:grid-cols-[1fr_2fr] gap-10">
    <div class="reveal">
      <p class="eyebrow mb-4">What's inside</p>
      <h2 class="text-3xl sm:text-4xl">Everything connects.</h2>
      <p class="text-soft mt-4 max-w-sm">Each part feeds the next — your records become understanding, plans and better decisions.</p>
    </div>
    <div class="divide-y" data-reveal-stagger style="border-color:rgb(var(--line))">
      <?php foreach ($features as [$name, $desc]): ?>
        <div class="grid sm:grid-cols-[180px_1fr] gap-2 sm:gap-6 py-5" style="border-color:rgb(var(--line))">
          <h3 class="font-display text-lg font-semibold"><?= e($name) ?></h3>
          <p class="text-soft text-sm leading-relaxed"><?= e($desc) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Tools / simulators ────────────────────────────────────── -->
<section id="tools" class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
  <div class="card card-pad sm:p-10">
    <div class="grid lg:grid-cols-2 gap-10 items-center">
      <div class="reveal">
        <p class="eyebrow mb-4">Decide with numbers</p>
        <h2 class="text-3xl sm:text-4xl">Eight honest simulators.</h2>
        <p class="text-soft mt-4 max-w-md">Test the "what if" before you commit — real formulas, plain results, a chart for each.</p>
        <a href="/simulators" class="btn-outline btn-sm mt-6">Open the simulators</a>
      </div>
      <div class="grid grid-cols-2 gap-px" data-reveal-stagger>
        <?php foreach ($tools as $tname): ?>
          <div class="px-4 py-3 text-sm border-t sm:border-t-0 sm:border-l" style="border-color:rgb(var(--line))"><?= e($tname) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── Guides ────────────────────────────────────────────────── -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
  <div class="flex items-end justify-between mb-6 reveal">
    <div>
      <p class="eyebrow mb-3">Learn as you go</p>
      <h2 class="text-3xl sm:text-4xl">Guides</h2>
    </div>
    <a href="/blog" class="text-sm font-medium text-accent hover:underline">All guides &rarr;</a>
  </div>
  <div class="divide-y" data-reveal-stagger style="border-color:rgb(var(--line))">
    <?php foreach ($articles as $a): ?>
      <a href="/article?slug=<?= e($a['slug']) ?>" class="grid sm:grid-cols-[1fr_auto] gap-2 sm:gap-8 py-5 group" style="border-color:rgb(var(--line))">
        <div>
          <h3 class="font-display text-lg font-semibold group-hover:text-accent"><?= e($a['title']) ?></h3>
          <p class="text-soft text-sm mt-1 max-w-2xl"><?= e($a['excerpt']) ?></p>
        </div>
        <div class="text-soft text-xs whitespace-nowrap sm:text-right pt-1"><?= (int) $a['reading_minutes'] ?> min read</div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── CTA ───────────────────────────────────────────────────── -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-16">
  <div class="card card-pad sm:p-12 flex flex-col sm:flex-row sm:items-center justify-between gap-6 reveal">
    <div>
      <h2 class="text-2xl sm:text-3xl">Start keeping the books.</h2>
      <p class="text-soft mt-2">Free forever. No card. Your data stays yours.</p>
    </div>
    <a href="/register" class="btn-primary px-5 py-2.5 whitespace-nowrap">Create your account</a>
  </div>
</section>

<script src="<?= asset('/assets/js/landing.js') ?>"></script>
<?php require __DIR__ . '/../app/views/partials/public_foot.php';

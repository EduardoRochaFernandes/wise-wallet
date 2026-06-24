<?php
require __DIR__ . '/../app/bootstrap.php';
$inApp = Auth::check();

$seo = [
    'title'       => 'Privacy & About this project · WiseWallet',
    'description' => 'WiseWallet is a simulated personal-finance platform built as a Portuguese PAP (Prova de Aptidão Profissional) project.',
];

if ($inApp) {
    $title = 'Privacy & About'; $nav = '';
    require __DIR__ . '/../app/views/partials/app_head.php';
} else {
    require __DIR__ . '/../app/views/partials/public_head.php';
}
?>
<section class="max-w-2xl mx-auto <?= $inApp ? '' : 'px-4 sm:px-6 py-14' ?>">
  <p class="eyebrow mb-3">About this project</p>
  <h1 class="font-display text-3xl sm:text-4xl font-semibold">Privacy &amp; about WiseWallet</h1>
  <p class="text-soft mt-3">Last updated <?= date('d M Y') ?></p>

  <div class="prose-ww space-y-5 mt-8 leading-relaxed text-[15px]">
    <p>
      WiseWallet is a <strong>simulated personal-finance platform</strong>. No real money,
      bank connection, or financial institution is involved at any point — every account
      balance, transaction, and market figure either originates from data the user enters
      themselves or from public, informational sources (currency rates, cryptocurrency
      prices, and financial news) used strictly for educational display.
    </p>

    <h2 class="font-display text-xl font-semibold pt-2">Academic context</h2>
    <p>
      This project was developed in the context of a <strong>Prova de Aptidão Profissional
      (PAP)</strong> — the final-year capstone project required for the 12th-grade
      Professional/Technical course — at <strong>Colégio de Gaia</strong>, a Catholic school
      in Vila Nova de Gaia, Portugal.
    </p>
    <p>
      Author: <strong>Eduardo da Rocha Fernandes</strong>. The project was awarded a final
      grade of <strong>20 out of 20</strong>, and the supervising teachers recommended it for
      submission among the <strong>best PAP projects in Portugal</strong>, to be presented in
      Lisbon.
    </p>

    <h2 class="font-display text-xl font-semibold pt-2">What data is stored</h2>
    <ul class="list-disc pl-5 space-y-1.5">
      <li>Account details you provide at registration (name, email, a securely hashed password).</li>
      <li>Whatever financial data you choose to enter — transactions, accounts, budgets, goals,
        bills, subscriptions, and investments — all scoped to your account only.</li>
      <li>Security/audit metadata (login timestamps, IP address, device fingerprint) used solely
        to protect your account, as described in the project's <code>SECURITY.md</code> file.</li>
    </ul>
    <p>No data is sold, shared with third parties, or used for advertising. This is a portfolio
      and academic demonstration, not a commercial product.</p>

    <h2 class="font-display text-xl font-semibold pt-2">Source code</h2>
    <p>
      WiseWallet is open-source. The complete codebase, security checklist, and setup
      instructions are available in the project repository linked from the homepage footer.
    </p>
  </div>
</section>
<?php
if ($inApp) { require __DIR__ . '/../app/views/partials/app_foot.php'; }
else { require __DIR__ . '/../app/views/partials/public_foot.php'; }

<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();

$steps = [
    ['Record', 'arrow-left-right', 'Capture your real financial life', [
        'Use the <strong>Add</strong> button (top right, or the quick-add shortcut) to log income, expenses, and transfers in seconds.',
        'Create one account per real-world account — checking, savings, cash, card, crypto — so each balance updates automatically.',
        'Add a short note on anything unusual; future-you will thank present-you.',
    ], '/transactions'],
    ['Understand', 'brain', 'Turn entries into insight', [
        'The <strong>Dashboard</strong> summarises net worth, monthly income/expenses, and your savings rate at a glance.',
        '<strong>Insights</strong> breaks spending down by category and weekday, and computes your Financial Health Score (0–100).',
        'Toggle <strong>Privacy mode</strong> (the eye icon) to blur amounts instantly when someone else can see your screen.',
    ], '/analytics'],
    ['Plan', 'target', 'Stay ahead of your money', [
        'Set a <strong>Budget</strong> per category — you will see a quiet warning as you approach the limit.',
        'Create <strong>Goals</strong> with a target and deadline; contributions create automatic 25/50/75% milestones.',
        '<strong>Bills</strong> tracks due dates and flags anything overdue; <strong>Subscriptions</strong> totals your recurring spend.',
    ], '/budgets'],
    ['Simulate', 'calculator', 'Decide before you commit', [
        'Open <strong>Simulators</strong> for mortgages, loans, savings growth, retirement, investment returns, Portuguese income tax, leasing, and an emergency-fund planner.',
        'Every simulator updates live as you change the numbers — no submit button needed.',
    ], '/simulators'],
    ['Learn', 'book-open', 'Close the loop', [
        'The <strong>Guides</strong> section has in-depth articles on budgeting, saving, credit, and investing.',
        'Guides are personalised: WiseWallet looks at signals like your savings rate or overdue bills and recommends what is most relevant right now.',
        'Check <strong>Achievements</strong> for milestones as you use the platform — there are 25 to unlock.',
    ], '/blog'],
];

$title = 'How WiseWallet works';
$nav = '';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<p class="text-soft mb-8 max-w-2xl">WiseWallet follows one simple cycle: record what happens, understand what it means, plan what comes next, simulate the big decisions, and learn so next time is easier.</p>

<div class="space-y-8">
  <?php foreach ($steps as $i => [$label, $ic, $tagline, $points, $cta]): ?>
    <div class="card card-pad flex flex-col sm:flex-row gap-5">
      <div class="flex sm:flex-col items-center sm:items-start gap-3 sm:w-40 shrink-0">
        <div class="w-10 h-10 rounded grid place-items-center text-white shrink-0" style="background:rgb(var(--accent))"><?= icon($ic, 'w-5 h-5') ?></div>
        <div>
          <div class="text-xs text-soft">Step <?= $i + 1 ?></div>
          <div class="font-display font-semibold"><?= e($label) ?></div>
        </div>
      </div>
      <div class="flex-1">
        <p class="font-medium mb-2"><?= e($tagline) ?></p>
        <ul class="list-disc pl-5 space-y-1.5 text-sm text-soft">
          <?php foreach ($points as $p): ?><li><?= $p /* trusted static copy */ ?></li><?php endforeach; ?>
        </ul>
        <a href="<?= e($cta) ?>" class="btn-outline btn-sm mt-4">Try it</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

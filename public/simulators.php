<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();

// key => [label, icon, [ [name,label,default,step], ... ] ]
$sims = [
    'mortgage'   => ['Mortgage', 'home', [['amount', 'Amount (€)', 150000, '1000'], ['rate', 'Annual rate (%)', 3.5, '0.1'], ['years', 'Term (years)', 30, '1']]],
    'personal'   => ['Personal loan', 'wallet', [['amount', 'Amount (€)', 10000, '500'], ['rate', 'APR (%)', 7.9, '0.1'], ['months', 'Months', 60, '1']]],
    'savings'    => ['Savings', 'piggy-bank', [['initial', 'Initial (€)', 1000, '100'], ['monthly', 'Monthly top-up (€)', 200, '10'], ['rate', 'Annual return (%)', 5, '0.1'], ['years', 'Years', 15, '1']]],
    'retirement' => ['Retirement', 'umbrella', [['age', 'Current age', 30, '1'], ['retage', 'Retirement age', 67, '1'], ['current', 'Saved (€)', 5000, '500'], ['monthly', 'Top-up / mo (€)', 200, '10'], ['rate', 'Return (%)', 5, '0.1'], ['income', 'Income / mo (€)', 1000, '50'], ['duration', 'Years in retirement', 25, '1']]],
    'investment' => ['Investment', 'trending-up', [['initial', 'Amount (€)', 10000, '500'], ['rate', 'Annual return (%)', 7, '0.1'], ['years', 'Years', 20, '1'], ['inflation', 'Inflation (%)', 2.5, '0.1']]],
    'irs'        => ['Income tax (PT)', 'landmark', [['income', 'Gross annual income (€)', 25000, '500']]],
    'leasing'    => ['Car leasing', 'trending-up', [['price', 'Price (€)', 30000, '500'], ['entry', 'Down payment (%)', 10, '1'], ['residual', 'Residual value (%)', 20, '1'], ['months', 'Months', 48, '1'], ['rate', 'Annual rate (%)', 6, '0.1']]],
    'emergency'  => ['Emergency fund', 'shield', [['expenses', 'Monthly expenses (€)', 1200, '50'], ['months', 'Target months', 6, '1'], ['current', 'Already saved (€)', 1500, '100'], ['save', 'Saving / mo (€)', 250, '10']]],
];

$title = 'Simulators';
$nav = 'simulators';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<p class="text-soft mb-5 max-w-2xl">Test the "what if" before you commit — real formulas with charts. Figures are educational estimates and not financial advice.</p>

<div class="grid lg:grid-cols-[220px_1fr] gap-5">
  <div class="card card-pad h-fit space-y-1">
    <?php foreach ($sims as $key => [$label, $ic, $fields]): ?>
      <button class="nav-link w-full" data-sim="<?= $key ?>"><?= icon($ic, 'w-4 h-4') ?><span><?= $label ?></span></button>
    <?php endforeach; ?>
  </div>

  <div>
    <?php foreach ($sims as $key => [$label, $ic, $fields]): ?>
      <div id="panel-<?= $key ?>" class="sim-panel hidden">
        <div class="card card-pad mb-4">
          <h2 class="font-display font-semibold text-xl mb-4"><?= e($label) ?></h2>
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($fields as [$n, $l, $def, $step]): ?>
              <div><label class="label"><?= e($l) ?></label><input type="number" name="<?= e($n) ?>" value="<?= e((string) $def) ?>" step="<?= e($step) ?>" class="input"></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="sim-out grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4"></div>
        <div class="card card-pad"><div class="sim-chart"></div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="<?= asset('/assets/js/simulators.js') ?>"></script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

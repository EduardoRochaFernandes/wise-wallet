<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();

// key => [label, icon, [ [name,label,default,step], ... ] ]
$sims = [
    'mortgage'   => ['Crédito Habitação', 'home', [['amount', 'Montante (€)', 150000, '1000'], ['rate', 'Taxa anual (%)', 3.5, '0.1'], ['years', 'Prazo (anos)', 30, '1']]],
    'personal'   => ['Crédito Pessoal', 'wallet', [['amount', 'Montante (€)', 10000, '500'], ['rate', 'TAEG (%)', 7.9, '0.1'], ['months', 'Meses', 60, '1']]],
    'savings'    => ['Poupança', 'piggy-bank', [['initial', 'Inicial (€)', 1000, '100'], ['monthly', 'Reforço mensal (€)', 200, '10'], ['rate', 'Retorno anual (%)', 5, '0.1'], ['years', 'Anos', 15, '1']]],
    'retirement' => ['Reforma', 'umbrella', [['age', 'Idade atual', 30, '1'], ['retage', 'Idade reforma', 67, '1'], ['current', 'Poupado (€)', 5000, '500'], ['monthly', 'Reforço/mês (€)', 200, '10'], ['rate', 'Retorno (%)', 5, '0.1'], ['income', 'Renda desejada/mês (€)', 1000, '50'], ['duration', 'Anos de reforma', 25, '1']]],
    'investment' => ['Investimento', 'trending-up', [['initial', 'Montante (€)', 10000, '500'], ['rate', 'Retorno anual (%)', 7, '0.1'], ['years', 'Anos', 20, '1'], ['inflation', 'Inflação (%)', 2.5, '0.1']]],
    'irs'        => ['IRS Portugal', 'landmark', [['income', 'Rendimento bruto anual (€)', 25000, '500']]],
    'leasing'    => ['Leasing Auto', 'trending-up', [['price', 'Preço (€)', 30000, '500'], ['entry', 'Entrada (%)', 10, '1'], ['residual', 'Valor residual (%)', 20, '1'], ['months', 'Meses', 48, '1'], ['rate', 'Taxa anual (%)', 6, '0.1']]],
    'emergency'  => ['Fundo de Emergência', 'shield', [['expenses', 'Despesas mensais (€)', 1200, '50'], ['months', 'Meses alvo', 6, '1'], ['current', 'Já poupado (€)', 1500, '100'], ['save', 'Poupança/mês (€)', 250, '10']]],
];

$title = 'Simuladores';
$nav = 'simulators';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<p class="text-soft mb-5 max-w-2xl">Testa decisões antes de as tomares — fórmulas reais com gráficos. Os valores são estimativas educativas e não constituem aconselhamento financeiro.</p>

<div class="grid lg:grid-cols-[240px_1fr] gap-4">
  <!-- Tabs -->
  <div class="card card-pad h-fit space-y-1">
    <?php foreach ($sims as $key => [$label, $ic, $fields]): ?>
      <button class="nav-link w-full" data-sim="<?= $key ?>"><?= icon($ic) ?><span><?= $label ?></span></button>
    <?php endforeach; ?>
  </div>

  <!-- Panels -->
  <div>
    <?php foreach ($sims as $key => [$label, $ic, $fields]): ?>
      <div id="panel-<?= $key ?>" class="sim-panel hidden">
        <div class="card card-pad mb-4">
          <h2 class="font-bold text-xl mb-4"><?= e($label) ?></h2>
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

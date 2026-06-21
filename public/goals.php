<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$goals = Finance::goals($uid);

$title = 'Objetivos';
$nav = 'goals';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<div class="flex justify-between items-center mb-5">
  <h2 class="font-bold text-lg">As tuas metas</h2>
  <button class="btn-primary" data-modal-open="#m-goal"><?= icon('plus','w-4 h-4') ?> Novo objetivo</button>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
  <?php foreach ($goals as $g): $done = $g['status'] === 'completed' || $g['pct'] >= 100; ?>
    <div class="card card-pad">
      <div class="flex items-start justify-between">
        <div class="w-11 h-11 rounded-xl grid place-items-center text-white" style="background:<?= e($g['color']) ?>"><?= icon('star') ?></div>
        <button class="btn-ghost btn-sm p-1.5" data-del="/api/goals.php" data-id="<?= $g['id'] ?>">✕</button>
      </div>
      <h3 class="font-bold mt-3"><?= e($g['name']) ?></h3>
      <div class="text-sm text-soft sensitive"><?= money($g['current_amount']) ?> de <?= money($g['target_amount']) ?></div>
      <div class="progress mt-3"><span style="width:<?= $g['pct'] ?>%;background:<?= e($g['color']) ?>"></span></div>
      <div class="flex items-center justify-between mt-2">
        <div class="flex gap-1">
          <?php foreach ([25, 50, 75, 100] as $m): ?>
            <span class="text-xs px-1.5 py-0.5 rounded <?= $g['pct'] >= $m ? 'text-white' : 'text-soft' ?>" style="<?= $g['pct'] >= $m ? 'background:' . e($g['color']) : 'background:rgb(var(--surface-2))' ?>"><?= $m ?></span>
          <?php endforeach; ?>
        </div>
        <span class="text-xs text-soft"><?= $g['days_left'] !== null ? max(0, $g['days_left']) . ' dias' : 'sem prazo' ?></span>
      </div>
      <?php if ($done): ?>
        <div class="badge-pos mt-3 w-full justify-center">Concluído 🎉</div>
      <?php else: ?>
        <button class="btn-ghost btn-sm w-full mt-3 js-contrib" data-id="<?= $g['id'] ?>" data-name="<?= e($g['name']) ?>">Contribuir</button>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$goals): ?><div class="card card-pad text-soft col-span-3 text-center py-10">Define o teu primeiro objetivo financeiro.</div><?php endif; ?>
</div>

<div id="m-goal" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold">Novo objetivo</h3><button class="btn-ghost btn-sm p-2" data-modal-close>✕</button></div>
    <form data-api-form="/api/goals.php" class="space-y-3">
      <div><label class="label">Nome</label><input name="name" required class="input" placeholder="Ex.: Fundo de emergência"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Objetivo (€)</label><input name="target_amount" type="number" step="0.01" min="0" required class="input" placeholder="6000"></div>
        <div><label class="label">Já tens (€)</label><input name="current_amount" type="number" step="0.01" min="0" value="0" class="input"></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Prazo</label><input name="deadline" type="date" class="input"></div>
        <div><label class="label">Cor</label><input name="color" type="color" value="#22c55e" class="input h-11 p-1"></div>
      </div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Criar</button><button type="button" class="btn-ghost" data-modal-close>Cancelar</button></div>
    </form>
  </div>
</div>

<div id="m-contrib" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold">Contribuir para <span id="contrib-name"></span></h3><button class="btn-ghost btn-sm p-2" data-modal-close>✕</button></div>
    <form data-api-form="/api/goals.php" data-method="PATCH" class="space-y-3">
      <input type="hidden" name="id" id="contrib-id">
      <div><label class="label">Valor (€)</label><input name="amount" type="number" step="0.01" min="0" required class="input" placeholder="100"></div>
      <div><label class="label">Nota <span class="text-soft">(opcional)</span></label><input name="note" class="input" placeholder="Ex.: poupança do mês"></div>
      <div class="flex gap-2 pt-1"><button type="submit" class="btn-primary flex-1">Adicionar</button><button type="button" class="btn-ghost" data-modal-close>Cancelar</button></div>
    </form>
  </div>
</div>

<script <?= nonce_attr() ?>>
document.querySelectorAll('.js-contrib').forEach((b) => b.addEventListener('click', () => {
  document.getElementById('contrib-id').value = b.dataset.id;
  document.getElementById('contrib-name').textContent = b.dataset.name;
  document.getElementById('m-contrib').classList.remove('hidden');
}));
</script>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

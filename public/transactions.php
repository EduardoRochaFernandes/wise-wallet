<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();

$filters = array_filter([
    'type'        => $_GET['type'] ?? '',
    'account_id'  => $_GET['account_id'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'q'           => trim($_GET['q'] ?? ''),
    'from'        => $_GET['from'] ?? '',
    'to'          => $_GET['to'] ?? '',
], fn($v) => $v !== '');

$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 20;
$total = Finance::transactionsCount($uid, $filters);
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$rows = Finance::transactions($uid, $filters, $per, ($page - 1) * $per);
$accounts = Finance::accounts($uid);
$cats = Finance::categories($uid);

$qbase = $_GET; unset($qbase['page']);
$qs = fn(int $p) => '?' . http_build_query(array_merge($qbase, ['page' => $p]));

$title = 'Transações';
$nav = 'transactions';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<form method="get" class="card card-pad mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
  <div class="lg:col-span-2"><label class="label">Pesquisar</label><input name="q" value="<?= e($filters['q'] ?? '') ?>" class="input" placeholder="Descrição ou notas…"></div>
  <div><label class="label">Tipo</label><select name="type" class="select">
    <option value="">Todos</option>
    <?php foreach (['income' => 'Receita', 'expense' => 'Despesa', 'transfer' => 'Transferência'] as $k => $v): ?>
      <option value="<?= $k ?>" <?= ($filters['type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select></div>
  <div><label class="label">Conta</label><select name="account_id" class="select"><option value="">Todas</option>
    <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>" <?= ($filters['account_id'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
  </select></div>
  <div><label class="label">De</label><input type="date" name="from" value="<?= e($filters['from'] ?? '') ?>" class="input"></div>
  <div class="flex items-end gap-2"><button class="btn-primary flex-1">Filtrar</button><a href="/transactions.php" class="btn-ghost">Limpar</a></div>
</form>

<div class="card overflow-hidden">
  <div class="flex items-center justify-between p-4 gap-2 flex-wrap">
    <div class="text-sm text-soft"><strong class="text-ink"><?= $total ?></strong> transações</div>
    <div class="flex gap-2">
      <a href="/api/export.php?format=csv" class="btn-ghost btn-sm">Exportar CSV</a>
      <a href="/api/export.php?format=pdf" class="btn-ghost btn-sm">Exportar PDF</a>
      <button id="quick-add" class="btn-primary btn-sm"><?= icon('plus','w-4 h-4') ?> Nova</button>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="table">
      <thead><tr><th>Descrição</th><th>Categoria</th><th>Conta</th><th>Data</th><th class="text-right">Valor</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $t): $exp = $t['type'] === 'expense'; $inc = $t['type'] === 'income'; ?>
        <tr>
          <td class="font-medium"><?= e($t['description'] ?: '—') ?><?php if ($t['notes']): ?><div class="text-xs text-soft mt-0.5"><?= e(mb_strimwidth($t['notes'], 0, 60, '…')) ?></div><?php endif; ?></td>
          <td><span class="badge" style="background:<?= e(($t['category_color'] ?? '#64748b')) ?>22;color:<?= e($t['category_color'] ?? '#94a3b8') ?>"><?= e($t['category_name'] ?? ($t['type'] === 'transfer' ? '→ ' . ($t['to_account_name'] ?? '') : '—')) ?></span></td>
          <td class="text-soft text-sm"><?= e($t['account_name']) ?></td>
          <td class="text-soft text-sm whitespace-nowrap"><?= date('d/m/Y', strtotime($t['occurred_on'])) ?></td>
          <td class="text-right font-semibold whitespace-nowrap <?= $exp ? 'text-neg' : ($inc ? 'text-pos' : '') ?>"><span class="sensitive"><?= ($exp ? '−' : ($inc ? '+' : '')) . money($t['amount']) ?></span></td>
          <td class="text-right"><button class="btn-ghost btn-sm p-1.5" data-del="/api/transactions.php" data-id="<?= $t['id'] ?>" title="Eliminar">✕</button></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-soft py-10">Sem transações para estes filtros.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
    <div class="flex items-center justify-center gap-1 p-4">
      <?php if ($page > 1): ?><a href="<?= e($qs($page - 1)) ?>" class="btn-ghost btn-sm">‹</a><?php endif; ?>
      <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
        <a href="<?= e($qs($p)) ?>" class="btn-sm <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= e($qs($page + 1)) ?>" class="btn-ghost btn-sm">›</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

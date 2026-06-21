<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAdmin();

$audit = Database::all("SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 60");
$attempts = Database::all("SELECT * FROM login_attempts ORDER BY attempted_at DESC LIMIT 40");
$events = Database::all("SELECT * FROM security_events ORDER BY created_at DESC LIMIT 50");

$title = 'Administração'; $nav = 'admin'; $adminPage = 'logs';
require __DIR__ . '/../../app/views/partials/app_head.php';
require __DIR__ . '/../../app/views/partials/admin_nav.php';
?>
<div class="grid lg:grid-cols-2 gap-4">
  <div class="card overflow-hidden">
    <div class="p-4 font-bold flex items-center gap-2"><?= icon('shield','w-4 h-4') ?> Log de auditoria</div>
    <div class="overflow-x-auto max-h-[60vh] overflow-y-auto"><table class="table">
      <thead><tr><th>Ação</th><th>Utilizador</th><th>IP</th><th>Quando</th></tr></thead>
      <tbody>
      <?php foreach ($audit as $a): ?>
        <tr><td><span class="badge-brand font-mono text-xs"><?= e($a['action']) ?></span></td>
        <td class="text-sm text-soft"><?= e($a['email'] ?? '—') ?></td>
        <td class="text-xs text-soft"><?= e($a['ip'] ?? '') ?></td>
        <td class="text-xs text-soft whitespace-nowrap"><?= date('d/m H:i', strtotime($a['created_at'])) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card overflow-hidden">
    <div class="p-4 font-bold flex items-center gap-2"><?= icon('lock','w-4 h-4') ?> Tentativas de login</div>
    <div class="overflow-x-auto max-h-[60vh] overflow-y-auto"><table class="table">
      <thead><tr><th>Identificador</th><th>IP</th><th>Resultado</th><th>Quando</th></tr></thead>
      <tbody>
      <?php foreach ($attempts as $a): ?>
        <tr><td class="text-sm"><?= e($a['identifier']) ?></td>
        <td class="text-xs text-soft"><?= e($a['ip']) ?></td>
        <td><?= $a['success'] ? '<span class="badge-pos">ok</span>' : '<span class="badge-neg">falhou</span>' ?></td>
        <td class="text-xs text-soft whitespace-nowrap"><?= date('d/m H:i', strtotime($a['attempted_at'])) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$attempts): ?><tr><td colspan="4" class="text-soft text-center py-6">Sem tentativas registadas.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
</div>

<div class="card overflow-hidden mt-4">
  <div class="p-4 font-bold flex items-center gap-2"><?= icon('alert-triangle','w-4 h-4') ?> Eventos de segurança (firewall / WAF / CSP / 2FA)</div>
  <div class="overflow-x-auto max-h-[50vh] overflow-y-auto"><table class="table">
    <thead><tr><th>Tipo</th><th>IP</th><th>URI</th><th>Detalhe</th><th>Quando</th></tr></thead>
    <tbody>
    <?php foreach ($events as $ev): ?>
      <tr>
        <td><span class="badge-neg font-mono text-xs"><?= e($ev['kind']) ?></span></td>
        <td class="text-xs text-soft"><?= e($ev['ip'] ?? '') ?></td>
        <td class="text-xs text-soft max-w-[220px] truncate" title="<?= e($ev['uri'] ?? '') ?>"><?= e($ev['uri'] ?? '') ?></td>
        <td class="text-xs text-soft"><?= e($ev['detail'] ?? '') ?></td>
        <td class="text-xs text-soft whitespace-nowrap"><?= date('d/m H:i', strtotime($ev['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$events): ?><tr><td colspan="5" class="text-soft text-center py-6">Nenhum evento de segurança — tudo tranquilo.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php require __DIR__ . '/../../app/views/partials/app_foot.php';

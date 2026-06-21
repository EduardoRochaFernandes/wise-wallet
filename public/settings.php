<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$u = Auth::user();
$ok = null; $err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action');
    if ($action === 'profile') {
        $v = new Validator($_POST);
        $v->required('name', 'Nome')->max('name', 120, 'Nome');
        $v->in('theme', ['dark', 'light'], 'Tema');
        $v->in('currency', ['EUR', 'USD', 'GBP', 'BRL'], 'Moeda');
        if ($v->passes()) {
            Database::run("UPDATE users SET name=?, currency=?, theme=?, privacy_mode=? WHERE id=?",
                [$v->get('name'), input('currency', 'EUR'), input('theme', 'dark'), input('privacy_mode') ? 1 : 0, $uid]);
            Audit::log('settings_profile', $uid);
            $ok = 'Perfil atualizado.';
        } else { $err = $v->firstError(); }
    } elseif ($action === 'password') {
        $current = (string) input('current_password');
        $hash = (string) Database::scalar("SELECT password_hash FROM users WHERE id=?", [$uid]);
        if (!password_verify($current, $hash)) {
            $err = 'A palavra-passe atual está incorreta.';
        } else {
            $v = new Validator($_POST);
            $v->required('new_password', 'Nova palavra-passe')->password('new_password');
            $v->matches('confirm_password', 'new_password', 'A confirmação');
            if ($v->passes()) {
                Database::run("UPDATE users SET password_hash=? WHERE id=?", [Auth::hash((string) input('new_password')), $uid]);
                Audit::log('password_change', $uid);
                $ok = 'Palavra-passe alterada com sucesso.';
            } else { $err = $v->firstError(); }
        }
    }
    $u = Database::one("SELECT id,name,email,role,currency,theme,privacy_mode,points,is_active,created_at FROM users WHERE id=?", [$uid]);
}

$title = 'Definições';
$nav = 'settings';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<?php if ($ok): ?><div class="badge-pos rounded-xl px-4 py-3 mb-4"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="badge-neg rounded-xl px-4 py-3 mb-4"><?= e($err) ?></div><?php endif; ?>

<div class="grid lg:grid-cols-2 gap-4">
  <!-- Profile -->
  <div class="card card-pad">
    <h2 class="font-bold text-lg mb-4">Perfil &amp; preferências</h2>
    <form method="post" class="space-y-3">
      <?= Csrf::field() ?><input type="hidden" name="action" value="profile">
      <div><label class="label">Nome</label><input name="name" class="input" value="<?= e($u['name']) ?>" required></div>
      <div><label class="label">Email</label><input class="input opacity-60" value="<?= e($u['email']) ?>" disabled></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Moeda</label><select name="currency" class="select">
          <?php foreach (['EUR' => 'Euro (€)', 'USD' => 'Dólar ($)', 'GBP' => 'Libra (£)', 'BRL' => 'Real (R$)'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $u['currency'] === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select></div>
        <div><label class="label">Tema</label><select name="theme" class="select">
          <option value="dark" <?= $u['theme'] === 'dark' ? 'selected' : '' ?>>Escuro</option>
          <option value="light" <?= $u['theme'] === 'light' ? 'selected' : '' ?>>Claro</option>
        </select></div>
      </div>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="privacy_mode" value="1" <?= $u['privacy_mode'] ? 'checked' : '' ?>> Ativar modo privado por defeito (desfocar valores)</label>
      <button class="btn-primary">Guardar perfil</button>
    </form>
  </div>

  <!-- Security -->
  <div class="card card-pad">
    <h2 class="font-bold text-lg mb-4">Segurança</h2>
    <form method="post" class="space-y-3">
      <?= Csrf::field() ?><input type="hidden" name="action" value="password">
      <div><label class="label">Palavra-passe atual</label><input name="current_password" type="password" class="input" required></div>
      <div><label class="label">Nova palavra-passe</label><input name="new_password" type="password" class="input" required placeholder="8+ caract., maiúscula, número"></div>
      <div><label class="label">Confirmar nova</label><input name="confirm_password" type="password" class="input" required></div>
      <button class="btn-primary">Alterar palavra-passe</button>
    </form>
    <div class="mt-5 pt-4 border-t text-sm text-soft" style="border-color:rgb(var(--line))">
      <p class="flex items-center gap-2"><?= icon('shield-check','w-4 h-4') ?> Conta protegida com Argon2id, CSRF e sessões seguras.</p>
      <p class="mt-1">Membro desde <?= date('d/m/Y', strtotime($u['created_at'])) ?>.</p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

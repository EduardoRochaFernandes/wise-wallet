<?php
require __DIR__ . '/../app/bootstrap.php';
if (Auth::check()) { redirect('/dashboard.php'); }

$token = (string) input('token', '');
$valid = strlen($token) >= 32 && ctype_xdigit($token);
$error = null; $done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $row = Database::one(
        "SELECT pr.id, pr.user_id FROM password_resets pr
          WHERE pr.token_hash=? AND pr.used=0 AND pr.expires_at > NOW() LIMIT 1",
        [hash('sha256', $token)]);
    if (!$row) {
        $error = 'Este link é inválido ou expirou. Pede um novo.';
    } else {
        $v = new Validator($_POST);
        $v->required('password', 'Palavra-passe')->password('password');
        $v->required('password_confirm', 'Confirmação')->matches('password_confirm', 'password', 'A confirmação');
        if (!$v->passes()) {
            $error = $v->firstError();
        } elseif (Pwned::isCompromised((string) input('password'))) {
            $error = 'Essa palavra-passe apareceu em fugas de dados conhecidas. Escolhe outra.';
        } else {
            Database::run("UPDATE users SET password_hash=?, locked_until=NULL WHERE id=?",
                [Auth::hash((string) input('password')), $row['user_id']]);
            // Invalidate every reset token for this user.
            Database::run("UPDATE password_resets SET used=1 WHERE user_id=?", [$row['user_id']]);
            Audit::log('password_reset_done', (int) $row['user_id']);
            $done = true;
        }
    }
}

$seo = ['title' => 'Definir nova palavra-passe · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-16">
  <div class="card card-pad animate-fade-up">
    <div class="text-center mb-6">
      <div class="flex justify-center mb-3"><?= logo_mark('w-12 h-12') ?></div>
      <h1 class="text-2xl font-bold">Nova palavra-passe</h1>
    </div>

    <?php if ($done): ?>
      <div class="badge-pos rounded-xl px-4 py-3 text-sm mb-4">Palavra-passe redefinida com sucesso.</div>
      <a href="/login.php" class="btn-primary w-full">Iniciar sessão</a>
    <?php elseif (!$valid): ?>
      <div class="badge-neg rounded-xl px-4 py-3 text-sm mb-4">Link inválido. <a href="/forgot-password.php" class="underline">Pedir um novo</a>.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="badge-neg rounded-xl px-4 py-3 text-sm mb-4"><?= e($error) ?></div><?php endif; ?>
      <form method="post" class="space-y-4" novalidate>
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div>
          <label class="label" for="password">Nova palavra-passe</label>
          <input id="password" name="password" type="password" required autofocus class="input" placeholder="8+ caract., maiúscula, número">
        </div>
        <div>
          <label class="label" for="password_confirm">Confirmar</label>
          <input id="password_confirm" name="password_confirm" type="password" required class="input">
        </div>
        <button class="btn-primary w-full">Redefinir palavra-passe</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../app/views/partials/public_foot.php';

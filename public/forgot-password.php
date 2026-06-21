<?php
require __DIR__ . '/../app/bootstrap.php';
if (Auth::check()) { redirect('/dashboard.php'); }

$sent = false; $error = null; $devLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) input('email', '')));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Introduz um email válido.';
    } elseif (!Firewall::allow('pwreset:' . $ip, 5, 900)) {
        $error = 'Demasiados pedidos. Tenta novamente dentro de alguns minutos.';
    } else {
        $user = Database::one("SELECT id, name, email FROM users WHERE email=? AND is_active=1", [$email]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            Database::run(
                "INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
                 VALUES (?, ?, NOW() + INTERVAL 1 HOUR, NOW())",
                [$user['id'], hash('sha256', $token)]);
            $link = WW_URL . '/reset-password.php?token=' . $token;
            Mailer::send(
                $user['email'],
                'Recuperação de palavra-passe — WiseWallet',
                "Olá {$user['name']},\n\nRecebemos um pedido para redefinir a tua palavra-passe.\n"
                . "Abre o link seguinte (válido durante 1 hora):\n{$link}\n\n"
                . "Se não foste tu, ignora este email — a tua conta continua segura.");
            Audit::log('password_reset_request', (int) $user['id']);
            if (WW_DEBUG) { $devLink = $link; }
        }
        // Generic response — never reveal whether the email exists.
        $sent = true;
    }
}

$seo = ['title' => 'Recuperar palavra-passe · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-16">
  <div class="card card-pad animate-fade-up">
    <div class="text-center mb-6">
      <div class="flex justify-center mb-3"><?= logo_mark('w-12 h-12') ?></div>
      <h1 class="text-2xl font-bold">Recuperar acesso</h1>
      <p class="text-soft text-sm mt-1">Enviamos-te um link para definir uma nova palavra-passe.</p>
    </div>

    <?php if ($error): ?>
      <div class="badge-neg rounded-xl px-4 py-3 text-sm mb-4"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($sent): ?>
      <div class="badge-pos rounded-xl px-4 py-3 text-sm mb-4">Se existir uma conta com esse email, enviámos instruções de recuperação.</div>
      <?php if ($devLink): ?>
        <div class="rounded-xl p-3 text-xs" style="background:rgb(var(--surface-2))">
          <p class="text-soft mb-1">🔧 Modo de desenvolvimento — link de reposição:</p>
          <a href="<?= e($devLink) ?>" class="text-brand-400 break-all"><?= e($devLink) ?></a>
        </div>
      <?php endif; ?>
      <p class="text-center text-sm text-soft mt-5"><a href="/login.php" class="text-brand-400">Voltar a iniciar sessão</a></p>
    <?php else: ?>
      <form method="post" class="space-y-4" novalidate>
        <?= Csrf::field() ?>
        <div>
          <label class="label" for="email">Email da conta</label>
          <input id="email" name="email" type="email" required autofocus class="input" placeholder="o-teu@email.pt">
        </div>
        <button class="btn-primary w-full">Enviar link de recuperação</button>
      </form>
      <p class="text-center text-sm text-soft mt-5"><a href="/login.php" class="text-brand-400">Voltar</a></p>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../app/views/partials/public_foot.php';

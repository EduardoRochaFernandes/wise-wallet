<?php
require __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect('/dashboard.php');
}

$error = flash('error');
$stage = isset($_SESSION['pending_2fa']) ? '2fa' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('code') !== null && isset($_SESSION['pending_2fa'])) {
        [$ok, $msg] = Auth::verify2fa((string) input('code'));
        if ($ok) { redirect('/dashboard.php'); }
        $error = $msg;
        $stage = '2fa';
    } else {
        $email = (string) input('email', '');
        $password = (string) input('password', '');
        [$ok, $msg] = Auth::attempt($email, $password);
        if ($ok) { redirect('/dashboard.php'); }
        if ($msg === '__2FA__') {
            $stage = '2fa';
        } else {
            $error = $msg;
            set_old(['email' => $email]);
            $stage = 'login';
        }
    }
}

$seo = ['title' => 'Entrar · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-16">
  <div class="card card-pad animate-fade-up">
    <div class="text-center mb-6">
      <div class="flex justify-center mb-3"><?= logo_mark('w-12 h-12') ?></div>
      <h1 class="text-2xl font-bold"><?= $stage === '2fa' ? 'Verificação em dois passos' : 'Bem-vindo de volta' ?></h1>
      <p class="text-soft text-sm mt-1"><?= $stage === '2fa' ? 'Introduz o código de 6 dígitos da tua app de autenticação.' : 'Inicia sessão na tua conta WiseWallet.' ?></p>
    </div>

    <?php if ($error): ?>
      <div class="badge-neg rounded-xl px-4 py-3 text-sm mb-4 flex items-center gap-2"><?= icon('alert-triangle','w-4 h-4') ?><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($stage === '2fa'): ?>
      <form method="post" class="space-y-4" novalidate>
        <?= Csrf::field() ?>
        <div>
          <label class="label" for="code">Código de autenticação</label>
          <input id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus required
                 class="input text-center tracking-[0.5em] text-lg" placeholder="000000">
        </div>
        <button class="btn-primary w-full">Verificar e entrar</button>
      </form>
      <p class="text-center text-sm text-soft mt-5"><a href="/logout.php" class="text-brand-400">Cancelar</a></p>
    <?php else: ?>
      <form method="post" class="space-y-4" novalidate>
        <?= Csrf::field() ?>
        <div>
          <label class="label" for="email">Email</label>
          <input id="email" name="email" type="email" required autofocus class="input" value="<?= old('email') ?>" placeholder="o-teu@email.pt">
        </div>
        <div>
          <label class="label" for="password">Palavra-passe</label>
          <input id="password" name="password" type="password" required class="input" placeholder="••••••••">
        </div>
        <div class="flex justify-end -mt-1">
          <a href="/forgot-password.php" class="text-sm text-brand-400">Esqueceste-te da palavra-passe?</a>
        </div>
        <button class="btn-primary w-full">Entrar</button>
      </form>

      <p class="text-center text-sm text-soft mt-5">Não tens conta? <a href="/register.php" class="text-brand-400 font-medium">Cria uma grátis</a></p>

      <div class="mt-6 pt-5 border-t text-xs text-soft" style="border-color:rgb(var(--line))">
        <p class="font-semibold mb-1">Conta de demonstração:</p>
        <p>demo@wisewallet.local · Demo@WiseWallet2026</p>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php clear_old(); require __DIR__ . '/../app/views/partials/public_foot.php';

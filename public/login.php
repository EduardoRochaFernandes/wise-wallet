<?php
require __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect('/dashboard.php');
}

$error = flash('error');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string) input('email', '');
    $password = (string) input('password', '');
    [$ok, $msg] = Auth::attempt($email, $password);
    if ($ok) {
        redirect('/dashboard.php');
    }
    $error = $msg;
    set_old(['email' => $email]);
}

$seo = ['title' => 'Entrar · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-16">
  <div class="card card-pad animate-fade-up">
    <div class="text-center mb-6">
      <div class="flex justify-center mb-3"><?= logo_mark('w-12 h-12') ?></div>
      <h1 class="text-2xl font-bold">Bem-vindo de volta</h1>
      <p class="text-soft text-sm mt-1">Inicia sessão na tua conta WiseWallet.</p>
    </div>

    <?php if ($error): ?>
      <div class="badge-neg rounded-xl px-4 py-3 text-sm mb-4 flex items-center gap-2"><?= icon('alert-triangle','w-4 h-4') ?><?= e($error) ?></div>
    <?php endif; ?>

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
      <button class="btn-primary w-full">Entrar</button>
    </form>

    <p class="text-center text-sm text-soft mt-5">Não tens conta? <a href="/register.php" class="text-brand-400 font-medium">Cria uma grátis</a></p>

    <div class="mt-6 pt-5 border-t text-xs text-soft" style="border-color:rgb(var(--line))">
      <p class="font-semibold mb-1">Conta de demonstração:</p>
      <p>demo@wisewallet.local · Demo@WiseWallet2026</p>
    </div>
  </div>
</section>
<?php clear_old(); require __DIR__ . '/../app/views/partials/public_foot.php';

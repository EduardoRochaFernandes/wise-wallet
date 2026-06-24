<?php
require __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect('/dashboard');
}

$error = flash('error');
$stage = isset($_SESSION['pending_2fa']) ? '2fa' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('code') !== null && isset($_SESSION['pending_2fa'])) {
        [$ok, $msg] = Auth::verify2fa((string) input('code'));
        if ($ok) { redirect('/dashboard'); }
        $error = $msg;
        $stage = '2fa';
    } else {
        $email = (string) input('email', '');
        $password = (string) input('password', '');
        [$ok, $msg] = Auth::attempt($email, $password);
        if ($ok) { redirect('/dashboard'); }
        if ($msg === '__2FA__') {
            $stage = '2fa';
        } else {
            $error = $msg;
            set_old(['email' => $email]);
            $stage = 'login';
        }
    }
}

$seo = ['title' => 'Sign in · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-20">
  <div class="card card-pad animate-fade-up">
    <div class="mb-6">
      <h1 class="font-display text-2xl font-semibold"><?= $stage === '2fa' ? 'Two-step verification' : 'Welcome back' ?></h1>
      <p class="text-soft text-sm mt-1"><?= $stage === '2fa' ? 'Enter the 6-digit code from your authenticator app.' : 'Sign in to your WiseWallet account.' ?></p>
    </div>

    <?php if ($error): ?>
      <div class="badge-neg rounded px-4 py-3 text-sm mb-4 flex items-center gap-2"><?= icon('alert-triangle','w-4 h-4') ?><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($stage === '2fa'): ?>
      <form method="post" class="space-y-4" novalidate>
        <?= Csrf::field() ?>
        <div>
          <label class="label" for="code">Authentication code</label>
          <input id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus required
                 class="input text-center tracking-[0.5em] text-lg" placeholder="000000">
        </div>
        <button class="btn-primary w-full">Verify and sign in</button>
      </form>
      <p class="text-center text-sm text-soft mt-5"><a href="/logout" class="text-accent">Cancel</a></p>
    <?php else: ?>
      <form method="post" class="space-y-4" novalidate>
        <?= Csrf::field() ?>
        <div>
          <label class="label" for="email">Email</label>
          <input id="email" name="email" type="email" required autofocus class="input" value="<?= old('email') ?>" placeholder="you@email.com">
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <input id="password" name="password" type="password" required class="input" placeholder="••••••••">
        </div>
        <div class="flex justify-end -mt-1">
          <a href="/forgot-password" class="text-sm text-accent">Forgot your password?</a>
        </div>
        <button class="btn-primary w-full">Sign in</button>
      </form>

      <p class="text-center text-sm text-soft mt-5">No account yet? <a href="/register" class="text-accent font-medium">Create one free</a></p>

      <div class="mt-6 pt-5 border-t text-xs text-soft" style="border-color:rgb(var(--line))">
        <p class="font-semibold mb-1">Demo account</p>
        <p>demo@wisewallet.local · Demo@WiseWallet2026</p>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php clear_old(); require __DIR__ . '/../app/views/partials/public_foot.php';

<?php
require __DIR__ . '/../app/bootstrap.php';
if (Auth::check()) { redirect('/dashboard.php'); }

$sent = false; $error = null; $devLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) input('email', '')));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (!Firewall::allow('pwreset:' . $ip, 5, 900)) {
        $error = 'Too many requests. Please try again in a few minutes.';
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
                'Reset your WiseWallet password',
                "Hi {$user['name']},\n\nWe received a request to reset your password.\n"
                . "Open the link below to choose a new one (valid for 1 hour):\n{$link}\n\n"
                . "If this wasn't you, ignore this email — your account stays safe.");
            Audit::log('password_reset_request', (int) $user['id']);
            if (WW_DEBUG) { $devLink = $link; }
        }
        $sent = true; // generic response — never reveal whether the email exists
    }
}

$seo = ['title' => 'Reset password · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-20">
  <div class="card card-pad animate-fade-up">
    <div class="mb-6">
      <h1 class="font-display text-2xl font-semibold">Reset your password</h1>
      <p class="text-soft text-sm mt-1">We'll email you a link to choose a new password.</p>
    </div>

    <?php if ($error): ?>
      <div class="badge-neg rounded px-4 py-3 text-sm mb-4"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($sent): ?>
      <div class="badge-pos rounded px-4 py-3 text-sm mb-4">If an account exists for that email, we've sent recovery instructions.</div>
      <?php if ($devLink): ?>
        <div class="rounded p-3 text-xs" style="background:rgb(var(--surface-2))">
          <p class="text-soft mb-1">Dev mode — reset link:</p>
          <a href="<?= e($devLink) ?>" class="text-accent break-all"><?= e($devLink) ?></a>
        </div>
      <?php endif; ?>
      <p class="text-center text-sm text-soft mt-5"><a href="/login.php" class="text-accent">Back to sign in</a></p>
    <?php else: ?>
      <form method="post" class="space-y-4" novalidate>
        <?= Csrf::field() ?>
        <div>
          <label class="label" for="email">Account email</label>
          <input id="email" name="email" type="email" required autofocus class="input" placeholder="you@email.com">
        </div>
        <button class="btn-primary w-full">Send reset link</button>
      </form>
      <p class="text-center text-sm text-soft mt-5"><a href="/login.php" class="text-accent">Back</a></p>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../app/views/partials/public_foot.php';

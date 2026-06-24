<?php
require __DIR__ . '/../app/bootstrap.php';
if (Auth::check()) { redirect('/dashboard'); }

$token = (string) input('token', '');
$valid = strlen($token) >= 32 && ctype_xdigit($token);
$error = null; $done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $row = Database::one(
        "SELECT pr.id, pr.user_id FROM password_resets pr
          WHERE pr.token_hash=? AND pr.used=0 AND pr.expires_at > NOW() LIMIT 1",
        [hash('sha256', $token)]);
    if (!$row) {
        $error = 'This link is invalid or has expired. Please request a new one.';
    } else {
        $v = new Validator($_POST);
        $v->required('password', 'Password')->password('password');
        $v->required('password_confirm', 'Confirmation')->matches('password_confirm', 'password', 'The confirmation');
        if (!$v->passes()) {
            $error = $v->firstError();
        } elseif (Pwned::isCompromised((string) input('password'))) {
            $error = 'That password appeared in known data breaches. Please choose another.';
        } else {
            Database::run("UPDATE users SET password_hash=?, locked_until=NULL WHERE id=?",
                [Auth::hash((string) input('password')), $row['user_id']]);
            Database::run("UPDATE password_resets SET used=1 WHERE user_id=?", [$row['user_id']]);
            Audit::log('password_reset_done', (int) $row['user_id']);
            $done = true;
        }
    }
}

$seo = ['title' => 'Set a new password · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-20">
  <div class="card card-pad animate-fade-up">
    <h1 class="font-display text-2xl font-semibold mb-6">New password</h1>

    <?php if ($done): ?>
      <div class="badge-pos rounded px-4 py-3 text-sm mb-4">Your password was reset successfully.</div>
      <a href="/login" class="btn-primary w-full">Sign in</a>
    <?php elseif (!$valid): ?>
      <div class="badge-neg rounded px-4 py-3 text-sm mb-4">Invalid link. <a href="/forgot-password" class="underline">Request a new one</a>.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="badge-neg rounded px-4 py-3 text-sm mb-4"><?= e($error) ?></div><?php endif; ?>
      <form method="post" class="space-y-4" novalidate>
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div>
          <label class="label" for="password">New password</label>
          <input id="password" name="password" type="password" required autofocus class="input" placeholder="8+ chars, upper & lower case, a number">
        </div>
        <div>
          <label class="label" for="password_confirm">Confirm</label>
          <input id="password_confirm" name="password_confirm" type="password" required class="input">
        </div>
        <button class="btn-primary w-full">Reset password</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../app/views/partials/public_foot.php';

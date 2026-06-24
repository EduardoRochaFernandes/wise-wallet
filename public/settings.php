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
        $v->required('name', 'Name')->max('name', 120, 'Name');
        $v->in('theme', ['dark', 'light'], 'Theme');
        $v->in('currency', ['EUR', 'USD', 'GBP', 'BRL'], 'Currency');
        if ($v->passes()) {
            Database::run("UPDATE users SET name=?, currency=?, theme=?, privacy_mode=?, email_notifications=? WHERE id=?",
                [$v->get('name'), input('currency', 'EUR'), input('theme', 'light'), input('privacy_mode') ? 1 : 0, input('email_notifications') ? 1 : 0, $uid]);
            Audit::log('settings_profile', $uid);
            $ok = 'Profile updated.';
        } else { $err = $v->firstError(); }
    } elseif ($action === 'password') {
        $current = (string) input('current_password');
        $hash = (string) Database::scalar("SELECT password_hash FROM users WHERE id=?", [$uid]);
        if (!password_verify($current, $hash)) {
            $err = 'Your current password is incorrect.';
        } else {
            $v = new Validator($_POST);
            $v->required('new_password', 'New password')->password('new_password');
            $v->matches('confirm_password', 'new_password', 'The confirmation');
            if (!$v->passes()) {
                $err = $v->firstError();
            } elseif (Pwned::isCompromised((string) input('new_password'))) {
                $err = 'That password appeared in known data breaches. Please choose another.';
            } else {
                Database::run("UPDATE users SET password_hash=? WHERE id=?", [Auth::hash((string) input('new_password')), $uid]);
                Audit::log('password_change', $uid);
                $ok = 'Password changed successfully.';
            }
        }
    } elseif ($action === '2fa_enable') {
        $_SESSION['totp_setup'] = Totp::secret();
        $ok = 'Add the key to your authenticator app and confirm with a code.';
    } elseif ($action === '2fa_confirm') {
        $secret = $_SESSION['totp_setup'] ?? '';
        if ($secret && Totp::verify($secret, (string) input('code'))) {
            Database::run("UPDATE users SET totp_secret=?, totp_enabled=1 WHERE id=?", [$secret, $uid]);
            unset($_SESSION['totp_setup']);
            Audit::log('2fa_enabled', $uid);
            $ok = 'Two-step verification is now enabled.';
        } else { $err = 'Invalid code. Please try again.'; }
    } elseif ($action === '2fa_disable') {
        $hash = (string) Database::scalar("SELECT password_hash FROM users WHERE id=?", [$uid]);
        if (password_verify((string) input('current_password'), $hash)) {
            Database::run("UPDATE users SET totp_enabled=0, totp_secret=NULL WHERE id=?", [$uid]);
            unset($_SESSION['totp_setup']);
            Audit::log('2fa_disabled', $uid);
            $ok = 'Two-step verification disabled.';
        } else { $err = 'Incorrect password.'; }
    }
    $u = Database::one("SELECT id,name,email,role,currency,theme,privacy_mode,email_notifications,points,is_active,created_at FROM users WHERE id=?", [$uid]);
}

$twofa = (int) (Database::scalar("SELECT totp_enabled FROM users WHERE id=?", [$uid]) ?? 0);
$title = 'Settings';
$nav = 'settings';
require __DIR__ . '/../app/views/partials/app_head.php';
?>
<?php if ($ok): ?><div class="badge-pos rounded px-4 py-3 mb-4"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="badge-neg rounded px-4 py-3 mb-4"><?= e($err) ?></div><?php endif; ?>

<div class="grid lg:grid-cols-2 gap-5">
  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-4">Profile &amp; preferences</h2>
    <form method="post" class="space-y-3">
      <?= Csrf::field() ?><input type="hidden" name="action" value="profile">
      <div><label class="label">Name</label><input name="name" class="input" value="<?= e($u['name']) ?>" required></div>
      <div><label class="label">Email</label><input class="input opacity-60" value="<?= e($u['email']) ?>" disabled></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Currency</label><select name="currency" class="select">
          <?php foreach (['EUR' => 'Euro (€)', 'USD' => 'US Dollar ($)', 'GBP' => 'Pound (£)', 'BRL' => 'Real (R$)'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $u['currency'] === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select></div>
        <div><label class="label">Theme</label><select name="theme" class="select">
          <option value="light" <?= $u['theme'] === 'light' ? 'selected' : '' ?>>Light</option>
          <option value="dark" <?= $u['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
        </select></div>
      </div>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="privacy_mode" value="1" <?= $u['privacy_mode'] ? 'checked' : '' ?>> Enable private mode by default (blur amounts)</label>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="email_notifications" value="1" <?= ($u['email_notifications'] ?? 1) ? 'checked' : '' ?>> Email me about logins, bills due, budgets, and achievements</label>
      <button class="btn-primary">Save profile</button>
    </form>
  </div>

  <div class="card card-pad">
    <h2 class="font-display font-semibold text-lg mb-4">Security</h2>
    <form method="post" class="space-y-3">
      <?= Csrf::field() ?><input type="hidden" name="action" value="password">
      <div><label class="label">Current password</label><input name="current_password" type="password" class="input" required></div>
      <div><label class="label">New password</label><input name="new_password" type="password" class="input" required placeholder="8+ chars, upper & lower, a number"></div>
      <div><label class="label">Confirm new</label><input name="confirm_password" type="password" class="input" required></div>
      <button class="btn-primary">Change password</button>
    </form>
    <div class="mt-5 pt-4 border-t text-sm text-soft" style="border-color:rgb(var(--line))">
      <p class="flex items-center gap-2"><?= icon('shield-check','w-4 h-4') ?> Protected with Argon2id, CSRF and secure sessions.</p>
      <p class="mt-1">Member since <?= date('d M Y', strtotime($u['created_at'])) ?>.</p>
    </div>
  </div>
</div>

<div class="card card-pad mt-5 max-w-3xl">
  <div class="flex items-center justify-between">
    <h2 class="font-display font-semibold text-lg flex items-center gap-2"><?= icon('lock','w-5 h-5') ?> Two-step verification (2FA)</h2>
    <?= $twofa ? '<span class="badge-pos">On</span>' : '<span class="badge-warn">Off</span>' ?>
  </div>
  <?php if ($twofa): ?>
    <p class="text-soft text-sm mt-2">Your account is protected with TOTP. To disable it, confirm your password.</p>
    <form method="post" class="flex gap-2 mt-3 max-w-sm">
      <?= Csrf::field() ?><input type="hidden" name="action" value="2fa_disable">
      <input name="current_password" type="password" class="input" placeholder="Current password" required>
      <button class="btn-ghost">Disable</button>
    </form>
  <?php elseif (!empty($_SESSION['totp_setup'])): $secret = $_SESSION['totp_setup']; $uri = Totp::uri($secret, $u['email']); ?>
    <p class="text-soft text-sm mt-2">1) Scan the QR with your authenticator app (Google Authenticator, Authy, 1Password…), or enter the key manually:</p>
    <div class="flex flex-col sm:flex-row gap-4 items-start my-3">
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=176x176&margin=0&data=<?= rawurlencode($uri) ?>" alt="Two-factor QR code" width="176" height="176" class="rounded border bg-white p-1" style="border-color:rgb(var(--line))">
      <div class="min-w-0">
        <div class="text-xs text-soft uppercase tracking-wide mb-1">Setup key</div>
        <div class="kbd text-base tracking-[0.25em] break-all px-3 py-2"><?= e($secret) ?></div>
      </div>
    </div>
    <p class="text-soft text-sm mt-3">2) Enter the generated 6-digit code to confirm:</p>
    <form method="post" class="flex gap-2 mt-2 max-w-sm">
      <?= Csrf::field() ?><input type="hidden" name="action" value="2fa_confirm">
      <input name="code" inputmode="numeric" maxlength="6" class="input text-center tracking-[0.3em]" placeholder="000000" required>
      <button class="btn-primary">Confirm</button>
    </form>
  <?php else: ?>
    <p class="text-soft text-sm mt-2">Add an extra layer of security with an authenticator app (TOTP).</p>
    <form method="post" class="mt-3">
      <?= Csrf::field() ?><input type="hidden" name="action" value="2fa_enable">
      <button class="btn-primary">Enable 2FA</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../app/views/partials/app_foot.php';

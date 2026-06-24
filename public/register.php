<?php
require __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect('/dashboard');
}

$allowReg = (string) (Database::scalar("SELECT `value` FROM settings WHERE `key`='allow_registration'") ?? '1') === '1';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$allowReg) {
        $errors['general'] = 'Registration is currently disabled.';
    } else {
        $v = new Validator($_POST);
        $v->required('name', 'Name')->max('name', 120, 'Name');
        $v->required('email', 'Email')->email('email')->max('email', 190, 'Email');
        $v->required('password', 'Password')->password('password');
        $v->required('password_confirm', 'Confirmation')->matches('password_confirm', 'password', 'The confirmation');

        if ($v->passes()) {
            [$ok, $res] = Auth::register((string) $v->get('name'), (string) $v->get('email'), (string) input('password'));
            if ($ok) {
                Auth::attempt((string) $v->get('email'), (string) input('password'));
                redirect('/dashboard');
            }
            $errors['email'] = $res;
        } else {
            $errors = $v->errors();
            set_old($_POST);
        }
    }
}

$seo = ['title' => 'Create account · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-20">
  <div class="card card-pad animate-fade-up">
    <div class="mb-6">
      <h1 class="font-display text-2xl font-semibold">Create your account</h1>
      <p class="text-soft text-sm mt-1">Free. No card. About thirty seconds.</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
      <div class="badge-neg rounded px-4 py-3 text-sm mb-4"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4" novalidate>
      <?= Csrf::field() ?>
      <div>
        <label class="label" for="name">Name</label>
        <input id="name" name="name" required autofocus class="input" value="<?= old('name') ?>" placeholder="Your name">
        <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="label" for="email">Email</label>
        <input id="email" name="email" type="email" required class="input" value="<?= old('email') ?>" placeholder="you@email.com">
        <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="label" for="password">Password</label>
        <input id="password" name="password" type="password" required class="input" placeholder="8+ chars, upper & lower case, a number">
        <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="label" for="password_confirm">Confirm password</label>
        <input id="password_confirm" name="password_confirm" type="password" required class="input" placeholder="Repeat your password">
        <?php if (!empty($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
      </div>
      <button class="btn-primary w-full">Create account</button>
    </form>

    <p class="text-center text-sm text-soft mt-5">Already have an account? <a href="/login" class="text-accent font-medium">Sign in</a></p>
  </div>
</section>
<?php clear_old(); require __DIR__ . '/../app/views/partials/public_foot.php';

<?php
require __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    redirect('/dashboard.php');
}

$allowReg = (string) (Database::scalar("SELECT `value` FROM settings WHERE `key`='allow_registration'") ?? '1') === '1';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$allowReg) {
        $errors['general'] = 'O registo está temporariamente desativado.';
    } else {
        $v = new Validator($_POST);
        $v->required('name', 'Nome')->max('name', 120, 'Nome');
        $v->required('email', 'Email')->email('email')->max('email', 190, 'Email');
        $v->required('password', 'Palavra-passe')->password('password');
        $v->required('password_confirm', 'Confirmação')->matches('password_confirm', 'password', 'A confirmação');

        if ($v->passes()) {
            [$ok, $res] = Auth::register((string) $v->get('name'), (string) $v->get('email'), (string) input('password'));
            if ($ok) {
                Auth::attempt((string) $v->get('email'), (string) input('password'));
                redirect('/dashboard.php');
            }
            $errors['email'] = $res;
        } else {
            $errors = $v->errors();
            set_old($_POST);
        }
    }
}

$seo = ['title' => 'Criar conta · WiseWallet', 'noindex' => true];
require __DIR__ . '/../app/views/partials/public_head.php';
?>
<section class="max-w-md mx-auto px-4 py-16">
  <div class="card card-pad animate-fade-up">
    <div class="text-center mb-6">
      <div class="flex justify-center mb-3"><?= logo_mark('w-12 h-12') ?></div>
      <h1 class="text-2xl font-bold">Cria a tua conta</h1>
      <p class="text-soft text-sm mt-1">Grátis. Sem cartão. 30 segundos.</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
      <div class="badge-neg rounded-xl px-4 py-3 text-sm mb-4"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4" novalidate>
      <?= Csrf::field() ?>
      <div>
        <label class="label" for="name">Nome</label>
        <input id="name" name="name" required autofocus class="input" value="<?= old('name') ?>" placeholder="O teu nome">
        <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="label" for="email">Email</label>
        <input id="email" name="email" type="email" required class="input" value="<?= old('email') ?>" placeholder="o-teu@email.pt">
        <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="label" for="password">Palavra-passe</label>
        <input id="password" name="password" type="password" required class="input" placeholder="8+ caracteres, maiúscula, número">
        <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="label" for="password_confirm">Confirmar palavra-passe</label>
        <input id="password_confirm" name="password_confirm" type="password" required class="input" placeholder="Repete a palavra-passe">
        <?php if (!empty($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
      </div>
      <button class="btn-primary w-full">Criar conta</button>
    </form>

    <p class="text-center text-sm text-soft mt-5">Já tens conta? <a href="/login.php" class="text-brand-400 font-medium">Entra aqui</a></p>
  </div>
</section>
<?php clear_old(); require __DIR__ . '/../app/views/partials/public_foot.php';

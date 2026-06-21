<?php
/**
 * Authenticated app shell — top half.
 * Page must: require bootstrap, call Auth::requireAuth(), set $title and $nav,
 * then `require app/views/partials/app_head.php`.
 */
$u = Auth::user();
$title = $title ?? 'WiseWallet';
$nav = $nav ?? '';
$theme = $u['theme'] ?? 'dark';
$privacy = (int) ($u['privacy_mode'] ?? 0) === 1;

$navItems = [
    ['dashboard',     'Dashboard',     'layout-dashboard', '/dashboard.php'],
    ['transactions',  'Transações',    'arrow-left-right', '/transactions.php'],
    ['accounts',      'Contas',        'landmark',         '/accounts.php'],
    ['budgets',       'Orçamentos',    'target',           '/budgets.php'],
    ['goals',         'Objetivos',     'star',             '/goals.php'],
    ['bills',         'Faturas',       'calendar',         '/bills.php'],
    ['subscriptions', 'Subscrições',   'repeat',           '/subscriptions.php'],
    ['investments',   'Investimentos', 'trending-up',      '/investments.php'],
    ['analytics',     'Análise',       'brain',            '/analytics.php'],
    ['simulators',    'Simuladores',   'calculator',       '/simulators.php'],
    ['achievements',  'Conquistas',    'trophy',           '/achievements.php'],
    ['news',          'Notícias',      'newspaper',        '/news.php'],
    ['blog',          'Blog',          'book-open',        '/blog.php'],
    ['settings',      'Definições',    'settings',         '/settings.php'],
];
$unread = (int) (Database::scalar("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [Auth::id()]) ?? 0);
?>
<!DOCTYPE html>
<html lang="pt" class="<?= $theme === 'light' ? 'light' : 'dark' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
<meta name="theme-color" content="#090b14">
<title><?= e($title) ?> · WiseWallet</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="#6366f1"/><path d="M6 9h11v9H6z" fill="none" stroke="#fff" stroke-width="1.6"/></svg>') ?>">
<link rel="stylesheet" href="/assets/css/app.css">
<script <?= nonce_attr() ?>>
(function(){try{var t=localStorage.getItem('ww-theme');if(t){var d=document.documentElement;d.classList.toggle('light',t==='light');d.classList.toggle('dark',t!=='light');}}catch(e){}})();
</script>
</head>
<body class="<?= $privacy ? 'private' : '' ?>">
<div id="scroll-progress"></div>

<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
  <!-- Sidebar -->
  <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-[260px] -translate-x-full lg:translate-0 transition-transform duration-300 border-r flex flex-col" style="background:rgb(var(--surface));border-color:rgb(var(--line))">
    <div class="flex items-center gap-2.5 px-5 h-16 border-b" style="border-color:rgb(var(--line))">
      <?= logo_mark('w-9 h-9') ?>
      <span class="font-bold text-lg">Wise<span class="text-brand-400">Wallet</span></span>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
      <?php foreach ($navItems as [$key, $label, $ic, $href]): ?>
        <a href="<?= $href ?>" class="nav-link<?= nav_active($key, $nav) ?>"><?= icon($ic) ?><span><?= $label ?></span></a>
      <?php endforeach; ?>
      <?php if (Auth::isAdmin()): ?>
        <div class="pt-3 mt-3 border-t" style="border-color:rgb(var(--line))">
          <a href="/admin/index.php" class="nav-link<?= nav_active('admin', $nav) ?>"><?= icon('shield') ?><span>Administração</span></a>
        </div>
      <?php endif; ?>
    </nav>
    <div class="p-3 border-t" style="border-color:rgb(var(--line))">
      <div class="flex items-center gap-3 rounded-xl p-2.5" style="background:rgb(var(--surface-2))">
        <div class="w-9 h-9 rounded-full grid place-items-center text-white font-semibold" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"><?= e(strtoupper(substr($u['name'] ?? 'U', 0, 1))) ?></div>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold truncate"><?= e($u['name'] ?? 'Utilizador') ?></div>
          <div class="text-xs text-soft truncate"><?= e($u['email'] ?? '') ?></div>
        </div>
        <a href="/logout.php" title="Terminar sessão" class="text-soft hover:text-[rgb(var(--neg))]"><?= icon('log-out') ?></a>
      </div>
    </div>
  </aside>
  <div id="nav-backdrop" class="hidden fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

  <!-- Main column -->
  <div class="flex flex-col min-h-screen">
    <header class="sticky top-0 z-20 glass border-b h-16 flex items-center gap-3 px-4 sm:px-6" style="border-color:rgb(var(--line))">
      <button id="nav-open" class="lg:hidden btn-ghost btn-sm p-2" aria-label="Menu"><?= icon('menu') ?></button>
      <h1 class="text-lg font-bold flex-1 truncate"><?= e($title) ?></h1>
      <button data-open-cmdk class="btn-ghost btn-sm hidden sm:inline-flex" title="Pesquisar (Ctrl+K)"><?= icon('search','w-4 h-4') ?><span class="kbd ml-1">Ctrl K</span></button>
      <button data-privacy-toggle class="btn-ghost btn-sm p-2" title="Modo privado" aria-label="Modo privado"><?= icon('eye','w-4 h-4') ?></button>
      <button data-theme-toggle class="btn-ghost btn-sm p-2" title="Alternar tema" aria-label="Tema"><?= icon('sun','w-4 h-4') ?></button>
      <a href="/achievements.php" class="btn-ghost btn-sm p-2 relative" title="Notificações" aria-label="Notificações">
        <?= icon('bell','w-4 h-4') ?>
        <?php if ($unread > 0): ?><span class="absolute -top-1 -right-1 w-4 h-4 text-[10px] grid place-items-center rounded-full text-white" style="background:rgb(var(--neg))"><?= $unread ?></span><?php endif; ?>
      </a>
      <button id="quick-add" class="btn-primary btn-sm"><?= icon('plus','w-4 h-4') ?><span class="hidden sm:inline">Adicionar</span></button>
    </header>

    <main class="flex-1 p-4 sm:p-6 max-w-[1400px] w-full mx-auto animate-fade-up">

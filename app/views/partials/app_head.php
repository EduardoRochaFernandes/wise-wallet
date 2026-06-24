<?php
/**
 * Authenticated app shell — top half.
 * Page must: require bootstrap, call Auth::requireAuth(), set $title and $nav,
 * then `require app/views/partials/app_head.php`.
 */
$u = Auth::user();
$title = $title ?? 'WiseWallet';
$nav = $nav ?? '';
$theme = $u['theme'] ?? 'light';
$privacy = (int) ($u['privacy_mode'] ?? 0) === 1;

$navGroups = [
    'Overview' => [
        ['dashboard', 'Dashboard', 'layout-dashboard', '/dashboard'],
    ],
    'Money' => [
        ['transactions', 'Transactions', 'arrow-left-right', '/transactions'],
        ['accounts', 'Accounts', 'landmark', '/accounts'],
        ['investments', 'Investments', 'trending-up', '/investments'],
    ],
    'Plan' => [
        ['budgets', 'Budgets', 'target', '/budgets'],
        ['goals', 'Goals', 'star', '/goals'],
        ['bills', 'Bills', 'calendar', '/bills'],
        ['subscriptions', 'Subscriptions', 'repeat', '/subscriptions'],
    ],
    'Analyze' => [
        ['analytics', 'Insights', 'brain', '/analytics'],
        ['simulators', 'Simulators', 'calculator', '/simulators'],
    ],
    'Learn' => [
        ['tutorial', 'How it works', 'help-circle', '/tutorial'],
        ['achievements', 'Achievements', 'trophy', '/achievements'],
        ['news', 'Market news', 'newspaper', '/news'],
        ['blog', 'Guides', 'book-open', '/blog'],
        ['settings', 'Settings', 'settings', '/settings'],
    ],
];
$unread = (int) (Database::scalar("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [Auth::id()]) ?? 0);
?>
<!DOCTYPE html>
<html lang="en" class="<?= $theme === 'dark' ? 'dark' : '' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
<meta name="theme-color" content="#1f5a3f">
<title><?= e($title) ?> · WiseWallet</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="4" fill="#1f5a3f"/><path d="M6 9h11v9H6z" fill="none" stroke="#fff" stroke-width="1.6"/></svg>') ?>">
<link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>">
<script <?= nonce_attr() ?>>
document.documentElement.classList.add('js');(function(){try{var t=localStorage.getItem('ww-theme');if(t){document.documentElement.classList.toggle('dark',t==='dark');}}catch(e){}})();
</script>
</head>
<body>
<div id="scroll-progress"></div>

<div class="min-h-screen lg:grid lg:grid-cols-[244px_1fr]">
  <!-- Sidebar -->
  <aside id="sidebar" class="fixed lg:sticky top-0 left-0 z-40 w-[244px] h-screen lg:h-screen -translate-x-full lg:translate-x-0 transition-transform duration-300 border-r flex flex-col" style="background:rgb(var(--surface));border-color:rgb(var(--line))">
    <div class="flex items-center gap-2.5 px-5 h-16 border-b" style="border-color:rgb(var(--line))">
      <?= logo_mark('w-8 h-8') ?>
      <span class="font-display text-lg font-semibold">WiseWallet</span>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4">
      <?php foreach ($navGroups as $group => $items): ?>
        <div>
          <div class="px-2 mb-1 text-[11px] font-semibold tracking-wide" style="color:rgb(var(--ink-soft))"><?= e($group) ?></div>
          <div class="space-y-0.5">
            <?php foreach ($items as [$key, $label, $ic, $href]): ?>
              <a href="<?= $href ?>" class="nav-link<?= nav_active($key, $nav) ?>"><?= icon($ic, 'w-4 h-4') ?><span><?= e($label) ?></span></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (Auth::isAdmin()): ?>
        <div class="pt-2 border-t" style="border-color:rgb(var(--line))">
          <a href="/admin/index" class="nav-link<?= nav_active('admin', $nav) ?>"><?= icon('shield', 'w-4 h-4') ?><span>Admin</span></a>
        </div>
      <?php endif; ?>
    </nav>
    <div class="p-3 border-t" style="border-color:rgb(var(--line))">
      <div class="flex items-center gap-3 rounded p-2" style="background:rgb(var(--surface-2))">
        <div class="w-8 h-8 rounded grid place-items-center text-white text-sm font-semibold" style="background:rgb(var(--accent))"><?= e(strtoupper(substr($u['name'] ?? 'U', 0, 1))) ?></div>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-medium truncate"><?= e($u['name'] ?? 'User') ?></div>
          <div class="text-xs text-soft truncate"><?= e($u['email'] ?? '') ?></div>
        </div>
        <a href="/logout" title="Sign out" class="text-soft hover:text-[rgb(var(--neg))]"><?= icon('log-out', 'w-4 h-4') ?></a>
      </div>
    </div>
  </aside>
  <div id="nav-backdrop" class="hidden fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

  <!-- Main column -->
  <div class="flex flex-col min-h-screen">
    <header class="sticky top-0 z-20 border-b h-16 flex items-center gap-3 px-4 sm:px-6" style="background:rgb(var(--paper));border-color:rgb(var(--line))">
      <button id="nav-open" class="lg:hidden btn-ghost btn-sm p-2" aria-label="Menu"><?= icon('menu', 'w-4 h-4') ?></button>
      <h1 class="text-xl font-semibold flex-1 truncate"><?= e($title) ?></h1>
      <button data-open-cmdk class="btn-ghost btn-sm hidden sm:inline-flex" title="Search"><?= icon('search', 'w-4 h-4') ?><span class="kbd ml-1">Ctrl K</span></button>
      <button data-privacy-toggle class="btn-ghost btn-sm p-2" title="Privacy mode" aria-label="Privacy mode"><span data-icon="eye"><?= icon('eye', 'w-4 h-4') ?></span><span data-icon="eye-off"><?= icon('eye-off', 'w-4 h-4') ?></span></button>
      <button data-theme-toggle class="btn-ghost btn-sm p-2" title="Toggle theme" aria-label="Toggle theme"><span data-icon="sun"><?= icon('sun', 'w-4 h-4') ?></span><span data-icon="moon"><?= icon('moon', 'w-4 h-4') ?></span></button>
      <a href="/achievements" class="btn-ghost btn-sm p-2 relative" title="Notifications" aria-label="Notifications">
        <?= icon('bell', 'w-4 h-4') ?>
        <?php if ($unread > 0): ?><span class="absolute -top-1 -right-1 w-4 h-4 text-[10px] grid place-items-center rounded-full text-white" style="background:rgb(var(--neg))"><?= $unread ?></span><?php endif; ?>
      </a>
      <button class="js-quick-add btn-primary btn-sm"><?= icon('plus', 'w-4 h-4') ?><span class="hidden sm:inline">Add transaction</span></button>
    </header>

    <main class="flex-1 p-4 sm:p-6 max-w-[1280px] w-full mx-auto animate-fade-up">

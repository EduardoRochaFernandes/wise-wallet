<?php
/**
 * Public layout — top half. Used by landing, blog, news, auth pages.
 * Page sets optional $seo array and $activeNav before requiring this.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#1f5a3f">
<?php require WW_APP . '/views/partials/seo.php'; ?>
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="4" fill="#1f5a3f"/><path d="M6 9h11v9H6z" fill="none" stroke="#fff" stroke-width="1.6"/></svg>') ?>">
<link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>">
<script <?= nonce_attr() ?>>
document.documentElement.classList.add('js');(function(){try{var t=localStorage.getItem('ww-theme');if(t){document.documentElement.classList.toggle('dark',t==='dark');}}catch(e){}})();
</script>
</head>
<body>
<div id="scroll-progress"></div>
<header class="sticky top-0 z-30 border-b" style="background:rgb(var(--paper));border-color:rgb(var(--line))">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-5">
    <a href="/" class="flex items-center gap-2.5">
      <?= logo_mark('w-8 h-8') ?>
      <span class="font-display text-lg font-semibold">WiseWallet</span>
    </a>
    <nav class="hidden md:flex items-center gap-5 ml-4 text-sm text-soft">
      <a href="/#features" class="hover:text-[rgb(var(--ink))]">Features</a>
      <a href="/#tools" class="hover:text-[rgb(var(--ink))]">Tools</a>
      <a href="/blog" class="hover:text-[rgb(var(--ink))]<?= ($activeNav ?? '')==='blog'?' text-[rgb(var(--ink))] font-medium':'' ?>">Guides</a>
      <a href="/news" class="hover:text-[rgb(var(--ink))]<?= ($activeNav ?? '')==='news'?' text-[rgb(var(--ink))] font-medium':'' ?>">Market news</a>
    </nav>
    <div class="flex-1"></div>
    <button data-theme-toggle class="btn-ghost btn-sm p-2" title="Toggle theme" aria-label="Toggle theme"><span data-icon="sun"><?= icon('sun', 'w-4 h-4') ?></span><span data-icon="moon"><?= icon('moon', 'w-4 h-4') ?></span></button>
    <?php if (Auth::check()): ?>
      <a href="/dashboard" class="btn-primary btn-sm">Open app</a>
    <?php else: ?>
      <a href="/login" class="btn-ghost btn-sm">Sign in</a>
      <a href="/register" class="btn-primary btn-sm">Get started</a>
    <?php endif; ?>
  </div>
</header>
<main>

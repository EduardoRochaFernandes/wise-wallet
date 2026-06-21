<?php
/**
 * Public layout — top half. Used by landing, blog, news, auth pages.
 * Page sets optional $seo array and $activeNav before requiring this.
 */
$theme = 'dark';
?>
<!DOCTYPE html>
<html lang="pt" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#090b14">
<?php require WW_APP . '/views/partials/seo.php'; ?>
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="#6366f1"/><path d="M6 9h11v9H6z" fill="none" stroke="#fff" stroke-width="1.6"/></svg>') ?>">
<link rel="stylesheet" href="/assets/css/app.css">
<script <?= nonce_attr() ?>>
(function(){try{var t=localStorage.getItem('ww-theme');if(t){var d=document.documentElement;d.classList.toggle('light',t==='light');d.classList.toggle('dark',t!=='light');}}catch(e){}})();
</script>
</head>
<body>
<div id="scroll-progress"></div>
<header class="sticky top-0 z-30 glass border-b" style="border-color:rgb(var(--line))">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-4">
    <a href="/index.php" class="flex items-center gap-2.5">
      <?= logo_mark('w-9 h-9') ?>
      <span class="font-bold text-lg">Wise<span class="text-brand-400">Wallet</span></span>
    </a>
    <nav class="hidden md:flex items-center gap-1 ml-4 text-sm">
      <a href="/index.php#features" class="nav-link">Funcionalidades</a>
      <a href="/index.php#simuladores" class="nav-link">Simuladores</a>
      <a href="/blog.php" class="nav-link<?= ($activeNav ?? '')==='blog'?' is-active':'' ?>">Blog</a>
      <a href="/news.php" class="nav-link<?= ($activeNav ?? '')==='news'?' is-active':'' ?>">Notícias</a>
    </nav>
    <div class="flex-1"></div>
    <button data-theme-toggle class="btn-ghost btn-sm p-2" title="Tema" aria-label="Tema"><?= icon('sun','w-4 h-4') ?></button>
    <?php if (Auth::check()): ?>
      <a href="/dashboard.php" class="btn-primary btn-sm">Abrir app</a>
    <?php else: ?>
      <a href="/login.php" class="btn-ghost btn-sm">Entrar</a>
      <a href="/register.php" class="btn-primary btn-sm">Criar conta</a>
    <?php endif; ?>
  </div>
</header>
<main>

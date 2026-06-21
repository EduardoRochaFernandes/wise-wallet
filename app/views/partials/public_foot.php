</main>
<footer class="border-t mt-20" style="border-color:rgb(var(--line));background:rgb(var(--surface))">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 grid gap-8 md:grid-cols-4">
    <div class="md:col-span-2">
      <a href="/index.php" class="flex items-center gap-2.5 mb-3"><?= logo_mark('w-9 h-9') ?><span class="font-bold text-lg">Wise<span class="text-brand-400">Wallet</span></span></a>
      <p class="text-soft text-sm max-w-sm">Regista, entende, planeia, simula e aprende sobre o teu dinheiro — do euro de hoje à reforma de amanhã.</p>
      <p class="text-xs text-soft mt-4">Construído com PHP, MySQL e Tailwind. Segurança de nível produção.</p>
    </div>
    <div>
      <h4 class="font-semibold mb-3 text-sm">Produto</h4>
      <ul class="space-y-2 text-sm text-soft">
        <li><a href="/index.php#features" class="hover:text-brand-400">Funcionalidades</a></li>
        <li><a href="/index.php#simuladores" class="hover:text-brand-400">Simuladores</a></li>
        <li><a href="/register.php" class="hover:text-brand-400">Criar conta</a></li>
      </ul>
    </div>
    <div>
      <h4 class="font-semibold mb-3 text-sm">Aprender</h4>
      <ul class="space-y-2 text-sm text-soft">
        <li><a href="/blog.php" class="hover:text-brand-400">Blog</a></li>
        <li><a href="/news.php" class="hover:text-brand-400">Notícias</a></li>
        <li><a href="/index.php#educacao" class="hover:text-brand-400">Educação financeira</a></li>
      </ul>
    </div>
  </div>
  <div class="border-t py-5 text-center text-xs text-soft" style="border-color:rgb(var(--line))">
    © <?= date('Y') ?> WiseWallet — Projeto demonstrativo de finanças pessoais.
  </div>
</footer>
<script src="/assets/js/app.js"></script>
</body>
</html>

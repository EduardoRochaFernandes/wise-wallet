</main>
<footer class="border-t mt-24" style="border-color:rgb(var(--line));background:rgb(var(--surface))">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 grid gap-8 md:grid-cols-4">
    <div class="md:col-span-2">
      <a href="/index.php" class="flex items-center gap-2.5 mb-3"><?= logo_mark('w-8 h-8') ?><span class="font-display text-lg font-semibold">WiseWallet</span></a>
      <p class="text-soft text-sm max-w-sm">A complete ledger for your personal finances — record it, understand it, plan it, and learn from it.</p>
    </div>
    <div>
      <h4 class="font-semibold mb-3 text-sm">Product</h4>
      <ul class="space-y-2 text-sm text-soft">
        <li><a href="/index.php#features" class="hover:text-[rgb(var(--ink))]">Features</a></li>
        <li><a href="/index.php#tools" class="hover:text-[rgb(var(--ink))]">Simulators</a></li>
        <li><a href="/register.php" class="hover:text-[rgb(var(--ink))]">Create account</a></li>
      </ul>
    </div>
    <div>
      <h4 class="font-semibold mb-3 text-sm">Learn</h4>
      <ul class="space-y-2 text-sm text-soft">
        <li><a href="/blog.php" class="hover:text-[rgb(var(--ink))]">Guides</a></li>
        <li><a href="/news.php" class="hover:text-[rgb(var(--ink))]">Market news</a></li>
      </ul>
    </div>
  </div>
  <div class="border-t py-5 text-center text-xs text-soft" style="border-color:rgb(var(--line))">
    <a href="/privacy" class="hover:text-[rgb(var(--ink))]">Privacy &amp; about this project</a>
  </div>
</footer>
<script src="<?= asset('/assets/js/app.js') ?>"></script>
</body>
</html>

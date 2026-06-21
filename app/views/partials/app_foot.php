    </main>
    <footer class="px-6 py-4 text-center text-xs text-soft border-t" style="border-color:rgb(var(--line))">
      WiseWallet 2.0 · <span class="text-pos">●</span> seguro com Argon2id, CSRF &amp; CSP · <?= date('Y') ?>
    </footer>
  </div>
</div>

<!-- Command palette -->
<div id="cmdk" class="hidden fixed inset-0 z-50 p-4 pt-[12vh]" style="background:rgb(0 0 0 / .55);backdrop-filter:blur(4px)">
  <div class="card max-w-xl mx-auto overflow-hidden animate-fade-up">
    <div class="flex items-center gap-2 px-4 border-b" style="border-color:rgb(var(--line))">
      <?= icon('search','w-4 h-4') ?>
      <input id="cmdk-input" class="w-full bg-transparent py-3.5 outline-none text-sm" placeholder="Saltar para… (escreve uma página ou ação)" autocomplete="off">
      <span class="kbd">Esc</span>
    </div>
    <div id="cmdk-list" class="p-2 max-h-[50vh] overflow-y-auto"></div>
  </div>
</div>

<!-- Quick-add transaction modal -->
<div id="qa-modal" class="hidden modal-backdrop">
  <div class="modal">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold">Nova transação</h3>
      <button id="qa-close" class="btn-ghost btn-sm p-2" aria-label="Fechar">✕</button>
    </div>
    <form id="qa-form" class="space-y-3">
      <div class="grid grid-cols-3 gap-2" id="qa-type">
        <label class="btn-ghost btn-sm cursor-pointer justify-center"><input type="radio" name="type" value="expense" class="sr-only" checked> Despesa</label>
        <label class="btn-ghost btn-sm cursor-pointer justify-center"><input type="radio" name="type" value="income" class="sr-only"> Receita</label>
        <label class="btn-ghost btn-sm cursor-pointer justify-center"><input type="radio" name="type" value="transfer" class="sr-only"> Transfer.</label>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="label">Valor (€)</label><input name="amount" type="number" step="0.01" min="0" required class="input" placeholder="0,00"></div>
        <div><label class="label">Data</label><input name="occurred_on" type="date" required class="input" value="<?= date('Y-m-d') ?>"></div>
      </div>
      <div><label class="label">Conta</label><select name="account_id" required class="select"></select></div>
      <div id="qa-cat-wrap"><label class="label">Categoria</label><select name="category_id" class="select"></select></div>
      <div><label class="label">Descrição</label><input name="description" class="input" maxlength="255" placeholder="Ex.: Supermercado"></div>
      <div><label class="label">Notas <span class="text-soft">(opcional)</span></label><textarea name="notes" class="textarea" rows="2" placeholder="Contexto…"></textarea></div>
      <div class="flex gap-2 pt-1">
        <button type="submit" class="btn-primary flex-1">Guardar</button>
        <button type="button" id="qa-cancel" class="btn-ghost">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script src="<?= asset('/assets/js/apexcharts.min.js') ?>"></script>
<script src="<?= asset('/assets/js/app.js') ?>"></script>
<script src="<?= asset('/assets/js/charts.js') ?>"></script>
<script <?= nonce_attr() ?>>
(function () {
  const modal = document.getElementById('qa-modal');
  const form = document.getElementById('qa-form');
  const accSel = form.account_id, catSel = form.category_id, catWrap = document.getElementById('qa-cat-wrap');
  let loaded = false;
  async function load() {
    if (loaded) return; loaded = true;
    try {
      const [accs, cats] = await Promise.all([WW.api('/api/accounts.php'), WW.api('/api/categories.php')]);
      accSel.innerHTML = (accs.data || []).map(a => `<option value="${a.id}">${a.name}</option>`).join('');
      window.__qaCats = cats.data || [];
      renderCats();
    } catch (e) { WW.toast('Não foi possível carregar contas/categorias', 'error'); }
  }
  function renderCats() {
    const type = form.querySelector('input[name=type]:checked').value;
    catWrap.style.display = type === 'transfer' ? 'none' : '';
    const list = (window.__qaCats || []).filter(c => c.type === type);
    catSel.innerHTML = list.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  }
  const open = () => { modal.classList.remove('hidden'); load(); };
  const close = () => modal.classList.add('hidden');
  document.getElementById('quick-add').addEventListener('click', open);
  document.getElementById('qa-close').addEventListener('click', close);
  document.getElementById('qa-cancel').addEventListener('click', close);
  modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
  form.querySelectorAll('input[name=type]').forEach(r => r.addEventListener('change', renderCats));
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = Object.fromEntries(new FormData(form).entries());
    try {
      await WW.api('/api/transactions.php', { method: 'POST', body: fd });
      WW.toast('Transação adicionada', 'success');
      close();
      setTimeout(() => location.reload(), 600);
    } catch (err) { WW.toast(err.message || 'Erro ao guardar', 'error'); }
  });
})();
</script>
</body>
</html>

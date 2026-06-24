/* ════════════════════════════════════════════════════════════════
   WiseWallet 2.0 — core client runtime
   Theme · privacy mode · scroll progress · toasts · CSRF fetch ·
   command palette (Ctrl/⌘+K) · mobile nav.
   ════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  const WW = (window.WW = window.WW || {});
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  /* ── Theme (light default; icons swap via CSS) ────────────── */
  WW.applyTheme = (t) => {
    document.documentElement.classList.toggle('dark', t === 'dark');
    try { localStorage.setItem('ww-theme', t); } catch (e) {}
  };
  WW.toggleTheme = () => {
    WW.applyTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
  };

  /* ── Lock page scroll while any modal / palette is open ───── */
  WW.refreshScrollLock = () => {
    const cmdk = document.getElementById('cmdk');
    const open = document.querySelector('.modal-backdrop:not(.hidden)') || (cmdk && !cmdk.classList.contains('hidden'));
    document.body.classList.toggle('modal-open', !!open);
  };

  /* ── Privacy mode (blur sensitive figures) ────────────────── */
  WW.togglePrivacy = () => {
    const on = document.body.classList.toggle('private');
    try { localStorage.setItem('ww-privacy', on ? '1' : '0'); } catch (e) {}
  };

  /* ── Toast notifications ──────────────────────────────────── */
  WW.toast = (msg, type = 'info') => {
    const colors = { success: 'var(--pos)', error: 'var(--neg)', info: 'var(--accent)', warn: 'var(--warn)' };
    const el = document.createElement('div');
    el.className = 'toast';
    el.style.borderLeft = `4px solid rgb(${colors[type] || colors.info})`;
    el.innerHTML = `<div class="text-sm">${msg}</div>`;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(10px)'; }, 3200);
    setTimeout(() => el.remove(), 3600);
  };

  /* ── Empty-state CTA (used when a chart/list has no data) ─── */
  WW.emptyState = (sel, o) => {
    const el = typeof sel === 'string' ? document.querySelector(sel) : sel;
    if (!el) return;
    const action = o.modal
      ? `<button class="btn-primary btn-sm mt-4 inline-flex" data-modal-open="${o.modal}">${o.cta}</button>`
      : `<a href="${o.href}" class="btn-primary btn-sm mt-4 inline-flex">${o.cta}</a>`;
    el.innerHTML =
      `<div class="text-center py-12 px-4">
         <h3 class="font-display text-lg font-semibold">${o.title}</h3>
         <p class="text-soft text-sm mt-1 max-w-sm mx-auto">${o.text || ''}</p>
         ${action}
       </div>`;
  };

  /* ── CSRF-aware fetch helper for the JSON API ─────────────── */
  WW.csrf = () => ($('meta[name="csrf-token"]') || {}).content || '';
  WW.api = async (url, { method = 'GET', body = null, json = true } = {}) => {
    const opts = { method, headers: { 'X-CSRF-Token': WW.csrf(), 'Accept': 'application/json' } };
    if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    const data = json ? await res.json().catch(() => ({})) : await res.text();
    if (!res.ok) throw Object.assign(new Error(data.error || 'Erro de pedido'), { status: res.status, data });
    return data;
  };

  /* ── Scroll progress bar ──────────────────────────────────── */
  function initScrollProgress() {
    const bar = $('#scroll-progress');
    if (!bar) return;
    const onScroll = () => {
      const h = document.documentElement;
      const pct = (h.scrollTop / (h.scrollHeight - h.clientHeight)) * 100;
      bar.style.width = (pct || 0) + '%';
    };
    document.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Mobile sidebar ───────────────────────────────────────── */
  function initSidebar() {
    const sidebar = $('#sidebar');
    const open = $('#nav-open');
    const backdrop = $('#nav-backdrop');
    if (!sidebar) return;
    const toggle = (show) => {
      sidebar.classList.toggle('-translate-x-full', !show);
      if (backdrop) backdrop.classList.toggle('hidden', !show);
    };
    open && open.addEventListener('click', () => toggle(true));
    backdrop && backdrop.addEventListener('click', () => toggle(false));
  }

  /* ── Command palette (Ctrl/⌘ + K) ─────────────────────────── */
  const COMMANDS = [
    ['Dashboard', '/dashboard'],
    ['Transactions', '/transactions'],
    ['Accounts', '/accounts'],
    ['Budgets', '/budgets'],
    ['Goals', '/goals'],
    ['Bills', '/bills'],
    ['Subscriptions', '/subscriptions'],
    ['Investments', '/investments'],
    ['Insights', '/analytics'],
    ['Simulators', '/simulators'],
    ['Achievements', '/achievements'],
    ['Market news', '/news'],
    ['Guides', '/blog'],
    ['Settings', '/settings'],
    ['Toggle theme', 'action:theme'],
    ['Privacy mode', 'action:privacy'],
    ['Sign out', '/logout'],
  ];

  function initPalette() {
    const root = $('#cmdk');
    if (!root) return;
    const input = $('#cmdk-input', root);
    const list = $('#cmdk-list', root);
    let active = 0, filtered = COMMANDS;

    const render = () => {
      list.innerHTML = filtered.map(([label], i) =>
        `<button data-i="${i}" class="cmdk-item w-full text-left flex items-center gap-3 rounded px-3 py-2.5 ${i === active ? 'bg-[rgb(var(--surface-2))]' : ''}">
           <span class="text-sm">${label}</span></button>`).join('') ||
        `<div class="px-3 py-6 text-center text-soft text-sm">No results</div>`;
    };
    const open = () => { root.classList.remove('hidden'); input.value = ''; filtered = COMMANDS; active = 0; render(); input.focus(); };
    const close = () => root.classList.add('hidden');
    const run = (cmd) => {
      if (!cmd) return;
      const target = cmd[1];
      if (target === 'action:theme') { WW.toggleTheme(); close(); }
      else if (target === 'action:privacy') { WW.togglePrivacy(); close(); }
      else window.location.href = target;
    };

    input.addEventListener('input', () => {
      const q = input.value.toLowerCase();
      filtered = COMMANDS.filter(([l]) => l.toLowerCase().includes(q));
      active = 0; render();
    });
    list.addEventListener('click', (e) => {
      const item = e.target.closest('.cmdk-item');
      if (item) run(filtered[+item.dataset.i]);
    });
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); root.classList.contains('hidden') ? open() : close(); }
      if (root.classList.contains('hidden')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, filtered.length - 1); render(); }
      if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); render(); }
      if (e.key === 'Enter') { e.preventDefault(); run(filtered[active]); }
    });
    $$('[data-open-cmdk]').forEach((b) => b.addEventListener('click', open));
  }

  /* ── Declarative modals, API forms & delete buttons ───────── */
  function initDeclarative() {
    // Open / close modals: [data-modal-open="#id"] and [data-modal-close]
    document.addEventListener('click', (e) => {
      const open = e.target.closest('[data-modal-open]');
      if (open) { const m = document.querySelector(open.dataset.modalOpen); if (m) m.classList.remove('hidden'); }
      const close = e.target.closest('[data-modal-close]');
      if (close) { const m = close.closest('.modal-backdrop'); if (m) m.classList.add('hidden'); }
      if (e.target.classList && e.target.classList.contains('modal-backdrop')) e.target.classList.add('hidden');
    });

    // Delete: <button data-del="/api/x.php" data-id="7" data-confirm="...">
    document.addEventListener('click', async (e) => {
      const del = e.target.closest('[data-del]');
      if (!del) return;
      e.preventDefault();
      if (!confirm(del.dataset.confirm || 'Are you sure you want to delete this?')) return;
      try {
        await WW.api(del.dataset.del, { method: 'DELETE', body: { id: del.dataset.id } });
        WW.toast('Deleted', 'success');
        setTimeout(() => location.reload(), 400);
      } catch (err) { WW.toast(err.message || 'Could not delete', 'error'); }
    });

    // API forms: <form data-api-form="/api/x.php" data-method="POST">
    document.addEventListener('submit', async (e) => {
      const form = e.target.closest('[data-api-form]');
      if (!form) return;
      e.preventDefault();
      const body = Object.fromEntries(new FormData(form).entries());
      form.querySelectorAll('input[type=checkbox]').forEach((c) => { body[c.name] = c.checked ? 1 : 0; });
      const btn = form.querySelector('[type=submit]');
      if (btn) btn.disabled = true;
      try {
        await WW.api(form.dataset.apiForm, { method: form.dataset.method || 'POST', body });
        WW.toast('Saved', 'success');
        setTimeout(() => location.reload(), 450);
      } catch (err) {
        WW.toast(err.message || 'Could not save', 'error');
        if (btn) btn.disabled = false;
      }
    });
  }

  /* ── Boot ─────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', () => {
    $$('[data-theme-toggle]').forEach((b) => b.addEventListener('click', WW.toggleTheme));
    $$('[data-privacy-toggle]').forEach((b) => b.addEventListener('click', WW.togglePrivacy));
    try { if (localStorage.getItem('ww-privacy') === '1') document.body.classList.add('private'); } catch (e) {}
    initScrollProgress();
    initSidebar();
    initPalette();
    initDeclarative();
    // Any click/keypress may open or close a modal — refresh the scroll lock after it.
    const refresh = () => setTimeout(WW.refreshScrollLock, 0);
    document.addEventListener('click', refresh);
    document.addEventListener('keydown', refresh);
  });
})();

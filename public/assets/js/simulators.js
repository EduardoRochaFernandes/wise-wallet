/* ════════════════════════════════════════════════════════════════
   WiseWallet 2.0 — Financial simulators (real formulas + charts).
   ════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';
  const WW = window.WW || {};
  const fmt = (v) => new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(isFinite(v) ? v : 0);
  const fmt2 = (v) => new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(isFinite(v) ? v : 0);
  const pct = (v) => (isFinite(v) ? v : 0).toFixed(2).replace('.', ',') + '%';

  /** Annuity payment for principal P, monthly rate r, n months. */
  const pmt = (P, r, n) => (r > 0 ? (P * r) / (1 - Math.pow(1 + r, -n)) : P / n);

  const SIM = {
    // 1) Crédito habitação
    mortgage(g) {
      const P = +g('amount'), ar = +g('rate'), y = +g('years');
      const r = ar / 100 / 12, n = y * 12, m = pmt(P, r, n);
      let bal = P; const labels = [], data = [];
      for (let k = 0; k <= n; k++) {
        if (k % 12 === 0) { labels.push('Ano ' + (k / 12)); data.push(Math.max(0, Math.round(bal))); }
        bal -= (m - bal * r);
      }
      return { metrics: [['Prestação mensal', fmt2(m)], ['Total pago', fmt(m * n)], ['Juros totais', fmt(m * n - P)]],
        chart: { type: 'area', labels, series: [{ name: 'Capital em dívida', data }] } };
    },
    // 2) Crédito pessoal
    personal(g) {
      const P = +g('amount'), ar = +g('rate'), mo = +g('months');
      const r = ar / 100 / 12, m = pmt(P, r, mo);
      return { metrics: [['Prestação mensal', fmt2(m)], ['Total pago', fmt(m * mo)], ['Custo do crédito', fmt(m * mo - P)]],
        chart: null };
    },
    // 3) Poupança (juros compostos com reforço mensal)
    savings(g) {
      const init = +g('initial'), pm = +g('monthly'), ar = +g('rate'), y = +g('years');
      const r = ar / 100 / 12, n = y * 12; const labels = [], data = [], contrib = [];
      let bal = init, paid = init;
      for (let k = 0; k <= n; k++) {
        if (k % 12 === 0) { labels.push('Ano ' + (k / 12)); data.push(Math.round(bal)); contrib.push(Math.round(paid)); }
        bal = bal * (1 + r) + pm; paid += pm;
      }
      const total = bal, invested = init + pm * n;
      return { metrics: [['Valor final', fmt(total)], ['Total investido', fmt(invested)], ['Juros ganhos', fmt(total - invested)]],
        chart: { type: 'area', labels, series: [{ name: 'Património', data }, { name: 'Investido', data: contrib }] } };
    },
    // 4) Reforma
    retirement(g) {
      const age = +g('age'), retAge = +g('retage'), cur = +g('current'), pm = +g('monthly'), ar = +g('rate'), income = +g('income'), dur = +g('duration');
      const r = ar / 100 / 12, n = Math.max(0, (retAge - age) * 12);
      let bal = cur; const labels = [], data = [];
      for (let k = 0; k <= n; k++) { if (k % 12 === 0) { labels.push(String(age + k / 12)); data.push(Math.round(bal)); } bal = bal * (1 + r) + pm; }
      const pot = bal;
      const needed = income * 12 * dur; // simplistic capital needed (no decumulation growth)
      const gap = needed - pot;
      const extra = gap > 0 ? pmt(gap, r, n) : 0;
      return { metrics: [['Capital à reforma', fmt(pot)], ['Necessário', fmt(needed)], gap > 0 ? ['Falta poupar/mês', fmt2(extra)] : ['Situação', 'No bom caminho ✓']],
        chart: { type: 'area', labels, series: [{ name: 'Capital acumulado', data }] } };
    },
    // 5) Investimento (nominal vs real)
    investment(g) {
      const init = +g('initial'), ar = +g('rate'), y = +g('years'), inf = +g('inflation');
      const labels = [], nom = [], real = [];
      for (let k = 0; k <= y; k++) { labels.push('Ano ' + k); const v = init * Math.pow(1 + ar / 100, k); nom.push(Math.round(v)); real.push(Math.round(v / Math.pow(1 + inf / 100, k))); }
      const fv = init * Math.pow(1 + ar / 100, y), rv = fv / Math.pow(1 + inf / 100, y);
      return { metrics: [['Valor nominal', fmt(fv)], ['Valor real (hoje)', fmt(rv)], ['Perda p/ inflação', fmt(fv - rv)]],
        chart: { type: 'area', labels, series: [{ name: 'Nominal', data: nom }, { name: 'Real', data: real }] } };
    },
    // 6) IRS Portugal (estimativa simplificada, escalões continente)
    irs(g) {
      const gross = +g('income');
      const brackets = [[7703, 0.1325], [11623, 0.18], [16472, 0.23], [21321, 0.26], [27146, 0.3275], [39791, 0.37], [51997, 0.435], [81199, 0.45], [Infinity, 0.48]];
      const taxable = Math.max(0, gross - 4104); // dedução específica
      let tax = 0, prev = 0;
      for (const [limit, rate] of brackets) { if (taxable > prev) { tax += (Math.min(taxable, limit) - prev) * rate; prev = limit; } else break; }
      const net = gross - tax, effective = gross > 0 ? (tax / gross) * 100 : 0;
      return { metrics: [['IRS estimado', fmt(tax)], ['Líquido anual', fmt(net)], ['Taxa efetiva', pct(effective)]],
        chart: { type: 'donut', labels: ['Líquido', 'IRS'], values: [Math.round(net), Math.round(tax)] } };
    },
    // 7) Leasing automóvel
    leasing(g) {
      const price = +g('price'), entry = +g('entry'), residual = +g('residual'), mo = +g('months'), ar = +g('rate');
      const r = ar / 100 / 12;
      const financed = price * (1 - entry / 100);
      const res = price * (residual / 100);
      // payment for (financed - PV(residual))
      const pvRes = res / Math.pow(1 + r, mo);
      const m = pmt(financed - pvRes, r, mo);
      return { metrics: [['Prestação mensal', fmt2(m)], ['Entrada', fmt(price * entry / 100)], ['Valor residual', fmt(res)]],
        chart: { type: 'donut', labels: ['Entrada', 'Prestações', 'Residual'], values: [Math.round(price * entry / 100), Math.round(m * mo), Math.round(res)] } };
    },
    // 8) Fundo de emergência
    emergency(g) {
      const exp = +g('expenses'), months = +g('months'), cur = +g('current'), save = +g('save');
      const target = exp * months, gap = Math.max(0, target - cur);
      const toGo = save > 0 ? Math.ceil(gap / save) : Infinity;
      const labels = [], data = []; let bal = cur, i = 0;
      while (bal < target && i < 60) { labels.push('M' + i); data.push(Math.round(bal)); bal += save; i++; }
      labels.push('M' + i); data.push(Math.round(Math.min(bal, target)));
      return { metrics: [['Objetivo', fmt(target)], ['Em falta', fmt(gap)], ['Meses p/ atingir', isFinite(toGo) ? toGo + ' meses' : '—']],
        chart: { type: 'area', labels, series: [{ name: 'Fundo', data }] } };
    },
  };

  const charts = {};
  function draw(el, key, spec) {
    if (charts[key]) { try { charts[key].destroy(); } catch (e) {} charts[key] = null; }
    if (!spec) { el.innerHTML = ''; return; }
    if (spec.type === 'donut') charts[key] = WW.donutChart(el, spec.labels, spec.values);
    else if (spec.type === 'area') charts[key] = WW.areaChart(el, spec.labels, spec.series);
    else charts[key] = WW.barChart(el, spec.labels, spec.series);
  }

  function compute(key) {
    const panel = document.getElementById('panel-' + key);
    if (!panel || !SIM[key]) return;
    const g = (f) => { const el = panel.querySelector('[name="' + f + '"]'); return el ? el.value : 0; };
    const res = SIM[key](g);
    panel.querySelector('.sim-out').innerHTML = res.metrics.map(([l, v]) =>
      `<div class="rounded-xl p-4 text-center" style="background:rgb(var(--surface-2))"><div class="text-soft text-xs">${l}</div><div class="text-xl font-extrabold mt-1">${v}</div></div>`).join('');
    draw(panel.querySelector('.sim-chart'), key, res.chart);
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Tabs
    document.querySelectorAll('[data-sim]').forEach((btn) => btn.addEventListener('click', () => {
      const key = btn.dataset.sim;
      document.querySelectorAll('[data-sim]').forEach((b) => b.classList.toggle('is-active', b === btn));
      document.querySelectorAll('.sim-panel').forEach((p) => p.classList.toggle('hidden', p.id !== 'panel-' + key));
      compute(key);
    }));
    // Live recompute
    document.querySelectorAll('.sim-panel').forEach((panel) => {
      panel.addEventListener('input', () => compute(panel.id.replace('panel-', '')));
    });
    // initial
    const first = document.querySelector('[data-sim]');
    if (first) first.click();
  });
})();

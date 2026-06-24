/* ════════════════════════════════════════════════════════════════
   WiseWallet 2.0 — ApexCharts theme + reusable chart builders.
   Reads CSS design tokens so charts follow the active light/dark theme.
   ════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';
  const WW = (window.WW = window.WW || {});
  if (typeof ApexCharts === 'undefined') return;

  // Our CSS vars are space-separated triplets ("31 90 63") for Tailwind's
  // rgb(var(--x) / <alpha>) trick. ApexCharts' internal color math expects
  // classic comma-separated rgb(), and silently breaks (zero-size slice
  // paths) if fed the space syntax — so always emit commas here.
  const cssVar = (name) => {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    if (!v) return '#888';
    const parts = v.split(/\s+/).join(',');
    return `rgb(${parts})`;
  };

  const palette = ['#1f5a3f', '#9e6b2a', '#2f6f7f', '#a53a2a', '#4f8e6c', '#7c6f64', '#b5852a', '#3f6b5a', '#6b6f4a', '#8a8378'];

  WW.chartBase = () => ({
    chart: { fontFamily: 'Inter, sans-serif', toolbar: { show: false }, foreColor: cssVar('--ink-soft'), animations: { easing: 'easeinout', speed: 600 } },
    colors: palette,
    grid: { borderColor: cssVar('--line'), strokeDashArray: 4 },
    tooltip: { theme: document.documentElement.classList.contains('light') ? 'light' : 'dark' },
    dataLabels: { enabled: false },
    legend: { labels: { colors: cssVar('--ink-soft') } },
  });

  const registry = [];
  WW.makeChart = (sel, options) => {
    const el = typeof sel === 'string' ? document.querySelector(sel) : sel;
    if (!el) return null;
    const chart = new ApexCharts(el, options);
    chart.render();
    registry.push(chart);
    return chart;
  };

  /* Area chart — 12-month cash flow etc. */
  WW.areaChart = (sel, categories, series) =>
    WW.makeChart(sel, Object.assign(WW.chartBase(), {
      chart: Object.assign(WW.chartBase().chart, { type: 'area', height: 300 }),
      series,
      xaxis: { categories, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: (v) => Math.round(v) + ' €' } },
      stroke: { curve: 'smooth', width: 2.5 },
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
    }));

  /* Donut — category breakdown / diversification */
  WW.donutChart = (sel, labels, values) =>
    WW.makeChart(sel, Object.assign(WW.chartBase(), {
      chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif', foreColor: cssVar('--ink-soft') },
      series: values, labels,
      legend: { position: 'bottom', labels: { colors: cssVar('--ink-soft') } },
      plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Total', formatter: (w) => Math.round(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) + ' €' } } } } },
      stroke: { colors: [cssVar('--surface')] },
    }));

  /* Bar — income/expense by category, weekday spend */
  WW.barChart = (sel, categories, series, horizontal = false) =>
    WW.makeChart(sel, Object.assign(WW.chartBase(), {
      chart: Object.assign(WW.chartBase().chart, { type: 'bar', height: 300 }),
      series,
      xaxis: { categories },
      plotOptions: { bar: { horizontal, borderRadius: 6, columnWidth: '55%' } },
    }));

  /* Radial gauge — Financial Health Score 0..100 */
  WW.gaugeChart = (sel, value) =>
    WW.makeChart(sel, {
      chart: { type: 'radialBar', height: 280, fontFamily: 'Inter, sans-serif' },
      series: [value],
      colors: [value >= 75 ? '#22c55e' : value >= 50 ? '#f59e0b' : '#ef4444'],
      plotOptions: { radialBar: {
        hollow: { size: '62%' },
        track: { background: cssVar('--line') },
        dataLabels: {
          name: { offsetY: 22, color: cssVar('--ink-soft'), fontSize: '13px' },
          value: { offsetY: -16, color: cssVar('--ink'), fontSize: '40px', fontWeight: 700, formatter: (v) => Math.round(v) },
        },
      } },
      labels: ['Saúde Financeira'],
      fill: { type: 'gradient', gradient: { shade: 'light', shadeIntensity: 0.3, gradientToColors: ['#2f6f4f'], stops: [0, 100] } },
    });

  /* Re-theme all charts when the theme switches */
  const _toggle = WW.toggleTheme;
  if (_toggle) WW.toggleTheme = function () { _toggle(); setTimeout(() => registry.forEach((c) => c.updateOptions(WW.chartBase(), false, false)), 60); };
})();

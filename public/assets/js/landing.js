/* ════════════════════════════════════════════════════════════════
   WiseWallet 2.0 — landing micro-interactions
   Scroll-reveal (viewport-based, IO-independent) · parallax · count-up
   ════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  function reveal() {
    const els = Array.from(document.querySelectorAll('.reveal, [data-reveal-stagger]'));
    document.querySelectorAll('[data-reveal-stagger]').forEach((g) => {
      Array.from(g.children).forEach((c, i) => { c.style.transitionDelay = (i * 80) + 'ms'; });
    });
    const inView = (el) => {
      const r = el.getBoundingClientRect();
      return r.top < (window.innerHeight * 0.92) && r.bottom > 0;
    };
    const check = () => els.forEach((el) => { if (!el.classList.contains('in') && inView(el)) el.classList.add('in'); });
    check();
    window.addEventListener('scroll', check, { passive: true });
    window.addEventListener('resize', check, { passive: true });
    // Safety net: never leave content hidden if something goes wrong.
    setTimeout(() => els.forEach((el) => el.classList.add('in')), 2500);
  }

  function parallax() {
    const els = document.querySelectorAll('[data-parallax]');
    if (!els.length) return;
    let ticking = false;
    const update = () => {
      const y = window.scrollY;
      els.forEach((el) => {
        const speed = parseFloat(el.dataset.parallax) || 0.2;
        el.style.transform = 'translateY(' + (y * speed).toFixed(1) + 'px)';
      });
      ticking = false;
    };
    window.addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(update); ticking = true; } }, { passive: true });
  }

  function counters() {
    const run = (el) => {
      const to = parseFloat(el.dataset.count);
      const suffix = el.dataset.suffix || '';
      const dur = 1300, t0 = performance.now();
      const tick = (t) => {
        const p = Math.min(1, (t - t0) / dur);
        el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3))) + suffix;
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    document.querySelectorAll('[data-count]').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.top < window.innerHeight) { run(el); el.dataset.done = '1'; }
    });
    window.addEventListener('scroll', () => {
      document.querySelectorAll('[data-count]:not([data-done])').forEach((el) => {
        if (el.getBoundingClientRect().top < window.innerHeight * 0.9) { run(el); el.dataset.done = '1'; }
      });
    }, { passive: true });
  }

  function heroFallback() {
    const v = document.querySelector('.hero-media video');
    if (!v) return;
    const hide = () => { v.style.display = 'none'; };
    v.addEventListener('error', hide, true);
    v.querySelectorAll('source').forEach((s) => s.addEventListener('error', hide));
    // If no source has loaded shortly after, fall back to the aurora.
    setTimeout(() => { if (v.readyState === 0) hide(); }, 1500);
  }

  document.addEventListener('DOMContentLoaded', () => { reveal(); parallax(); counters(); heroFallback(); });
})();

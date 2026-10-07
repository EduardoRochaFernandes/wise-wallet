/**
 * Captures the README screenshots from a RUNNING instance (docker compose up).
 * Used by .github/workflows/screenshots.yml; run locally with:
 *   npm i --no-save playwright && npx playwright install chromium
 *   WW_BASE=http://localhost:8080 node scripts/screenshots.js
 * Logs in with the documented DEMO account only.
 */
const { chromium } = require('playwright');
const path = require('path');

const BASE = (process.env.WW_BASE || 'http://localhost:8080').replace(/\/$/, '');
const OUT = path.resolve(__dirname, '..', 'docs', 'screenshots');

const PUBLIC_PAGES = [['landing', '/'], ['login', '/login']];
const APP_PAGES = [
  ['dashboard', '/dashboard'], ['transactions', '/transactions'], ['budgets', '/budgets'],
  ['investments', '/investments'], ['analytics', '/analytics'], ['simulators', '/simulators'],
];

(async () => {
  const browser = await chromium.launch();
  const page = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
  const shot = async (name, url) => {
    await page.goto(BASE + url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500); // let ApexCharts animate in
    await page.screenshot({ path: path.join(OUT, name + '.png') });
    console.log('captured', name);
  };
  for (const [n, u] of PUBLIC_PAGES) await shot(n, u);
  await page.goto(BASE + '/login');
  await page.fill('input[name=email]', 'demo@wisewallet.local');
  await page.fill('input[name=password]', 'Demo@WiseWallet2026');
  await Promise.all([page.waitForURL('**/dashboard'), page.click('form button')]);
  for (const [n, u] of APP_PAGES) await shot(n, u);
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

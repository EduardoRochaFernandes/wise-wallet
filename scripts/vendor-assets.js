/**
 * Copies third-party browser assets from node_modules into public/assets so the
 * app can serve them first-party (keeps the Content-Security-Policy at 'self').
 */
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const targets = [
  {
    from: path.join(root, 'node_modules', 'apexcharts', 'dist', 'apexcharts.min.js'),
    to: path.join(root, 'public', 'assets', 'js', 'apexcharts.min.js'),
  },
];

let copied = 0;
for (const t of targets) {
  try {
    fs.mkdirSync(path.dirname(t.to), { recursive: true });
    fs.copyFileSync(t.from, t.to);
    console.log('vendored →', path.relative(root, t.to));
    copied++;
  } catch (err) {
    console.error('!! could not vendor', t.from, '\n  ', err.message);
  }
}
console.log(`Done. ${copied}/${targets.length} asset(s) vendored.`);

#!/usr/bin/env bash
# Runs once when the Codespace / dev container is created.
# MariaDB is already up with the schema + DEMO seed imported (see docker-compose.yml),
# and Apache is already serving public/ -- the only thing missing is the CSS bundle,
# because the bind-mounted working tree hides the one built into the image.
set -euo pipefail
cd "$(dirname "$0")/.."

git config --global --add safe.directory "$PWD" || true

echo "==> Building CSS + vendoring ApexCharts..."
npm ci --no-audit --no-fund
npm run build

echo "==> Making runtime folders writable for Apache..."
mkdir -p storage/logs data
chown -R www-data:www-data storage data || chmod -R a+rwX storage data

echo
echo "==> Ready. WiseWallet is served by Apache on port 80 (open the forwarded port)."
echo "    DEMO logins (throw-away accounts that exist only in this container's database):"
echo "      demo@wisewallet.local   /  Demo@WiseWallet2026"
echo "      admin@wisewallet.local  /  Admin@WiseWallet2026"

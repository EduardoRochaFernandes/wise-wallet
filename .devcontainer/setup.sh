#!/usr/bin/env bash
# Runs once when the Codespace/devcontainer is created.
# Builds the front-end assets and imports the database — no local install
# required by the person opening the Codespace; everything runs in the
# container the devcontainer already provisioned.
set -e
cd "$(dirname "$0")/.."

echo "==> Installing npm dependencies..."
npm install --no-audit --no-fund

echo "==> Building CSS + vendoring assets..."
npm run build

echo "==> Refreshing CA bundle for live news/FX/crypto..."
php scripts/refresh-ca-bundle.php || true

echo "==> Writing .env for the container's MariaDB service..."
if [ ! -f .env ]; then
  cp .env.example .env
  APP_KEY=$(php -r "echo bin2hex(random_bytes(32));")
  sed -i "s/^APP_KEY=.*/APP_KEY=${APP_KEY}/" .env
  sed -i "s/^DB_HOST=.*/DB_HOST=db/" .env
  sed -i "s/^DB_PASS=.*/DB_PASS=wisewallet/" .env
fi

echo "==> Waiting for MariaDB..."
for i in $(seq 1 30); do
  if mysql -h db -uroot -pwisewallet -e "SELECT 1" >/dev/null 2>&1; then break; fi
  sleep 1
done

echo "==> Importing schema + seed data..."
mysql -h db -uroot -pwisewallet < database/wisewallet.sql

echo "==> Generating sitemap..."
php scripts/make-sitemap.php || true

echo "==> Ready. WiseWallet is live on the forwarded 8080 port."
echo "    Demo login:  demo@wisewallet.local / Demo@WiseWallet2026"

#!/usr/bin/env bash
# WiseWallet 2.0 — one-click setup (Linux/macOS, or Git Bash on Windows).
set -e
cd "$(dirname "$0")"

echo "==================================================="
echo "   WiseWallet 2.0 — Automated installation"
echo "==================================================="

# ---- Locate PHP / MySQL (prefer XAMPP on Windows) ----
PHP="php"; MYSQL="mysql"; MYSQLD=""
[ -x "/c/xampp/php/php.exe" ] && PHP="/c/xampp/php/php.exe"
[ -x "/c/xampp/mysql/bin/mysql.exe" ] && MYSQL="/c/xampp/mysql/bin/mysql.exe"
[ -x "/c/xampp/mysql/bin/mysqld.exe" ] && MYSQLD="/c/xampp/mysql/bin/mysqld.exe"

echo "PHP:   $PHP"
echo "MySQL: $MYSQL"

echo; echo "[1/6] Installing npm dependencies..."
npm install --no-audit --no-fund

echo; echo "[2/6] Building assets (ApexCharts + Tailwind)..."
npm run build

echo; echo "[3/6] Configuring environment (.env)..."
"$PHP" scripts/setup-env.php

echo; echo "Updating CA bundle for live news/FX/crypto (skips gracefully if offline)..."
"$PHP" scripts/refresh-ca-bundle.php || true

echo; echo "[4/6] Starting MySQL and waiting..."
if [ "$("$PHP" -r "echo @fsockopen('127.0.0.1',3306,\$e,\$s,1)?1:0;")" = "0" ]; then
  if [ -n "$MYSQLD" ]; then
    "$MYSQLD" --defaults-file="/c/xampp/mysql/bin/my.ini" --console >/dev/null 2>&1 &
    "$PHP" scripts/wait-mysql.php
  else
    # Generic Linux: assume a running mysqld/service
    "$PHP" scripts/wait-mysql.php || { echo "Start the MySQL service and try again."; exit 1; }
  fi
else
  echo "MySQL is already running."
fi

echo; echo "[5/6] Importing database..."
"$MYSQL" -u root --default-character-set=utf8mb4 < database/wisewallet.sql
echo "Database imported."

echo; echo "[6/6] Generating sitemap and starting the server..."
"$PHP" scripts/make-sitemap.php

echo "==================================================="
echo " Done! Opening http://localhost:8000"
echo " Admin: admin@wisewallet.local / Admin@WiseWallet2026"
echo " Demo:  demo@wisewallet.local  / Demo@WiseWallet2026"
echo "==================================================="
( command -v xdg-open >/dev/null && xdg-open http://localhost:8000 ) || \
( command -v open >/dev/null && open http://localhost:8000 ) || \
( command -v start >/dev/null && start http://localhost:8000 ) || true

"$PHP" -S localhost:8000 -t public scripts/router.php

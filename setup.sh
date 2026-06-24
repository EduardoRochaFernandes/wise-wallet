#!/usr/bin/env bash
# WiseWallet 2.0 — one-click setup (Linux/macOS, or Git Bash on Windows).
set -e
cd "$(dirname "$0")"

echo "==================================================="
echo "   WiseWallet 2.0 — Instalação automática"
echo "==================================================="

# ---- Locate PHP / MySQL (prefer XAMPP on Windows) ----
PHP="php"; MYSQL="mysql"; MYSQLD=""
[ -x "/c/xampp/php/php.exe" ] && PHP="/c/xampp/php/php.exe"
[ -x "/c/xampp/mysql/bin/mysql.exe" ] && MYSQL="/c/xampp/mysql/bin/mysql.exe"
[ -x "/c/xampp/mysql/bin/mysqld.exe" ] && MYSQLD="/c/xampp/mysql/bin/mysqld.exe"

echo "PHP:   $PHP"
echo "MySQL: $MYSQL"

echo; echo "[1/6] Instalar dependências npm..."
npm install --no-audit --no-fund

echo; echo "[2/6] Compilar assets (ApexCharts + Tailwind)..."
npm run build

echo; echo "[3/6] Configurar ambiente (.env)..."
"$PHP" scripts/setup-env.php

echo; echo "[4/6] Arrancar MySQL e aguardar..."
if [ "$("$PHP" -r "echo @fsockopen('127.0.0.1',3306,\$e,\$s,1)?1:0;")" = "0" ]; then
  if [ -n "$MYSQLD" ]; then
    "$MYSQLD" --defaults-file="/c/xampp/mysql/bin/my.ini" --console >/dev/null 2>&1 &
    "$PHP" scripts/wait-mysql.php
  else
    # Generic Linux: assume a running mysqld/service
    "$PHP" scripts/wait-mysql.php || { echo "Inicie o serviço MySQL e tente novamente."; exit 1; }
  fi
else
  echo "MySQL já está a correr."
fi

echo; echo "[5/6] Importar base de dados..."
"$MYSQL" -u root --default-character-set=utf8mb4 < database/wisewallet.sql
echo "Base de dados importada."

echo; echo "[6/6] Gerar sitemap e arrancar o servidor..."
"$PHP" scripts/make-sitemap.php

echo "==================================================="
echo " Pronto! A abrir http://localhost:8000"
echo " Admin: admin@wisewallet.local / Admin@WiseWallet2026"
echo " Demo:  demo@wisewallet.local  / Demo@WiseWallet2026"
echo "==================================================="
( command -v xdg-open >/dev/null && xdg-open http://localhost:8000 ) || \
( command -v open >/dev/null && open http://localhost:8000 ) || \
( command -v start >/dev/null && start http://localhost:8000 ) || true

"$PHP" -S localhost:8000 -t public

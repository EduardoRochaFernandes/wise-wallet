@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"
title WiseWallet 2.0 - Setup
echo ===================================================
echo    WiseWallet 2.0 - Instalacao automatica (1 clique)
echo ===================================================
echo.

REM ---- Locate PHP (prefer XAMPP) ----
set "PHP=php"
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
REM ---- Locate MySQL (prefer XAMPP) ----
set "MYSQL=mysql"
set "MYSQLD="
if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
if exist "C:\xampp\mysql\bin\mysqld.exe" set "MYSQLD=C:\xampp\mysql\bin\mysqld.exe"

echo Usando PHP:   %PHP%
echo Usando MySQL: %MYSQL%
echo.

echo [1/6] Instalar dependencias npm...
call npm install --no-audit --no-fund || goto :err

echo.
echo [2/6] Compilar assets (ApexCharts + Tailwind CSS)...
call npm run build || goto :err

echo.
echo [3/6] Configurar ambiente (.env)...
"%PHP%" scripts\setup-env.php || goto :err

echo.
echo [4/6] Arrancar MySQL e aguardar...
for /f %%R in ('"%PHP%" -r "echo @fsockopen('127.0.0.1',3306,$e,$s,1)?1:0;"') do set "DBUP=%%R"
if "!DBUP!"=="0" (
  if defined MYSQLD (
    echo Iniciando mysqld...
    start "" /b "!MYSQLD!" --defaults-file="C:\xampp\mysql\bin\my.ini"
    "%PHP%" scripts\wait-mysql.php || goto :err
  ) else (
    echo MySQL nao esta a correr. Inicie-o no painel do XAMPP e volte a executar.
    goto :err
  )
) else ( echo MySQL ja esta a correr. )

echo.
echo [5/6] Importar base de dados (database\wisewallet.sql)...
"%MYSQL%" -u root --default-character-set=utf8mb4 < "database\wisewallet.sql" || goto :err
echo Base de dados importada.

echo.
echo [6/6] Gerar sitemap e arrancar o servidor...
"%PHP%" scripts\make-sitemap.php
echo.
echo ===================================================
echo  Pronto! A abrir http://localhost:8000
echo  Admin: admin@wisewallet.local / Admin@WiseWallet2026
echo  Demo:  demo@wisewallet.local  / Demo@WiseWallet2026
echo  (Ctrl+C para parar o servidor)
echo ===================================================
start "" http://localhost:8000
"%PHP%" -S localhost:8000 -t public
goto :eof

:err
echo.
echo *** Ocorreu um erro durante o setup. Verifique as mensagens acima. ***
pause
exit /b 1

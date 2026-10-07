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

echo [1/6] Installing npm dependencies...
call npm install --no-audit --no-fund || goto :err

echo.
echo [2/6] Building assets (ApexCharts + Tailwind CSS)...
call npm run build || goto :err

echo.
echo [3/6] Configuring environment (.env)...
"%PHP%" scripts\setup-env.php || goto :err

echo.
echo Updating CA bundle for live news/FX/crypto (skips gracefully if offline)...
"%PHP%" scripts\refresh-ca-bundle.php

echo.
echo [4/6] Starting MySQL and waiting...
for /f %%R in ('"%PHP%" -r "echo @fsockopen('127.0.0.1',3306,$e,$s,1)?1:0;"') do set "DBUP=%%R"
if "!DBUP!"=="0" (
  if defined MYSQLD (
    echo Starting mysqld...
    start "" /b "!MYSQLD!" --defaults-file="C:\xampp\mysql\bin\my.ini"
    "%PHP%" scripts\wait-mysql.php || goto :err
  ) else (
    echo MySQL is not running. Start it from the XAMPP control panel and run this script again.
    goto :err
  )
) else ( echo MySQL is already running. )

echo.
echo [5/6] Importing database (database\wisewallet.sql)...
"%MYSQL%" -u root --default-character-set=utf8mb4 < "database\wisewallet.sql" || goto :err
echo Database imported.

echo.
echo [6/6] Generating sitemap and starting the server...
"%PHP%" scripts\make-sitemap.php
echo.
echo ===================================================
echo  Done! Opening http://localhost:8000
echo  Admin: admin@wisewallet.local / Admin@WiseWallet2026
echo  Demo:  demo@wisewallet.local  / Demo@WiseWallet2026
echo  (Press Ctrl+C to stop the server)
echo ===================================================
start "" http://localhost:8000
"%PHP%" -S localhost:8000 -t public scripts/router.php
goto :eof

:err
echo.
echo *** Setup failed. Check the messages above. ***
pause
exit /b 1

<?php
/** Create .env from .env.example (once), injecting a fresh APP_KEY. */
declare(strict_types=1);
$root = dirname(__DIR__);
$env = $root . '/.env';
if (file_exists($env)) { echo ".env already exists - kept.\n"; exit(0); }
$tpl = (string) file_get_contents($root . '/.env.example');
$tpl = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . bin2hex(random_bytes(32)), $tpl);
file_put_contents($env, $tpl);
echo ".env created with a fresh APP_KEY.\n";

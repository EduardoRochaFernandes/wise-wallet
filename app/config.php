<?php
/**
 * WiseWallet 2.0 — Central configuration loader.
 *
 * Loads `.env` (if present) into a tiny config registry and exposes the
 * `ww_config()` helper. No external dependency (no Composer / vendor lib).
 */

declare(strict_types=1);

if (defined('WW_CONFIG_LOADED')) {
    return;
}
define('WW_CONFIG_LOADED', true);

define('WW_ROOT', dirname(__DIR__));            // project root
define('WW_APP', __DIR__);                       // /app
define('WW_PUBLIC', WW_ROOT . '/public');
define('WW_STORAGE', WW_ROOT . '/storage');
define('WW_DATA', WW_ROOT . '/data');

/**
 * Minimal .env parser — supports KEY=value, quoted values and # comments.
 */
function ww_load_env(string $path): array
{
    $vars = [];
    if (!is_file($path)) {
        return $vars;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip surrounding quotes.
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'")) {
            $value = substr($value, 1, -1);
        }
        $vars[$key] = $value;
    }
    return $vars;
}

$GLOBALS['__ww_env'] = ww_load_env(WW_ROOT . '/.env');

/**
 * Read a configuration value (env first, then default).
 * Casts the strings "true"/"false" to booleans automatically.
 */
function ww_config(string $key, $default = null)
{
    $env = $GLOBALS['__ww_env'][$key] ?? getenv($key);
    if ($env === false || $env === null || $env === '') {
        return $default;
    }
    $lower = strtolower((string) $env);
    if ($lower === 'true')  return true;
    if ($lower === 'false') return false;
    return $env;
}

// ── Derived runtime settings ───────────────────────────────────
define('WW_ENV', ww_config('APP_ENV', 'local'));
define('WW_DEBUG', (bool) ww_config('APP_DEBUG', WW_ENV !== 'production'));
define('WW_NAME', ww_config('APP_NAME', 'WiseWallet'));
define('WW_URL', rtrim((string) ww_config('APP_URL', 'http://localhost:8000'), '/'));

// Fail loudly in dev, silently (logged) in production.
error_reporting(E_ALL);
ini_set('display_errors', WW_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', WW_STORAGE . '/logs/php-error.log');

date_default_timezone_set('Europe/Lisbon');

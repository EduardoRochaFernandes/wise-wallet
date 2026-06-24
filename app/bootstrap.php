<?php
/**
 * WiseWallet 2.0 — Application bootstrap.
 *
 * Every page and API endpoint starts with:  require __DIR__ . '/../app/bootstrap.php';
 * It wires configuration, the database gateway, the security stack and helpers,
 * starts a hardened session, sends security headers and enforces CSRF on every
 * state-changing request automatically.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/Database.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/icons.php';

// Security stack
require __DIR__ . '/Security/Session.php';
require __DIR__ . '/Security/Headers.php';
require __DIR__ . '/Security/Csrf.php';
require __DIR__ . '/Security/Validator.php';
require __DIR__ . '/Security/RateLimit.php';
require __DIR__ . '/Security/Audit.php';
require __DIR__ . '/Security/Totp.php';
require __DIR__ . '/Security/Pwned.php';
require __DIR__ . '/Security/Auth.php';

// Services / models are loaded on demand.
spl_autoload_register(static function (string $class): void {
    foreach (['/Models/', '/Services/'] as $dir) {
        $file = WW_APP . $dir . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

Session::start();
Headers::send(Session::nonce());

// Canonical URL: redirect direct GET hits on *.php (outside /api/) to the
// extension-less path. Internal links already use clean paths; this is a
// safety net for bookmarks/typed URLs and keeps a single canonical address.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if (substr($reqPath, -4) === '.php' && strpos($reqPath, '/api/') !== 0) {
        $clean = substr($reqPath, 0, -4);
        if (substr($clean, -6) === '/index') { $clean = substr($clean, 0, -6) ?: '/'; }
        $qs = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
        header('Location: ' . $clean . ($qs ? '?' . $qs : ''), true, 301);
        exit;
    }
}

// Request firewall: IP filtering, API rate limiting, WAF-lite signatures.
Firewall::guard();
// Bind the session to the device fingerprint (token-theft defence).
Auth::enforceFingerprint();

// Global CSRF guard for POST/PUT/PATCH/DELETE.
Csrf::check();

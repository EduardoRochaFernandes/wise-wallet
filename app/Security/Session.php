<?php
/**
 * WiseWallet 2.0 — Secure session bootstrap.
 *
 *  • HttpOnly + SameSite=Lax + Secure (when on HTTPS) cookies
 *  • Idle timeout (auto-logout after SESSION_LIFETIME seconds)
 *  • Session-id regeneration on privilege change / periodically
 *  • Per-request CSP nonce for inline <script> tags
 */

declare(strict_types=1);

final class Session
{
    private const REGEN_EVERY = 300; // rotate the session id every 5 minutes

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure   = (bool) ww_config('SESSION_SECURE', false);
        $lifetime = (int) ww_config('SESSION_LIFETIME', 1800);

        session_name('WW_SESSID');
        session_set_cookie_params([
            'lifetime' => 0,            // session cookie (until browser closes)
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_start();

        self::enforceTimeout($lifetime);
        self::rotate();
        self::ensureNonce();
    }

    /** Auto-logout after a period of inactivity. */
    private static function enforceTimeout(int $lifetime): void
    {
        $now = time();
        if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > $lifetime) {
            self::destroy();
            session_start();
            $_SESSION['flash_error'] = 'A sua sessão expirou por inatividade. Inicie sessão novamente.';
        }
        $_SESSION['last_activity'] = $now;
    }

    /** Periodically regenerate the id to limit fixation windows. */
    private static function rotate(): void
    {
        $now = time();
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = $now;
            return;
        }
        if (($now - (int) $_SESSION['created_at']) > self::REGEN_EVERY) {
            session_regenerate_id(true);
            $_SESSION['created_at'] = $now;
        }
    }

    /** Force-regenerate (call right after a successful login). */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
    }

    /** Generate a one-time-per-request nonce used by the CSP + inline scripts. */
    private static function ensureNonce(): void
    {
        if (empty($GLOBALS['__ww_nonce'])) {
            $GLOBALS['__ww_nonce'] = base64_encode(random_bytes(16));
        }
    }

    public static function nonce(): string
    {
        return $GLOBALS['__ww_nonce'] ?? '';
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}

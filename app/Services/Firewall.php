<?php
/**
 * WiseWallet 2.0 — request firewall (runs early in the bootstrap).
 *
 *  • IP deny-list (and optional admin allow-list)
 *  • Global per-IP rate limiting for the JSON API (DB token window)
 *  • WAF-lite: blocks high-confidence injection/XSS/traversal signatures
 *  • Records blocks to `security_events` for the admin logs
 */

declare(strict_types=1);

final class Firewall
{
    /** High-confidence attack signatures (kept tight to avoid false positives). */
    private const SIGNATURES = [
        '#<script\b#i',
        '#javascript:#i',
        '#\bon(error|load|mouseover)\s*=#i',
        '#\bunion\s+select\b#i',
        '#information_schema#i',
        '#\.\./\.\./#',
        '#/etc/passwd#i',
        '#<\?php#i',
        '#base64_decode\s*\(#i',
        '#document\.cookie#i',
    ];

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function isApi(): bool
    {
        return str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }

    /** Run the full chain. Any failure exits the request. */
    public static function guard(): void
    {
        self::enforceIp();
        self::throttle();
        self::scan();
    }

    /* ── IP filtering ─────────────────────────────────────────── */
    private static function enforceIp(): void
    {
        $ip = self::ip();
        $deny = self::list('IP_DENYLIST');
        if ($deny && in_array($ip, $deny, true)) {
            self::block('ip_denied', 403, 'Acesso bloqueado.', $ip);
        }
        $allow = self::list('ADMIN_IP_ALLOWLIST');
        if ($allow && str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin') && !in_array($ip, $allow, true)) {
            self::block('admin_ip_blocked', 403, 'Área de administração restrita por IP.', $ip);
        }
    }

    /* ── Rate limiting (API only) ─────────────────────────────── */
    private static function throttle(): void
    {
        if (!self::isApi()) { return; }
        $max = (int) ww_config('API_RATE_MAX', 240);
        $window = 60;
        $now = time();
        $key = 'api:' . self::ip();
        try {
            Database::run(
                "INSERT INTO rate_limits (rl_key, hits, window_start) VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE
                   hits = IF(window_start < ?, 1, hits + 1),
                   window_start = IF(window_start < ?, ?, window_start)",
                [$key, $now, $now - $window, $now - $window, $now]
            );
            $hits = (int) Database::scalar("SELECT hits FROM rate_limits WHERE rl_key=?", [$key]);
            if ($hits > $max) {
                header('Retry-After: ' . $window);
                self::block('rate_limited', 429, 'Demasiados pedidos. Tente novamente em instantes.', "hits=$hits");
            }
        } catch (Throwable $e) {
            // Never let the limiter take down the app.
            error_log('throttle error: ' . $e->getMessage());
        }
    }

    /* ── WAF-lite signature scan ──────────────────────────────── */
    private static function scan(): void
    {
        $parts = [rawurldecode($_SERVER['REQUEST_URI'] ?? '')];
        foreach (['get' => $_GET, 'post' => $_POST] as $bag) {
            foreach ($bag as $k => $v) {
                if (preg_match('/pass(word)?|token|secret/i', (string) $k)) { continue; } // don't scan secrets
                $parts[] = is_scalar($v) ? (string) $v : json_encode($v);
            }
        }
        $raw = file_get_contents('php://input');
        if ($raw) { $parts[] = $raw; }
        $hay = implode("\n", $parts);

        foreach (self::SIGNATURES as $re) {
            if (preg_match($re, $hay)) {
                self::block('waf_block', 403, 'Pedido bloqueado por motivos de segurança.', substr($re, 1, 24));
            }
        }
    }

    /** Generic sliding-window limiter usable by any flow. True if allowed. */
    public static function allow(string $key, int $max, int $window): bool
    {
        $now = time();
        try {
            Database::run(
                "INSERT INTO rate_limits (rl_key, hits, window_start) VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE
                   hits = IF(window_start < ?, 1, hits + 1),
                   window_start = IF(window_start < ?, ?, window_start)",
                [$key, $now, $now - $window, $now - $window, $now]
            );
            return (int) Database::scalar("SELECT hits FROM rate_limits WHERE rl_key=?", [$key]) <= $max;
        } catch (Throwable $e) {
            return true; // fail open
        }
    }

    /* ── helpers ──────────────────────────────────────────────── */
    private static function list(string $key): array
    {
        return array_filter(array_map('trim', explode(',', (string) ww_config($key, ''))));
    }

    private static function block(string $kind, int $code, string $msg, string $detail = ''): void
    {
        try {
            Database::run(
                "INSERT INTO security_events (kind, ip, uri, detail, user_id, created_at) VALUES (?,?,?,?,?,NOW())",
                [$kind, self::ip(), substr($_SERVER['REQUEST_URI'] ?? '', 0, 255), substr($detail, 0, 255),
                 isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null]
            );
        } catch (Throwable $e) { /* best effort */ }

        http_response_code($code);
        if (self::isApi()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $msg]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><meta charset="utf-8"><title>' . $code . '</title>'
               . '<body style="background:#0b0e1a;color:#e2e8f0;font-family:system-ui;display:grid;place-items:center;height:100vh;margin:0">'
               . '<div style="text-align:center"><h1 style="font-size:3rem;margin:0">' . $code . '</h1><p>' . htmlspecialchars($msg, ENT_QUOTES) . '</p></div>';
        }
        exit;
    }
}

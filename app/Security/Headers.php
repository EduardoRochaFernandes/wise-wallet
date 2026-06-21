<?php
/**
 * WiseWallet 2.0 — HTTP security headers.
 *
 * A strict, nonce-based Content-Security-Policy plus the standard hardening
 * headers. All external data (FX, crypto, news) is proxied server-side, so the
 * CSP can keep `connect-src 'self'` and `default-src 'self'`.
 */

declare(strict_types=1);

final class Headers
{
    public static function send(string $nonce = ''): void
    {
        if (headers_sent()) {
            return;
        }

        // Hide the server/runtime fingerprint.
        header_remove('X-Powered-By');

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            // Inline scripts must carry the per-request nonce.
            "script-src 'self' 'nonce-{$nonce}'",
            // ApexCharts injects <style> at runtime → allow inline styles only.
            "style-src 'self' 'unsafe-inline'",
            "upgrade-insecure-requests",
            "report-uri /api/csp-report.php",
        ]);

        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=(), interest-cohort=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('X-XSS-Protection: 0'); // modern browsers: rely on CSP, disable legacy auditor

        // HSTS only makes sense (and is only honoured) over HTTPS.
        if ((bool) ww_config('SESSION_SECURE', false) || self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }
}

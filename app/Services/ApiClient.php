<?php
/**
 * WiseWallet 2.0 — minimal HTTPS client (cURL) for the free, keyless APIs.
 * Verifies TLS using XAMPP's bundled CA where available; returns null on any
 * failure so callers can fall back to cache/seed data.
 */

declare(strict_types=1);

final class ApiClient
{
    public static function get(string $url, int $timeout = 6): ?string
    {
        if (!function_exists('curl_init')) {
            return @file_get_contents($url) ?: null;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT      => 'WiseWallet/2.0 (+finance app)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => ['Accept: application/json, application/xml, text/xml'],
        ]);
        $ca = self::caBundle();
        if ($ca) { curl_setopt($ch, CURLOPT_CAINFO, $ca); }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code >= 400) {
            error_log("ApiClient GET $url failed (code $code): $err");
            return null;
        }
        return (string) $body;
    }

    public static function getJson(string $url, int $timeout = 6): ?array
    {
        $raw = self::get($url, $timeout);
        if ($raw === null) { return null; }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function caBundle(): ?string
    {
        // Our project-local bundle (storage/cacert.pem) takes priority: it's
        // kept current and, on machines running an HTTPS-scanning antivirus
        // (e.g. Avast/AVG Web Shield, which transparently re-signs outbound
        // TLS with its own root cert), that root is appended to it too — see
        // scripts/update-ca-bundle.php. Stale system bundles are only a
        // fallback for environments without our bundle present.
        foreach ([
            WW_ROOT . '/storage/cacert.pem',
            ini_get('curl.cainfo'),
            'C:/xampp/apache/bin/curl-ca-bundle.crt',
            'C:/xampp/php/extras/ssl/cacert.pem',
        ] as $p) {
            if ($p && is_file($p)) { return $p; }
        }
        return null;
    }
}

<?php
/**
 * WiseWallet 2.0 — Tiny file cache for external API responses.
 * Lets us serve fresh data within TTL and fall back to the last-good copy
 * (or seed data) when an upstream API is offline — "graceful degradation".
 */

declare(strict_types=1);

final class Cache
{
    private static function path(string $key): string
    {
        return WW_DATA . '/cache_' . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.json';
    }

    /** Fresh value (within TTL) or null. */
    public static function fresh(string $key, int $ttl)
    {
        $f = self::path($key);
        if (is_file($f) && (time() - filemtime($f)) < $ttl) {
            return json_decode((string) file_get_contents($f), true);
        }
        return null;
    }

    /** Last stored value regardless of age (used as offline fallback). */
    public static function stale(string $key)
    {
        $f = self::path($key);
        return is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    }

    public static function put(string $key, $data): void
    {
        @file_put_contents(self::path($key), json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}

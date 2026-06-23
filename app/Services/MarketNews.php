<?php
/**
 * WiseWallet 2.0 — Financial news via public RSS feeds (no API key).
 * Server-side fetch + parse + cache, with graceful fallback to the last-good
 * cache or the news_cache table when feeds are offline. Every returned item is
 * normalized to a fixed shape so the view never hits an undefined key.
 */

declare(strict_types=1);

final class MarketNews
{
    /** Force every item into a known shape. */
    private static function normalize(array $items): array
    {
        $out = [];
        foreach ($items as $it) {
            $title = trim((string) ($it['title'] ?? ''));
            $url   = trim((string) ($it['url'] ?? ''));
            if ($title === '' || $url === '') { continue; }
            $out[] = [
                'title'        => $title,
                'url'          => $url,
                'summary'      => (string) ($it['summary'] ?? ''),
                'source'       => (string) ($it['source'] ?? 'News'),
                'published_at' => !empty($it['published_at']) ? (string) $it['published_at'] : null,
            ];
        }
        return $out;
    }

    public static function latest(int $max = 16): array
    {
        $ttl = (int) ww_config('API_CACHE_TTL', 900);
        $key = 'news_feed';

        $fresh = Cache::fresh($key, $ttl);
        if (is_array($fresh)) {
            return ['data' => array_slice(self::normalize($fresh), 0, $max), 'stale' => false];
        }

        $feeds = array_filter(array_map('trim', explode(',', (string) ww_config('NEWS_RSS', ''))));
        $items = [];
        foreach ($feeds as $url) {
            $xml = ApiClient::get($url, 7);
            if (!$xml) { continue; }
            $prev = libxml_use_internal_errors(true);
            $rss = simplexml_load_string($xml);
            libxml_use_internal_errors($prev);
            if (!$rss) { continue; }

            $source = 'News';
            $entries = [];
            if (isset($rss->channel)) {
                $source = (string) ($rss->channel->title ?? parse_url($url, PHP_URL_HOST));
                $entries = $rss->channel->item ?? [];
            } elseif (isset($rss->entry)) {
                $source = (string) ($rss->title ?? parse_url($url, PHP_URL_HOST));
                $entries = $rss->entry;
            }

            foreach ($entries as $item) {
                $link = (string) ($item->link['href'] ?? $item->link ?? '');
                $date = (string) ($item->pubDate ?? $item->updated ?? $item->published ?? '');
                $items[] = [
                    'title'        => trim((string) $item->title),
                    'url'          => trim($link),
                    'summary'      => mb_substr(trim(strip_tags((string) ($item->description ?? $item->summary ?? ''))), 0, 220),
                    'source'       => $source,
                    'published_at' => $date !== '' ? date('Y-m-d H:i', strtotime($date) ?: time()) : null,
                ];
                if (count($items) >= $max) { break; }
            }
            if (count($items) >= $max) { break; }
        }

        $items = self::normalize($items);

        if ($items) {
            foreach ($items as $it) {
                Database::run(
                    "INSERT IGNORE INTO news_cache (source,title,url,summary,published_at,fetched_at) VALUES (?,?,?,?,?,NOW())",
                    [$it['source'], $it['title'], $it['url'], $it['summary'], $it['published_at']]
                );
            }
            Cache::put($key, $items);
            return ['data' => $items, 'stale' => false];
        }

        $stale = Cache::stale($key);
        if (is_array($stale)) { return ['data' => array_slice(self::normalize($stale), 0, $max), 'stale' => true]; }
        $rows = Database::all("SELECT source,title,url,summary,published_at FROM news_cache ORDER BY published_at DESC LIMIT ?", [$max]);
        return ['data' => self::normalize($rows), 'stale' => true];
    }
}

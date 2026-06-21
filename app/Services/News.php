<?php
/**
 * WiseWallet 2.0 — Financial news via public RSS feeds (no API key).
 * Server-side fetch + parse + cache, with graceful fallback to the last-good
 * cache or the news_cache table when feeds are offline.
 */

declare(strict_types=1);

final class News
{
    public static function latest(int $max = 16): array
    {
        $ttl = (int) ww_config('API_CACHE_TTL', 900);
        $key = 'news_feed';

        $fresh = Cache::fresh($key, $ttl);
        if ($fresh) { return ['data' => array_slice($fresh, 0, $max), 'stale' => false]; }

        $feeds = array_filter(array_map('trim', explode(',', (string) ww_config('NEWS_RSS', ''))));
        $items = [];
        foreach ($feeds as $url) {
            $xml = ApiClient::get($url, 7);
            if (!$xml) { continue; }
            $prev = libxml_use_internal_errors(true);
            $rss = simplexml_load_string($xml);
            libxml_use_internal_errors($prev);
            if (!$rss || !isset($rss->channel)) { continue; }
            $source = (string) ($rss->channel->title ?? parse_url($url, PHP_URL_HOST));
            foreach ($rss->channel->item ?? [] as $item) {
                $items[] = [
                    'title'        => trim((string) $item->title),
                    'url'          => trim((string) $item->link),
                    'summary'      => mb_substr(trim(strip_tags((string) $item->description)), 0, 220),
                    'source'       => $source,
                    'published_at' => !empty($item->pubDate) ? date('Y-m-d H:i', strtotime((string) $item->pubDate)) : null,
                ];
                if (count($items) >= $max) { break; }
            }
            if (count($items) >= $max) { break; }
        }

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

        // Fallbacks.
        $stale = Cache::stale($key);
        if ($stale) { return ['data' => array_slice($stale, 0, $max), 'stale' => true]; }
        $rows = Database::all("SELECT source,title,url,summary,published_at FROM news_cache ORDER BY published_at DESC LIMIT ?", [$max]);
        return ['data' => $rows, 'stale' => true];
    }
}

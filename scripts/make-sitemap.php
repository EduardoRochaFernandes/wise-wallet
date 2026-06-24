<?php
/**
 * Generates public/sitemap.xml from the published articles + static pages.
 * Run by setup after the DB import:  php scripts/make-sitemap.php
 */
declare(strict_types=1);

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/Database.php';

$base = WW_URL;
$today = date('Y-m-d');

$urls = [
    ['/', '1.0', 'weekly'],
    ['/blog', '0.8', 'weekly'],
    ['/news', '0.7', 'daily'],
    ['/register', '0.6', 'monthly'],
    ['/login', '0.5', 'monthly'],
    ['/privacy', '0.3', 'yearly'],
];

try {
    foreach (Database::all("SELECT slug, updated_at FROM articles WHERE status='published'") as $a) {
        $urls[] = ['/article?slug=' . $a['slug'], '0.7', 'monthly', date('Y-m-d', strtotime($a['updated_at']))];
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Warning: database unavailable, sitemap will only include static pages.\n");
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    $loc = htmlspecialchars($base . $u[0], ENT_QUOTES);
    $lastmod = $u[3] ?? $today;
    $xml .= "  <url>\n    <loc>$loc</loc>\n    <lastmod>$lastmod</lastmod>\n"
          . "    <changefreq>{$u[2]}</changefreq>\n    <priority>{$u[1]}</priority>\n  </url>\n";
}
$xml .= "</urlset>\n";

file_put_contents(WW_PUBLIC . '/sitemap.xml', $xml);
echo "sitemap.xml generated with " . count($urls) . " URLs.\n";

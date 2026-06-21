<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();

$base = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($_GET['base'] ?? 'EUR')) ?: 'EUR');
$symbols = 'USD,GBP,BRL,CHF,JPY,CAD,AUD,CNY';
$ttl = (int) ww_config('API_CACHE_TTL', 900);
$api = rtrim((string) ww_config('FX_API', 'https://api.frankfurter.app'), '/');
$key = 'fx_' . $base;

$fresh = Cache::fresh($key, $ttl);
if ($fresh) { json_out($fresh + ['cached' => true]); }

$data = ApiClient::getJson("$api/latest?base=$base&symbols=$symbols");
if ($data && !empty($data['rates'])) {
    $out = ['base' => $base, 'date' => $data['date'] ?? date('Y-m-d'), 'rates' => $data['rates'], 'source' => 'Frankfurter (BCE)', 'stale' => false];
    Cache::put($key, $out);
    json_out($out);
}

// Graceful fallback — last good copy, else static reference rates.
$stale = Cache::stale($key);
if ($stale) { json_out($stale + ['stale' => true]); }
json_out([
    'base' => 'EUR', 'date' => date('Y-m-d'), 'source' => 'Fallback estático', 'stale' => true,
    'rates' => ['USD' => 1.08, 'GBP' => 0.85, 'BRL' => 5.45, 'CHF' => 0.97, 'JPY' => 169.5, 'CAD' => 1.47, 'AUD' => 1.63, 'CNY' => 7.82],
]);

<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();

$ttl = (int) ww_config('API_CACHE_TTL', 900);
$api = rtrim((string) ww_config('CRYPTO_API', 'https://api.coingecko.com/api/v3'), '/');
$ids = 'bitcoin,ethereum,solana,cardano,ripple';
$key = 'crypto_eur';

$fresh = Cache::fresh($key, $ttl);
if ($fresh) { json_out($fresh + ['cached' => true]); }

$data = ApiClient::getJson("$api/simple/price?ids=$ids&vs_currencies=eur&include_24hr_change=true");
if ($data) {
    $names = ['bitcoin' => 'Bitcoin', 'ethereum' => 'Ethereum', 'solana' => 'Solana', 'cardano' => 'Cardano', 'ripple' => 'XRP'];
    $coins = [];
    foreach ($data as $id => $row) {
        $coins[] = ['id' => $id, 'name' => $names[$id] ?? ucfirst($id), 'price' => $row['eur'] ?? null, 'change' => round((float) ($row['eur_24h_change'] ?? 0), 2)];
    }
    $out = ['coins' => $coins, 'source' => 'CoinGecko', 'stale' => false];
    Cache::put($key, $out);
    json_out($out);
}

$stale = Cache::stale($key);
if ($stale) { json_out($stale + ['stale' => true]); }
json_out([
    'source' => 'Fallback estático', 'stale' => true,
    'coins' => [
        ['id' => 'bitcoin', 'name' => 'Bitcoin', 'price' => 42500, 'change' => 1.2],
        ['id' => 'ethereum', 'name' => 'Ethereum', 'price' => 2350, 'change' => -0.6],
        ['id' => 'solana', 'name' => 'Solana', 'price' => 138, 'change' => 2.4],
        ['id' => 'cardano', 'name' => 'Cardano', 'price' => 0.42, 'change' => 0.3],
    ],
]);

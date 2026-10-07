<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();
$types = ['stock', 'etf', 'crypto', 'bond', 'real_estate', 'retirement'];

switch ($method) {
    case 'GET':
        json_out(Finance::investments($uid));

    case 'POST':
        $v = new Validator($body);
        $v->required('name', 'Nome')->max('name', 150, 'Nome');
        $v->required('type', 'Tipo')->in('type', $types, 'Tipo');
        $v->required('quantity', 'Quantidade')->numeric('quantity', 'Quantidade');
        $v->required('buy_price', 'Purchase price')->numeric('buy_price', 'Purchase price');
        if ($v->fails()) { json_out(['error' => $v->firstError()], 422); }
        $id = Database::insert(
            "INSERT INTO investments (user_id,name,symbol,type,quantity,buy_price,current_price,currency,purchased_on,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,NOW())",
            [$uid, $v->get('name'), $body['symbol'] ?? null, $body['type'],
             (float) $body['quantity'], (float) $body['buy_price'],
             ($body['current_price'] ?? '') !== '' ? (float) $body['current_price'] : (float) $body['buy_price'],
             'EUR', !empty($body['purchased_on']) ? $body['purchased_on'] : null]
        );
        Achievements::evaluate($uid);
        json_out(['ok' => true, 'id' => $id]);

    case 'PATCH':
    case 'PUT':
        $id = (int) ($body['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM investments WHERE id=?", [$id]));
        Database::run("UPDATE investments SET current_price=? WHERE id=? AND user_id=?", [(float) ($body['current_price'] ?? 0), $id, $uid]);
        json_out(['ok' => true]);

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM investments WHERE id=?", [$id]));
        Database::run("DELETE FROM investments WHERE id=? AND user_id=?", [$id, $uid]);
        json_out(['ok' => true]);
}
json_out(['error' => 'Method not supported'], 405);

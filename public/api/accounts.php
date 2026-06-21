<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();
$types = ['checking', 'savings', 'credit', 'cash', 'crypto', 'investment'];

switch ($method) {
    case 'GET':
        json_out(['data' => Finance::accounts($uid, !empty($_GET['all']))]);

    case 'POST':
        $v = new Validator($body);
        $v->required('name', 'Nome')->max('name', 120, 'Nome');
        $v->required('type', 'Tipo')->in('type', $types, 'Tipo');
        if ($v->fails()) { json_out(['error' => $v->firstError()], 422); }
        $id = Database::insert(
            "INSERT INTO accounts (user_id,name,type,balance,currency,color,created_at) VALUES (?,?,?,?,?,?,NOW())",
            [$uid, $v->get('name'), $body['type'], round((float) ($body['balance'] ?? 0), 2), 'EUR', $body['color'] ?? null]
        );
        Achievements::evaluate($uid);
        json_out(['ok' => true, 'id' => $id]);

    case 'PUT':
    case 'PATCH':
        $id = (int) ($body['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM accounts WHERE id=?", [$id]));
        Database::run("UPDATE accounts SET name=?, type=?, color=?, is_archived=? WHERE id=? AND user_id=?",
            [mb_substr((string) ($body['name'] ?? ''), 0, 120), in_array($body['type'] ?? '', $types, true) ? $body['type'] : 'checking',
             $body['color'] ?? null, !empty($body['is_archived']) ? 1 : 0, $id, $uid]);
        json_out(['ok' => true]);

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM accounts WHERE id=?", [$id]));
        Database::run("DELETE FROM accounts WHERE id=? AND user_id=?", [$id, $uid]);
        json_out(['ok' => true]);
}
json_out(['error' => 'Método não suportado'], 405);

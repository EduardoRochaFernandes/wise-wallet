<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();

switch ($method) {
    case 'GET':
        json_out(Finance::subscriptions($uid));

    case 'POST':
        $v = new Validator($body);
        $v->required('name', 'Nome')->max('name', 150, 'Nome');
        $v->required('amount', 'Valor')->numeric('amount', 'Valor');
        if ($v->fails()) { json_out(['error' => $v->firstError()], 422); }
        $id = Database::insert(
            "INSERT INTO subscriptions (user_id,name,amount,billing_cycle,next_renewal,category_id,is_active,created_at)
             VALUES (?,?,?,?,?,?,1,NOW())",
            [$uid, $v->get('name'), round((float) $body['amount'], 2),
             ($body['billing_cycle'] ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly',
             !empty($body['next_renewal']) ? $body['next_renewal'] : null,
             !empty($body['category_id']) ? (int) $body['category_id'] : null]
        );
        Achievements::evaluate($uid);
        json_out(['ok' => true, 'id' => $id]);

    case 'PATCH':
    case 'PUT':
        $id = (int) ($body['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM subscriptions WHERE id=?", [$id]));
        Database::run("UPDATE subscriptions SET is_active = ? WHERE id=? AND user_id=?", [!empty($body['is_active']) ? 1 : 0, $id, $uid]);
        json_out(['ok' => true]);

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM subscriptions WHERE id=?", [$id]));
        Database::run("DELETE FROM subscriptions WHERE id=? AND user_id=?", [$id, $uid]);
        json_out(['ok' => true]);
}
json_out(['error' => 'Método não suportado'], 405);

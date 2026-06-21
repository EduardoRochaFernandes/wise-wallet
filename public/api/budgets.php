<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();

switch ($method) {
    case 'GET':
        json_out(['data' => Finance::budgets($uid)]);

    case 'POST':
        $v = new Validator($body);
        $v->required('category_id', 'Categoria');
        $v->required('amount', 'Valor')->numeric('amount', 'Valor');
        if ($v->fails()) { json_out(['error' => $v->firstError()], 422); }
        $id = Database::insert(
            "INSERT INTO budgets (user_id,category_id,amount,period,start_date,created_at) VALUES (?,?,?,?,?,NOW())",
            [$uid, (int) $body['category_id'], round((float) $body['amount'], 2),
             in_array($body['period'] ?? 'monthly', ['weekly', 'monthly', 'yearly'], true) ? $body['period'] : 'monthly',
             date('Y-m-01')]
        );
        Achievements::evaluate($uid);
        json_out(['ok' => true, 'id' => $id]);

    case 'PATCH':
    case 'PUT':
        $id = (int) ($body['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM budgets WHERE id=?", [$id]));
        Database::run("UPDATE budgets SET amount=? WHERE id=? AND user_id=?", [round((float) ($body['amount'] ?? 0), 2), $id, $uid]);
        json_out(['ok' => true]);

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM budgets WHERE id=?", [$id]));
        Database::run("DELETE FROM budgets WHERE id=? AND user_id=?", [$id, $uid]);
        json_out(['ok' => true]);
}
json_out(['error' => 'Método não suportado'], 405);

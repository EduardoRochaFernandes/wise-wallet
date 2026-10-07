<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();

switch ($method) {
    case 'GET':
        json_out(['data' => Finance::bills($uid)]);

    case 'POST':
        $v = new Validator($body);
        $v->required('name', 'Nome')->max('name', 150, 'Nome');
        $v->required('amount', 'Valor')->numeric('amount', 'Valor');
        $v->required('due_date', 'Vencimento')->date('due_date', 'Vencimento');
        if ($v->fails()) { json_out(['error' => $v->firstError()], 422); }
        $id = Database::insert(
            "INSERT INTO bills (user_id,name,amount,due_date,recurrence,category_id,account_id,status,created_at)
             VALUES (?,?,?,?,?,?,?, 'pending', NOW())",
            [$uid, $v->get('name'), round((float) $body['amount'], 2), $body['due_date'],
             in_array($body['recurrence'] ?? 'monthly', ['once', 'weekly', 'monthly', 'quarterly', 'yearly'], true) ? $body['recurrence'] : 'monthly',
             !empty($body['category_id']) ? (int) $body['category_id'] : null,
             !empty($body['account_id']) ? (int) $body['account_id'] : null]
        );
        json_out(['ok' => true, 'id' => $id]);

    case 'PATCH':
    case 'PUT':
        // Mark paid — optionally generate the matching expense transaction.
        $id = (int) ($body['id'] ?? 0);
        $bill = Database::one("SELECT * FROM bills WHERE id=?", [$id]);
        Auth::ownOr404($bill['user_id'] ?? null);
        Database::run("UPDATE bills SET status='paid', paid_at=NOW() WHERE id=? AND user_id=?", [$id, $uid]);
        if (!empty($body['create_expense']) && $bill['account_id']) {
            Finance::createTransaction($uid, [
                'type' => 'expense', 'amount' => $bill['amount'], 'account_id' => $bill['account_id'],
                'category_id' => $bill['category_id'], 'description' => 'Pagamento: ' . $bill['name'], 'occurred_on' => date('Y-m-d'),
            ]);
        }
        Achievements::evaluate($uid);
        json_out(['ok' => true]);

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM bills WHERE id=?", [$id]));
        Database::run("DELETE FROM bills WHERE id=? AND user_id=?", [$id, $uid]);
        json_out(['ok' => true]);
}
json_out(['error' => 'Method not supported'], 405);

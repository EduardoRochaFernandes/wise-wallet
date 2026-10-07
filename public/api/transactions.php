<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();

switch ($method) {
    case 'GET':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = min(100, max(5, (int) ($_GET['per'] ?? 25)));
        $filters = array_intersect_key($_GET, array_flip(['type', 'account_id', 'category_id', 'from', 'to', 'q']));
        json_out([
            'data'  => Finance::transactions($uid, $filters, $per, ($page - 1) * $per),
            'total' => Finance::transactionsCount($uid, $filters),
            'page'  => $page, 'per' => $per,
        ]);

    case 'POST':
        $v = new Validator($body);
        $v->required('amount', 'Valor')->numeric('amount', 'Valor');
        $v->required('type', 'Tipo')->in('type', ['income', 'expense', 'transfer'], 'Tipo');
        $v->required('account_id', 'Conta');
        $v->date('occurred_on', 'Data');
        if ((float) ($body['amount'] ?? 0) <= 0) { json_out(['error' => 'O valor tem de ser positivo.'], 422); }
        if ($v->fails()) { json_out(['error' => $v->firstError()], 422); }
        try {
            $id = Finance::createTransaction($uid, $body);
            json_out(['ok' => true, 'id' => $id]);
        } catch (Throwable $e) {
            json_out(['error' => WW_DEBUG ? $e->getMessage() : 'Could not save the transaction.'], 422);
        }

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        json_out(['ok' => Finance::deleteTransaction($uid, $id)]);
}
json_out(['error' => 'Method not supported'], 405);

<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();
$types = ['checking', 'savings', 'credit', 'cash', 'crypto', 'investment'];

switch ($method) {
    case 'GET':
        if (!empty($_GET['trashed'])) {
            json_out(['data' => Finance::trashedAccounts($uid)]);
        }
        json_out(['data' => Finance::accounts($uid, !empty($_GET['all']))]);

    case 'POST':
        $v = new Validator($body);
        $v->required('name', 'Name')->max('name', 120, 'Name');
        $v->required('type', 'Type')->in('type', $types, 'Type');
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
        if (($body['action'] ?? '') === 'restore') {
            $ok = Finance::restoreAccount($uid, $id);
            if ($ok) { Audit::log('account_restore', $uid, ['account_id' => $id]); }
            json_out(['ok' => $ok]);
        }
        Auth::ownOr404(Database::scalar("SELECT user_id FROM accounts WHERE id=?", [$id]));
        Database::run("UPDATE accounts SET name=?, type=?, color=?, is_archived=? WHERE id=? AND user_id=?",
            [mb_substr((string) ($body['name'] ?? ''), 0, 120), in_array($body['type'] ?? '', $types, true) ? $body['type'] : 'checking',
             $body['color'] ?? null, !empty($body['is_archived']) ? 1 : 0, $id, $uid]);
        json_out(['ok' => true]);

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM accounts WHERE id=?", [$id]));
        $ok = Finance::trashAccount($uid, $id);
        if ($ok) { Audit::log('account_delete', $uid, ['account_id' => $id]); }
        json_out(['ok' => $ok, 'recoverable_days' => 30]);
}
json_out(['error' => 'Method not allowed'], 405);

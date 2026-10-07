<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();

switch ($method) {
    case 'GET':
        json_out(['data' => Finance::goals($uid)]);

    case 'POST':
        $v = new Validator($body);
        $v->required('name', 'Nome')->max('name', 150, 'Nome');
        $v->required('target_amount', 'Objetivo')->numeric('target_amount', 'Objetivo');
        $v->date('deadline', 'Prazo');
        if ($v->fails()) { json_out(['error' => $v->firstError()], 422); }
        $id = Database::insert(
            "INSERT INTO goals (user_id,name,target_amount,current_amount,deadline,color,icon,notes,created_at)
             VALUES (?,?,?,?,?,?,?,?,NOW())",
            [$uid, $v->get('name'), round((float) $body['target_amount'], 2), round((float) ($body['current_amount'] ?? 0), 2),
             !empty($body['deadline']) ? $body['deadline'] : null, $body['color'] ?? '#1f5a3f', $body['icon'] ?? 'target',
             ($body['notes'] ?? '') !== '' ? $body['notes'] : null]
        );
        Achievements::evaluate($uid);
        json_out(['ok' => true, 'id' => $id]);

    case 'PATCH':
    case 'PUT':
        // Contribute to a goal.
        $id = (int) ($body['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM goals WHERE id=?", [$id]));
        $amount = round((float) ($body['amount'] ?? 0), 2);
        if ($amount <= 0) { json_out(['error' => 'Invalid amount.'], 422); }
        Database::begin();
        try {
            Database::run("INSERT INTO goal_contributions (goal_id,user_id,amount,note,contributed_on,created_at) VALUES (?,?,?,?,CURDATE(),NOW())",
                [$id, $uid, $amount, ($body['note'] ?? '') !== '' ? $body['note'] : null]);
            Database::run("UPDATE goals SET current_amount = current_amount + ? WHERE id=? AND user_id=?", [$amount, $id, $uid]);
            Database::run("UPDATE goals SET status='completed' WHERE id=? AND current_amount>=target_amount AND status<>'completed'", [$id]);
            Database::commit();
        } catch (Throwable $e) { Database::rollback(); json_out(['error' => 'Could not add the contribution.'], 422); }
        Achievements::evaluate($uid);
        json_out(['ok' => true]);

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        Auth::ownOr404(Database::scalar("SELECT user_id FROM goals WHERE id=?", [$id]));
        Database::run("DELETE FROM goals WHERE id=? AND user_id=?", [$id, $uid]);
        json_out(['ok' => true]);
}
json_out(['error' => 'Method not supported'], 405);

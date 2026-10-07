<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();

switch ($method) {
    case 'GET':
        json_out(['data' => Finance::categories($uid, $_GET['type'] ?? null)]);

    case 'POST':
        $v = new Validator($body);
        $v->required('name', 'Nome')->max('name', 80, 'Nome');
        $v->required('type', 'Tipo')->in('type', ['income', 'expense', 'transfer'], 'Tipo');
        if ($v->fails()) { json_out(['error' => $v->firstError()], 422); }
        $id = Database::insert(
            "INSERT INTO categories (user_id,name,type,icon,color,is_system,created_at) VALUES (?,?,?,?,?,0,NOW())",
            [$uid, $v->get('name'), $body['type'], $body['icon'] ?? 'tag', $body['color'] ?? '#64748b']
        );
        json_out(['ok' => true, 'id' => $id]);

    case 'DELETE':
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        // Only user-owned (non-system) categories may be deleted here.
        $owner = Database::scalar("SELECT user_id FROM categories WHERE id=? AND is_system=0", [$id]);
        Auth::ownOr404($owner);
        Database::run("DELETE FROM categories WHERE id=? AND user_id=?", [$id, $uid]);
        json_out(['ok' => true]);
}
json_out(['error' => 'Method not supported'], 405);

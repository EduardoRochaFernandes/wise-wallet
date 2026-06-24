<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$method = $_SERVER['REQUEST_METHOD'];
$body = json_body();

switch ($method) {
    case 'GET':
        json_out(['data' => Database::all("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 30", [$uid])]);

    case 'PATCH':
    case 'POST':
        if (!empty($body['id'])) {
            Database::run("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", [(int) $body['id'], $uid]);
        } else {
            Database::run("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);
        }
        json_out(['ok' => true]);
}
json_out(['error' => 'Método não suportado'], 405);

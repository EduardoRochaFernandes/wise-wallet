<?php
/**
 * CSP violation collector. Browsers POST reports here (cross-origin, no CSRF
 * token), so this endpoint deliberately bypasses the full bootstrap/firewall
 * and only logs. Never returns content.
 */
declare(strict_types=1);

require __DIR__ . '/../../app/config.php';
require __DIR__ . '/../../app/Database.php';

http_response_code(204);

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') { exit; }
$data = json_decode($raw, true);
$report = $data['csp-report'] ?? $data;
$detail = is_array($report)
    ? (($report['violated-directive'] ?? '?') . ' ← ' . ($report['blocked-uri'] ?? '?'))
    : 'malformed';

try {
    Database::run(
        "INSERT INTO security_events (kind, ip, uri, detail, created_at) VALUES ('csp_violation', ?, ?, ?, NOW())",
        [$_SERVER['REMOTE_ADDR'] ?? null,
         substr((string) ($report['document-uri'] ?? ''), 0, 255),
         substr($detail, 0, 255)]
    );
} catch (Throwable $e) {
    error_log('CSP report log failed: ' . $e->getMessage());
}
exit;

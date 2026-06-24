<?php
/**
 * WiseWallet 2.0 — CSRF protection (synchronizer-token pattern).
 *
 * A single per-session token is issued and embedded in every state-changing
 * form / fetch request. Validation is constant-time (hash_equals).
 */

declare(strict_types=1);

final class Csrf
{
    private const KEY = '_csrf_token';

    /** Return the current token, generating one on first use. */
    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    /** Hidden <input> for HTML forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    /** Constant-time comparison against the session token. */
    public static function validate(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }

    /**
     * Guard a mutating request. Accepts the token from the form field or the
     * `X-CSRF-Token` header (used by fetch/JSON calls). Aborts with 403 on fail.
     */
    public static function check(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }
        $token = $_POST['csrf_token']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        if (!self::validate($token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Invalid or missing CSRF token.']);
            exit;
        }
    }
}

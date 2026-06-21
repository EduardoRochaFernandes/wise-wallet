<?php
/**
 * WiseWallet 2.0 — Global view/controller helpers.
 * Small, dependency-free functions used across pages and API endpoints.
 */

declare(strict_types=1);

/** Escape for safe HTML output (XSS defence). Always use in templates. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format a numeric amount as EUR currency. */
function money($amount, string $symbol = '€'): string
{
    return number_format((float) $amount, 2, ',', ' ') . ' ' . $symbol;
}

/** Render the CSP nonce attribute for an inline <script>. */
function nonce_attr(): string
{
    return 'nonce="' . e(Session::nonce()) . '"';
}

/** HTTP redirect then stop. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Emit a JSON response with the right header and stop. */
function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Read & trim a request value (POST then GET). */
function input(string $key, $default = null)
{
    $val = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($val) ? trim($val) : $val;
}

/** Decode a JSON request body (for fetch() API calls). */
function json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** One-shot flash message helpers. */
function flash(string $key, ?string $msg = null)
{
    if ($msg !== null) {
        $_SESSION['flash_' . $key] = $msg;
        return null;
    }
    $val = $_SESSION['flash_' . $key] ?? null;
    unset($_SESSION['flash_' . $key]);
    return $val;
}

/** Old form input repopulation after a validation error. */
function old(string $key, $default = '')
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function set_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['csrf_token']);
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

/** Active-nav helper for the sidebar. */
function nav_active(string $page, string $current): string
{
    return $page === $current ? ' is-active' : '';
}

/** Versioned asset URL (cache-busting via file mtime). */
function asset(string $path): string
{
    $full = WW_PUBLIC . $path;
    $v = is_file($full) ? (string) filemtime($full) : '2';
    return $path . '?v=' . $v;
}

/**
 * Reusable empty-state block with a call-to-action.
 * Used instead of empty/placeholder charts & lists when a user has no data yet.
 */
function empty_state(string $title, string $text, string $ctaLabel, string $ctaHref, string $iconName = 'sparkles'): string
{
    return '<div class="text-center py-10 px-4">'
        . '<div class="w-14 h-14 mx-auto rounded-2xl grid place-items-center mb-4 text-brand-400" style="background:rgb(var(--brand) / .12)">' . icon($iconName, 'w-7 h-7') . '</div>'
        . '<h3 class="font-bold text-lg">' . e($title) . '</h3>'
        . '<p class="text-soft text-sm mt-1 max-w-sm mx-auto">' . e($text) . '</p>'
        . '<a href="' . e($ctaHref) . '" class="btn-primary mt-4 inline-flex">' . e($ctaLabel) . ' ' . icon('chevron-right', 'w-4 h-4') . '</a>'
        . '</div>';
}

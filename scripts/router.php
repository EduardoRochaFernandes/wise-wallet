<?php
/**
 * Router for PHP's built-in web server, which does not read public/.htaccess.
 * It reproduces the clean-URL rules from that file:  /login  ->  public/login.php
 *
 * Usage:  php -S localhost:8000 -t public scripts/router.php
 * (Docker/Apache and XAMPP use .htaccess instead and do not need this file.)
 */
declare(strict_types=1);

$public = dirname(__DIR__) . '/public';
$path   = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Never serve dotfiles (.env, .git, ...) -- mirrors the FilesMatch rule in .htaccess,
// except /.well-known/ which is intentionally public.
if (preg_match('#/\.(?!well-known/)#', $path)) {
    http_response_code(403);
    exit;
}

$file = realpath($public . $path);
if ($file !== false && str_starts_with($file, $public)) {
    if (is_file($file)) {
        return false;                       // static asset or an existing .php file: serve as-is
    }
    if (is_file($file . '/index.php')) {    // directory -> index.php (e.g. /admin/)
        $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '/index.php';
        require $file . '/index.php';
        return true;
    }
}

if ($path !== '/' && is_file($public . rtrim($path, '/') . '.php')) {   // /login -> login.php
    $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '.php';
    require $public . rtrim($path, '/') . '.php';
    return true;
}

return false;                               // fall through to the default behaviour (index.php / 404)

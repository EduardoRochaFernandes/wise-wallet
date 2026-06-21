<?php
/**
 * WiseWallet 2.0 — Authentication & access control.
 *
 *  • Argon2id password hashing (with automatic rehash-on-login upgrade)
 *  • Login throttling via RateLimit
 *  • Role-based access (user / admin)
 *  • IDOR guard (ownership verification) used by every resource endpoint
 */

declare(strict_types=1);

final class Auth
{
    private const ARGON_OPTS = [
        'memory_cost' => 65536, // 64 MB
        'time_cost'   => 4,
        'threads'     => 2,
    ];

    private static ?array $cached = null;

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, self::ARGON_OPTS);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /** Current user row (cached for the request), or null. */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        if (self::$cached !== null) {
            return self::$cached;
        }
        self::$cached = Database::one(
            "SELECT id, name, email, role, currency, theme, privacy_mode, points, is_active, created_at
               FROM users WHERE id = ? LIMIT 1",
            [self::id()]
        );
        return self::$cached;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === 'admin';
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            if (self::wantsJson()) {
                json_out(['error' => 'Não autenticado.'], 401);
            }
            flash('error', 'Inicie sessão para continuar.');
            redirect('/login.php');
        }
        // Account could have been deactivated mid-session.
        $u = self::user();
        if (!$u || (int) $u['is_active'] !== 1) {
            self::logout();
            redirect('/login.php');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('403 — Acesso negado.');
        }
    }

    /**
     * Attempt a login. Returns [success, message].
     */
    public static function attempt(string $email, string $password): array
    {
        $email = strtolower(trim($email));

        // Hard lockout window (set after repeated failures).
        $locked = Database::scalar("SELECT locked_until FROM users WHERE email=? AND locked_until > NOW()", [$email]);
        if ($locked) {
            Audit::log('login_locked', null, ['email' => $email]);
            return [false, 'Conta temporariamente bloqueada por segurança. Tente novamente mais tarde.'];
        }

        $wait = RateLimit::lockedFor($email);
        if ($wait > 0) {
            Audit::log('login_blocked', null, ['email' => $email]);
            $mins = (int) ceil($wait / 60);
            return [false, "Demasiadas tentativas. Tente novamente em {$mins} minuto(s)."];
        }

        $user = Database::one(
            "SELECT id, name, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1",
            [$email]
        );

        // Always run a hash verify (even on unknown user) to avoid timing leaks.
        $hash = $user['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=2$ZHVtbXlzYWx0ZHVtbXk$0000000000000000000000000000000000000000000';
        $ok = password_verify($password, $hash);

        if (!$user || !$ok) {
            RateLimit::record($email, false);
            if ($user) {
                $fails = (int) Database::scalar(
                    "SELECT COUNT(*) FROM login_attempts WHERE identifier=? AND success=0 AND attempted_at > (NOW() - INTERVAL 15 MINUTE)",
                    [$email]);
                if ($fails >= 10) {
                    Database::run("UPDATE users SET locked_until = NOW() + INTERVAL 15 MINUTE WHERE id=?", [$user['id']]);
                }
            }
            Audit::log('login_failed', $user['id'] ?? null, ['email' => $email]);
            return [false, 'Credenciais inválidas.'];
        }

        if ((int) $user['is_active'] !== 1) {
            return [false, 'Conta desativada. Contacte o suporte.'];
        }

        // Upgrade the hash if parameters changed.
        if (password_needs_rehash($hash, PASSWORD_ARGON2ID, self::ARGON_OPTS)) {
            Database::run("UPDATE users SET password_hash = ? WHERE id = ?",
                [self::hash($password), $user['id']]);
        }

        RateLimit::record($email, true);
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['fp'] = self::fp();

        // Login alert when the IP has never been seen for this account.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $seen = Database::scalar("SELECT id FROM audit_log WHERE user_id=? AND action='login_success' AND ip=? LIMIT 1", [$user['id'], $ip]);
        if (!$seen) {
            Database::run(
                "INSERT INTO notifications (user_id,type,title,body,icon,created_at) VALUES (?,?,?,?,?,NOW())",
                [$user['id'], 'security', 'Novo início de sessão', 'Sessão iniciada a partir de um novo dispositivo/IP (' . $ip . ').', 'shield']);
        }
        Database::run("UPDATE users SET last_login_at = NOW(), locked_until = NULL WHERE id = ?", [$user['id']]);
        Audit::log('login_success', (int) $user['id']);

        return [true, 'Sessão iniciada.'];
    }

    /**
     * Register a new user. Returns [success, messageOrUserId].
     */
    public static function register(string $name, string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $exists = Database::scalar("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
        if ($exists) {
            return [false, 'Já existe uma conta com este email.'];
        }

        $userId = Database::insert(
            "INSERT INTO users (name, email, password_hash, role, currency, theme, is_active, created_at)
             VALUES (?, ?, ?, 'user', 'EUR', 'dark', 1, NOW())",
            [$name, $email, self::hash($password)]
        );

        self::seedDefaults($userId);
        Audit::log('register', $userId, ['email' => $email]);
        return [true, $userId];
    }

    /** Give a brand-new user sensible starting accounts. */
    private static function seedDefaults(int $userId): void
    {
        Database::run(
            "INSERT INTO accounts (user_id, name, type, balance, currency, created_at)
             VALUES (?, 'Conta à Ordem', 'checking', 0, 'EUR', NOW()),
                    (?, 'Poupança', 'savings', 0, 'EUR', NOW()),
                    (?, 'Dinheiro', 'cash', 0, 'EUR', NOW())",
            [$userId, $userId, $userId]
        );
    }

    public static function logout(): void
    {
        $id = self::id();
        if ($id) {
            Audit::log('logout', $id);
        }
        self::$cached = null;
        Session::destroy();
    }

    /**
     * IDOR guard: abort unless $ownerId matches the logged-in user
     * (admins bypass for moderation). Use on EVERY resource fetch.
     */
    public static function ownOr404($ownerId): void
    {
        if ((int) $ownerId === self::id() || self::isAdmin()) {
            return;
        }
        http_response_code(404);
        if (self::wantsJson()) {
            json_out(['error' => 'Recurso não encontrado.'], 404);
        }
        exit('404 — Não encontrado.');
    }

    /** Session fingerprint = hash(user-agent + app key). */
    private static function fp(): string
    {
        return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ww_config('APP_KEY', 'ww'));
    }

    /** Invalidate the session if the device fingerprint changes (token theft defence). */
    public static function enforceFingerprint(): void
    {
        if (!isset($_SESSION['user_id'])) { return; }
        $current = self::fp();
        if (!isset($_SESSION['fp'])) { $_SESSION['fp'] = $current; return; }
        if (!hash_equals($_SESSION['fp'], $current)) {
            Audit::log('session_fp_mismatch', (int) $_SESSION['user_id']);
            self::logout();
            if (self::wantsJson()) { json_out(['error' => 'Sessão inválida.'], 401); }
            redirect('/login.php');
        }
    }

    private static function wantsJson(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($uri, '/api/') || str_contains($accept, 'application/json');
    }
}

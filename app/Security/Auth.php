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
            "SELECT id, name, email, role, currency, theme, privacy_mode, email_notifications, points, is_active, created_at
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
                json_out(['error' => 'Not authenticated.'], 401);
            }
            flash('error', 'Please sign in to continue.');
            redirect('/login');
        }
        // Account could have been deactivated mid-session.
        $u = self::user();
        if (!$u || (int) $u['is_active'] !== 1) {
            self::logout();
            redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('403 — Access denied.');
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
            return [false, 'Account temporarily locked for security. Please try again later.'];
        }

        $wait = RateLimit::lockedFor($email);
        if ($wait > 0) {
            Audit::log('login_blocked', null, ['email' => $email]);
            $mins = (int) ceil($wait / 60);
            return [false, "Too many attempts. Try again in {$mins} minute(s)."];
        }

        $user = Database::one(
            "SELECT id, name, password_hash, role, is_active, totp_enabled, totp_secret FROM users WHERE email = ? LIMIT 1",
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
            return [false, 'Invalid credentials.'];
        }

        if ((int) $user['is_active'] !== 1) {
            return [false, 'Account disabled. Please contact support.'];
        }

        // Upgrade the hash if parameters changed.
        if (password_needs_rehash($hash, PASSWORD_ARGON2ID, self::ARGON_OPTS)) {
            Database::run("UPDATE users SET password_hash = ? WHERE id = ?",
                [self::hash($password), $user['id']]);
        }

        RateLimit::record($email, true);

        // Second factor required → defer full login until the TOTP code is verified.
        if ((int) ($user['totp_enabled'] ?? 0) === 1) {
            $_SESSION['pending_2fa'] = (int) $user['id'];
            return [false, '__2FA__'];
        }
        return self::completeLogin($user);
    }

    /** Finalize a session (shared by password-only and 2FA logins). */
    private static function completeLogin(array $user): array
    {
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['fp'] = self::fp();
        unset($_SESSION['pending_2fa']);

        // Login alert when the IP has never been seen for this account.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $seen = Database::scalar("SELECT id FROM audit_log WHERE user_id=? AND action='login_success' AND ip=? LIMIT 1", [$user['id'], $ip]);
        if (!$seen) {
            Notifier::send((int) $user['id'], 'security', 'New sign-in detected', 'Your account was signed into from a new device or IP address (' . $ip . '). If this was not you, change your password immediately.', 'shield');
        }
        Database::run("UPDATE users SET last_login_at = NOW(), locked_until = NULL WHERE id = ?", [$user['id']]);
        Audit::log('login_success', (int) $user['id']);
        return [true, 'Signed in.'];
    }

    /** Verify the TOTP code for a pending 2FA login. */
    public static function verify2fa(string $code): array
    {
        $uid = $_SESSION['pending_2fa'] ?? null;
        if (!$uid) { return [false, 'Your verification session expired. Please sign in again.']; }
        $user = Database::one("SELECT id, name, role, is_active, totp_secret FROM users WHERE id = ?", [$uid]);
        if (!$user || (int) $user['is_active'] !== 1 || !Totp::verify((string) $user['totp_secret'], $code)) {
            Audit::log('2fa_failed', (int) $uid);
            return [false, 'Invalid verification code.'];
        }
        return self::completeLogin($user);
    }

    /**
     * Register a new user. Returns [success, messageOrUserId].
     */
    public static function register(string $name, string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $exists = Database::scalar("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
        if ($exists) {
            return [false, 'An account with this email already exists.'];
        }
        if (Pwned::isCompromised($password)) {
            return [false, 'That password appeared in known data breaches. Please choose a stronger one.'];
        }

        $userId = Database::insert(
            "INSERT INTO users (name, email, password_hash, role, currency, theme, is_active, created_at)
             VALUES (?, ?, ?, 'user', 'EUR', 'light', 1, NOW())",
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
             VALUES (?, 'Checking', 'checking', 0, 'EUR', NOW()),
                    (?, 'Savings', 'savings', 0, 'EUR', NOW()),
                    (?, 'Cash', 'cash', 0, 'EUR', NOW())",
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
            json_out(['error' => 'Resource not found.'], 404);
        }
        exit('404 — Not found.');
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
            if (self::wantsJson()) { json_out(['error' => 'Invalid session.'], 401); }
            redirect('/login');
        }
    }

    private static function wantsJson(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($uri, '/api/') || str_contains($accept, 'application/json');
    }
}

# Security Policy

WiseWallet is a **portfolio / learning project**: a personal-finance app built to practise secure
web development. It has not been independently audited or pentested and has never handled real
financial data. The controls below are real and were checked against the source; the
[known limitations](#known-limitations) are listed just as plainly.

## Reporting a vulnerability

Please **do not open a public issue**. Use GitHub's private reporting instead:
**<https://github.com/EduardoRochaFernandes/wise-wallet/security/advisories/new>**
(also published in `public/.well-known/security.txt`). Include a description, reproduction steps
and the affected file or endpoint.

## Demo accounts and credentials

`database/wisewallet.sql` seeds two **DEMO-ONLY** accounts so the app is usable immediately:

| Account   | Email                    | Password               |
|-----------|--------------------------|------------------------|
| Demo user | `demo@wisewallet.local`  | `Demo@WiseWallet2026`  |
| Admin     | `admin@wisewallet.local` | `Admin@WiseWallet2026` |

These passwords are public by design. They exist only in the throw-away database created by
`docker compose`, Codespaces or the setup scripts. **Never expose this seed to the internet**:
delete or re-hash both accounts before any real deployment. The Docker Compose stack binds to
`127.0.0.1` for that reason, and its database passwords are demo defaults (see `.env.example`).
No real credentials, API keys or personal data are in the repository; `.env` is git-ignored.

## Security controls implemented

Each item names where it lives so it can be checked.

**Authentication and accounts** (`app/Security/Auth.php`, `Totp.php`, `Pwned.php`, `RateLimit.php`)
- Passwords hashed with **Argon2id** (64 MiB, 4 iterations, 2 threads) and transparently re-hashed at login if the parameters change.
- Password policy: at least 8 characters with upper case, lower case and a digit (`Validator::password`).
- **Breached-password check** at registration and password reset using the Have I Been Pwned range API (k-anonymity: only the first 5 characters of the SHA-1 leave the server). It fails open if the API is unreachable.
- **Optional TOTP two-factor authentication** (RFC 6238, pure PHP, +/-1 time-step tolerance, `hash_equals` comparison).
- **Brute-force protection:** failed logins are recorded per email and per IP; after 5 failures in 15 minutes further attempts are refused (`LOGIN_MAX_ATTEMPTS` / `LOGIN_LOCKOUT`), and 10 failures lock the account for 15 minutes.
- A password hash is verified even for unknown emails and the error message is generic, to limit user-enumeration and timing leaks on the login form.
- Password-reset tokens are 256-bit random values; only their SHA-256 hash is stored; they expire after 1 hour; requests are rate-limited (5 per 15 minutes per IP). The forgot-password response never reveals whether the email exists.
- A notification is created when an account signs in from an IP it has not used before.

**Sessions** (`app/Security/Session.php`)
- Cookie flags `HttpOnly` and `SameSite=Lax`; `Secure` when `SESSION_SECURE=true`; `use_strict_mode` and `use_only_cookies`.
- Idle timeout (`SESSION_LIFETIME`, default 30 minutes); session ID regenerated at login and every 5 minutes.
- The session is bound to a fingerprint (SHA-256 of User-Agent + `APP_KEY`) and destroyed if it changes.

**Request handling** (`app/bootstrap.php`, `Csrf.php`, `Firewall.php`, `Validator.php`)
- **CSRF:** a per-session synchroniser token is required on every POST/PUT/PATCH/DELETE (form field or `X-CSRF-Token` header), compared in constant time and enforced centrally in the bootstrap.
- **SQL injection:** all queries go through `Database` using PDO prepared statements with emulation disabled. Dynamic `WHERE` clauses are assembled from fixed fragments with bound `?` parameters.
- **XSS:** view output is escaped with `e()` (`htmlspecialchars`, `ENT_QUOTES`), backed by the CSP below.
- **Access control:** API endpoints and app pages call `Auth::requireAuth()` (admin pages `requireAdmin()`), and resource access goes through `Auth::ownOr404()` to prevent IDOR.
- **Request firewall:** optional IP deny-list and admin-area IP allow-list; per-IP API rate limit (default 240 requests/minute, HTTP 429); a small set of signature rules that blocks obvious XSS, UNION SELECT and path-traversal payloads, with blocks written to `security_events`. This is a defence-in-depth filter, not a substitute for a real WAF.
- **Exports:** CSV cells starting with `=`, `+`, `-`, `@`, tab or CR are neutralised against spreadsheet formula injection.
- An audit log records logins, failures, password resets and admin actions and is viewable in the admin panel.

**Response headers** (`app/Security/Headers.php`, `public/.htaccess`)
- Nonce-based `Content-Security-Policy` (`default-src 'self'`, `script-src 'self' 'nonce-...'`, `object-src 'none'`, `frame-ancestors 'none'`, `base-uri 'self'`, `form-action 'self'`) with a `report-uri` collector (`public/api/csp-report.php`).
- `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, COOP/CORP `same-origin`, HSTS (sent over HTTPS), `X-Powered-By` removed.
- Apache denies dotfiles and directory listing, and only `public/` is the document root, so `app/`, `database/` and `.env` are not web-reachable.

**Tests:** `tests/security-checks.php` drives the running app over HTTP and asserts login, CSRF, validation, IDOR, RBAC, WAF, escaping, CSV-injection, rate-limit and header behaviour. CI runs it against the Docker Compose stack.

## Known limitations

- **TOTP setup leaks the secret to a third party.** The QR code is rendered by `api.qrserver.com` (`public/settings.php`), so the otpauth URI, including the 2FA secret, is sent to that service. Enter the displayed key manually instead, or replace the QR with a locally generated one. The secret is also stored unencrypted in `users.totp_secret`, there are no recovery codes, and used codes are not tracked against replay.
- The CSP allows `style-src 'unsafe-inline'` (needed by ApexCharts) and a few external image/media hosts (`images.unsplash.com`, `api.qrserver.com`, video CDNs).
- `/logout` is a plain GET without CSRF protection (logout CSRF, low impact).
- Registration reveals whether an email is already registered, and the per-account lockout can be abused to lock someone else out for 15 minutes.
- `public/api/csp-report.php` is unauthenticated by design (browsers post to it) and has no rate limit, so it could be used to fill the `security_events` table.
- Rate limits and the session fingerprint rely on `REMOTE_ADDR` and the User-Agent; behind a reverse proxy the proxy address would be seen unless trusted-proxy handling is added.
- The `Secure` cookie flag is controlled by `SESSION_SECURE`, not auto-detected.
- No formal threat model and no external audit.

## Hardening checklist for a real deployment

- [ ] `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] Serve over HTTPS and set `SESSION_SECURE=true`.
- [ ] Delete the seeded demo and admin accounts (or re-hash them with new passwords).
- [ ] Use a dedicated least-privilege MySQL user (not `root`) with a strong password.
- [ ] Generate a fresh `APP_KEY` (`php -r "echo bin2hex(random_bytes(32));"`).
- [ ] Set `MAIL_DRIVER=smtp` if password-reset emails should be delivered.

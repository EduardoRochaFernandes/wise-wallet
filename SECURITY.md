# Security Policy

WiseWallet is a learning/demo project, but it is built to **production security
standards**. We take security reports seriously.

## Supported versions

| Version | Supported |
|---------|-----------|
| 2.0.x   | ✅        |
| < 2.0   | ❌        |

## Reporting a vulnerability

Please **do not open a public issue** for security problems.

- Email: **security@wisewallet.local** (replace with the maintainer's address)
- Or use GitHub's *Private vulnerability reporting* (Security → Advisories).

Include: a description, reproduction steps, affected files/endpoints, and the
potential impact. We aim to acknowledge within 72 hours.

## Security measures in place

- **Passwords:** Argon2id (`memory_cost=64MB, time_cost=4, threads=2`) with
  automatic rehash-on-login.
- **SQL:** PDO prepared statements only — no string concatenation.
- **CSRF:** synchronizer token on every state-changing request (form field or
  `X-CSRF-Token` header), constant-time validation.
- **Sessions:** HttpOnly + SameSite=Lax cookies, idle timeout, id regeneration
  on login and periodically, strict mode.
- **Brute force:** per-identifier/IP rate limiting with lockout + attempt log.
- **Headers:** nonce-based CSP, HSTS, X-Frame-Options DENY, X-Content-Type-
  Options nosniff, Referrer-Policy, Permissions-Policy.
- **Access control:** RBAC (user/admin) and per-resource ownership checks
  (anti-IDOR) on every API endpoint.
- **Output:** all dynamic output escaped with `htmlspecialchars` (XSS defence).
- **Secrets:** kept in `.env` (git-ignored); never committed.

## Implemented controls (v2.0)

**Authentication & accounts**
- [x] Argon2id hashing (64 MB / t=4 / p=2) + rehash-on-login
- [x] TOTP two-factor authentication (RFC 6238, pure PHP)
- [x] Breached-password rejection (HaveIBeenPwned k-anonymity, keyless)
- [x] Brute-force rate limiting + escalating account lockout
- [x] Login alert on first sign-in from a new IP
- [x] Constant-time credential comparison (`hash_equals`)

**Session**
- [x] HttpOnly + SameSite=Lax cookies, Secure on HTTPS
- [x] Idle timeout + periodic id regeneration + regen on login
- [x] Device fingerprint binding (invalidates stolen sessions)
- [x] `session.use_strict_mode` / `use_only_cookies`

**Request / API**
- [x] CSRF synchronizer token on every mutation (form + `X-CSRF-Token`)
- [x] Global per-IP API rate limiting (429 + Retry-After)
- [x] WAF-lite signature blocking (XSS / SQLi / traversal) → `security_events`
- [x] IP deny-list + admin IP allow-list
- [x] RBAC (user/admin) + per-resource ownership checks (anti-IDOR)
- [x] Strict server-side validation/sanitization (`Validator`)
- [x] CSV/formula-injection-safe export

**Transport / headers**
- [x] Nonce-based CSP + `report-uri` collector
- [x] HSTS, X-Frame-Options DENY, X-Content-Type-Options, Referrer-Policy,
      Permissions-Policy, COOP/CORP
- [x] `X-Powered-By` removed

**Data / output**
- [x] PDO prepared statements only
- [x] Output escaping everywhere (`e()`)
- [x] Secrets in `.env` (git-ignored), never committed
- [x] `/.well-known/security.txt`
- [x] Audit log + security-event log surfaced in the admin panel

> EDR/XDR is an OS/endpoint agent and lives *outside* the application; deploy it
> at the host level (e.g. Defender for Endpoint, CrowdStrike) alongside a WAF/CDN
> (Cloudflare) and a reverse proxy for network-layer protection.

## Hardening checklist for production

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false` in `.env`.
- [ ] Serve over HTTPS and set `SESSION_SECURE=true` (enables HSTS + Secure cookies).
- [ ] Change the seeded admin/demo passwords (or remove the demo user).
- [ ] Use a dedicated MySQL user with least privilege (not `root`).
- [ ] Generate a fresh `APP_KEY`.

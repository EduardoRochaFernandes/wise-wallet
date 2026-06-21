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

## Hardening checklist for production

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false` in `.env`.
- [ ] Serve over HTTPS and set `SESSION_SECURE=true` (enables HSTS + Secure cookies).
- [ ] Change the seeded admin/demo passwords (or remove the demo user).
- [ ] Use a dedicated MySQL user with least privilege (not `root`).
- [ ] Generate a fresh `APP_KEY`.

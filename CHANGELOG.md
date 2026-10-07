# Changelog

All notable changes to WiseWallet are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/) and the
project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- One-click run: `docker compose up --build` (PHP 8.2 + Apache + MariaDB with auto-imported demo data)
  and a GitHub Codespaces dev container.
- `scripts/router.php` so clean URLs work under `php -S` (`setup.sh`, `setup.bat`, `npm run serve`).
- CI jobs for the Docker Compose stack (smoke tests + `tests/security-checks.php`), the dev container
  and PHP 8.4 lint; a manual workflow that captures the README screenshots.
- `.env.example` section for the Compose overrides; `.editorconfig`, Dependabot config.

### Changed
- `SECURITY.md` rewritten to list only controls verified in the code, plus known limitations.
- The test-suite reads its base URL and database settings from the environment.
- Remaining Portuguese text (docs, templates, setup output, API errors, default SEO strings) translated to English.

### Fixed
- Dev container forwarded a port nothing listened on; the Docker image now installs the `zip` extension
  needed by the XLSX export.

## [2.0.0] — 2026-06-21

### Added
- Complete rewrite as **WiseWallet 2.0**: the full personal-finance lifecycle
  (record → understand → plan → simulate → learn).
- Transactions (income / expense / transfer) with categories, tags, notes and
  multi-account balances kept in sync automatically.
- Budgets with green/amber/red alerts, goals with automatic 25/50/75% milestones.
- Bills calendar (overdue detection, mark-as-paid) and subscription tracker
  (normalized monthly/yearly cost).
- Investment portfolio with gain/loss, ROI and diversification donut.
- Analytics: 12-month cash flow, category breakdowns, weekday spend, top
  expenses and a **Financial Health Score (0–100)** gauge.
- **8 financial simulators** (mortgage, personal loan, savings, retirement,
  investment, PT income tax, leasing, emergency fund) with real formulas.
- Educational blog (admin-managed) and live financial **news via public RSS**.
- Gamification: 17 achievements with rarity and points.
- Admin panel: metrics, users, categories, blog, global settings, audit logs.
- CSV and PDF export (pure-PHP PDF writer, no GD dependency).
- Cross-cutting: dark/light theme, private mode, command palette (Ctrl+K),
  search/filter/pagination on every list.

### Security
- Argon2id password hashing, PDO prepared statements everywhere.
- CSRF tokens (synchronizer pattern) enforced on all mutations.
- Hardened sessions (HttpOnly, SameSite, idle timeout, id regeneration).
- Brute-force protection (rate limiting + login attempt log).
- Security headers: CSP (nonce-based), HSTS, X-Frame-Options, X-Content-Type-
  Options, Referrer-Policy, Permissions-Policy.
- Output escaping (XSS), IDOR ownership checks, RBAC (user/admin), audit log.

### SEO
- Per-page meta, Open Graph + Twitter cards, JSON-LD, sitemap.xml, robots.txt.

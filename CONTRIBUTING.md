# Contributing to WiseWallet

Thanks for your interest! Bug reports, ideas, docs fixes and code are all welcome.

## Getting started

The quickest path is a container (no local PHP or MySQL needed):

- **Codespaces:** click *Code -> Codespaces -> Create codespace on main* (see the README).
- **Docker:** `docker compose up --build`, then open <http://localhost:8080>.

Native alternative: PHP 8.0+ (`pdo_mysql`, `openssl`, `curl`, `mbstring`, `zip`),
MySQL/MariaDB and Node 18+, then `bash setup.sh` (or `setup.bat` on Windows) and open
<http://localhost:8000>.

## Workflow

1. Create a branch from `main`: `git checkout -b feat/my-change`.
2. Make your change in the existing style (see below).
3. Run the same checks as CI:
   ```bash
   find app public scripts tests vendor -name "*.php" -print0 | xargs -0 -n1 php -l   # PHP lint
   npm run build                                                                       # CSS bundle
   docker compose up --build -d --wait
   docker compose exec -T -e WW_TEST_BASE=http://localhost web php tests/security-checks.php
   ```
4. Commit with clear messages ([Conventional Commits](https://www.conventionalcommits.org/) recommended).
5. Open a pull request using the template and explain the *why*.

## Code style

- **PHP:** `declare(strict_types=1)`, roughly PSR-12, always use **prepared statements**
  through the `Database` class, and **escape all output** with `e()`.
- **Security first:** any endpoint that changes data must validate input (`Validator`),
  be covered by CSRF (automatic through the bootstrap) and verify resource ownership
  (`Auth::ownOr404`).
- **JavaScript:** vanilla ES6, no heavy dependencies, no inline scripts without the CSP nonce.
- **CSS:** Tailwind utilities and theme tokens.

## Structure

See *Project structure* in the [README](README.md).

## Reporting bugs and requesting features

Use the issue templates. For security problems follow [SECURITY.md](SECURITY.md) instead
of opening a public issue.

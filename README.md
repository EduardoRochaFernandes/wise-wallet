# Wise Wallet

Wise Wallet is a personal finance management application covering the full cycle of household money management: recording transactions, tracking accounts, planning budgets and goals, monitoring bills and subscriptions, analysing spending, and running financial simulations.

The project is built as a self-contained PHP application with no front-end framework dependency, designed to run on a standard LAMP-style stack (PHP, MySQL/MariaDB, Apache) with Tailwind CSS for styling and ApexCharts for data visualisation.

## Overview

The application is organised around a single domain model shared across every module:

Record transactions and account balances, understand spending through analytics, plan ahead with budgets and goals, simulate financial decisions before committing to them, and learn through educational content tailored to the user's own financial data.

## Features

Transactions
- Unified income, expense, and transfer entries with categories, accounts, tags, and notes.
- Quick-add modal accessible from anywhere in the application.

Accounts
- Multiple account types (checking, savings, card, cash, crypto, investment).
- Automatic balance updates on every transaction.
- Soft-delete with a recovery window before permanent removal.

Planning
- Category-based budgets with threshold alerts.
- Goals with deadlines, contributions, and automatic milestone tracking.
- Bill calendar with due-date and overdue detection.
- Subscription tracking with normalised monthly and annual cost.

Investments
- Support for stocks, ETFs, cryptocurrency, bonds, real estate, and retirement accounts.
- Gain and loss tracking, return on investment, and portfolio diversification breakdown.

Analytics
- Twelve-month cash flow, income and expense breakdown by category, spending by day of week, and top expenses.
- A composite Financial Health Score with supporting visual indicator.

Simulators
- Eight calculators built on real financial formulas: mortgage, personal loan, savings, retirement, investment growth, income tax (Portuguese brackets), leasing, and emergency fund sizing.

Education and engagement
- An educational blog maintained through the administration panel, with recommendations based on the user's own financial profile.
- Market news sourced from public feeds.
- An achievement system to encourage consistent use of the platform.

Data export
- CSV and formatted Excel (XLSX) export, and PDF report generation.

Interface
- Light and dark themes, a privacy mode that masks monetary values, and a command palette for keyboard-driven navigation.

## Technology stack

Frontend: semantic HTML5, Tailwind CSS, vanilla JavaScript (ES6), ApexCharts.

Backend: PHP 8 using PDO with prepared statements throughout, organised as a set of domain services without a heavyweight framework.

Database: MySQL or MariaDB, InnoDB, utf8mb4.

PDF generation: a dependency-free PHP PDF writer.

Build tooling: Node.js and npm, PostCSS, Autoprefixer.

External data: exchange rates from Frankfurter (European Central Bank data), cryptocurrency prices from CoinGecko, and market news from public RSS feeds. All external calls are made server-side, with local caching and graceful degradation when a source is unavailable, in keeping with a restrictive Content Security Policy.

## Security

- Password hashing with Argon2id, including automatic rehashing on login.
- Parameterised queries via PDO prepared statements throughout the data layer.
- CSRF protection using a synchroniser token enforced on all state-changing requests.
- Hardened sessions: HttpOnly and SameSite cookies, inactivity timeout, and session ID regeneration on privilege changes.
- Rate limiting on authentication endpoints with logging of failed attempts.
- Security response headers: a nonce-based Content Security Policy, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, and Permissions-Policy.
- Output escaping throughout the view layer to mitigate cross-site scripting.
- Ownership verification on every resource access to prevent insecure direct object reference (IDOR) issues.
- Role-based access control distinguishing standard users from administrators, with an audit log of sensitive actions.

Further detail and a production deployment checklist are documented in SECURITY.md.

## Getting started

### Requirements

- PHP 8.0 or later, with the pdo_mysql, openssl, curl, and mbstring extensions.
- MySQL or MariaDB.
- Node.js 18 or later, with npm.

On Windows, XAMPP provides PHP and MySQL/MariaDB together; the setup scripts detect a XAMPP installation automatically.

### Automated setup

```
setup.bat        (Windows)
bash setup.sh    (Linux, macOS, or Git Bash)
```

This performs the full setup without further manual steps: installing npm dependencies, building the CSS bundle and vendoring ApexCharts, creating a .env file with a generated application key, starting MySQL, importing the database schema and seed data, generating a sitemap, and starting a local PHP server at http://localhost:8000.

The quick-start server uses PHP's built-in server, so URLs retain the .php extension. For clean, extension-less URLs, serve the application through Apache as described below.

### Manual setup

```
npm install
npm run build
cp .env.example .env
mysql -u root < database/wisewallet.sql
php -S localhost:8000 -t public
```

### Demo accounts

| Role  | Email                     | Password              |
|-------|---------------------------|------------------------|
| Admin | admin@wisewallet.local    | Admin@WiseWallet2026   |
| Demo  | demo@wisewallet.local     | Demo@WiseWallet2026    |

These credentials should be changed before any production use; see SECURITY.md.

### Clean URLs via Apache

The public/.htaccess file already configures URL rewriting through mod_rewrite. Point a virtual host at this project's public/ directory, for example:

```
<VirtualHost *:8080>
    DocumentRoot "C:/path/to/wise-wallet/public"
    ServerName localhost
    <Directory "C:/path/to/wise-wallet/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

After restarting Apache, every route resolves without a .php extension, and direct .php URLs redirect to their clean equivalent.

## Search engine optimisation

Per-page meta tags, Open Graph and Twitter Card markup, JSON-LD structured data, a generated sitemap.xml, robots.txt, semantic HTML with correct heading hierarchy, canonical URLs, language attributes, descriptive alt text, and minified CSS.

## Project structure

```
wise-wallet/
  app/                    Core PHP application logic, not served directly
    bootstrap.php         Configuration, security, session, and header wiring
    config.php            Environment loader and application constants
    Database.php          PDO gateway using prepared statements
    Security/             Session, Headers, Csrf, Auth, RateLimit, Validator, Audit
    Services/             Finance, Achievements, News, ApiClient, Cache
    views/partials/       Shared layout, SEO, and navigation partials
  public/                 Web root
    index.php             Landing page
    admin/                 Administration panel
    api/                   JSON API endpoints
    assets/                 Compiled CSS, JavaScript, and images
  database/wisewallet.sql  Schema, indexes, and seed data
  scripts/                  Environment setup and build automation
  vendor/                   Dependency-free PDF and XLSX writers
  src/css/app.css           Tailwind source and design tokens
  setup.bat, setup.sh       Automated setup scripts
```

## Architecture

Every page and API endpoint requires a single bootstrap file, which loads configuration, applies security middleware, starts a hardened session, sends response headers, and enforces CSRF protection.

Domain logic is organised into services: Finance handles transactions, balances, budgets, and the financial health score; Achievements evaluates gamification state; News, ApiClient, and Cache manage external data sources with fallback behaviour.

JSON API endpoints under public/api require an authenticated session, enforce CSRF on mutations, and verify resource ownership on every request.

Pages are server-rendered and progressively enhanced with declarative JavaScript bindings and ApexCharts visualisations.

## Continuous integration

A GitHub Actions workflow runs on every push: PHP linting across supported versions, a build verification step that confirms the CSS bundle compiles, and a database validation step that imports the schema into a disposable MySQL instance and confirms the expected tables and seed data.

## Contributing

See CONTRIBUTING.md. In summary: fork the repository, create a branch, ensure php -l and npm run build succeed, and open a pull request using the provided template. Please also review the Code of Conduct.

## License

Released under the MIT License. See LICENSE for the full text.

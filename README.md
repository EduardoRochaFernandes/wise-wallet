<div align="center">

# 💜 WiseWallet 2.0

### A tua vida financeira, num só lugar — do euro de hoje à reforma de amanhã.

[![CI](https://img.shields.io/badge/CI-passing-brightgreen)](.github/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL%2FMariaDB-10.4%2B-00758f)](https://mariadb.org)
[![Tailwind](https://img.shields.io/badge/TailwindCSS-3-38bdf8)](https://tailwindcss.com)
[![Security](https://img.shields.io/badge/security-Argon2id%20%7C%20CSRF%20%7C%20CSP-success)](SECURITY.md)

</div>

> **WiseWallet** une o ciclo completo das finanças pessoais num produto coerente:
> **registar → entender → planear → simular → aprender**. Cada módulo alimenta o
> seguinte, e o utilizador volta ao início mais capaz.

---

## ✨ Porquê a WiseWallet

A maioria das apps faz uma coisa: ou regista gastos, ou faz orçamentos, ou simula
créditos. A WiseWallet faz o **ciclo inteiro**, com três prioridades equilibradas:

| Pilar | O que entrega |
|------|----------------|
| **Funcionalidades** | Transações, contas, orçamentos, objetivos, faturas, subscrições, investimentos, 8 simuladores, blog, notícias, gamificação |
| **Design** | Dashboard moderno e cinematográfico, dark/light, responsivo, microinterações, command palette |
| **Cibersegurança** | Argon2id, CSRF, CSP com nonce, sessões seguras, anti-brute-force, anti-IDOR, RBAC, auditoria |

---

## 🌐 Live preview — zero install

<!-- TODO: replace OWNER/REPO below with the real GitHub path once published -->
[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/OWNER/REPO?quickstart=1)

Click the badge above to launch a **fully working, always-available preview** of
WiseWallet in your browser — nothing to install on your own machine. GitHub
Codespaces builds the container (`Dockerfile` + `docker-compose.yml`: PHP 8.2 +
Apache + MariaDB), then `.devcontainer/setup.sh` installs dependencies, builds
the CSS, and imports the database automatically. After a minute or two, the
app opens on the forwarded port with the same demo data described below —
sign in with `demo@wisewallet.local` / `Demo@WiseWallet2026`.

> Codespaces is part of every free GitHub account; you only need to be signed
> in. No PHP, MySQL, or Node installation required on your computer.

---

## 🚀 One-click local start

### Pré-requisitos
- **PHP 8.0+** com extensões `pdo_mysql`, `openssl`, `curl`, `mbstring`
- **MySQL** ou **MariaDB**
- **Node.js 18+** e **npm**

> No Windows o **XAMPP** já inclui PHP + MySQL/MariaDB. Os scripts detetam-no
> automaticamente em `C:\xampp`.

### One-click start
```bash
# Windows
setup.bat

# Linux / macOS / Git Bash
bash setup.sh
```

The script does **everything**, no manual steps:
1. `npm install` 2. builds the CSS + vendors ApexCharts 3. creates `.env` (with a fresh `APP_KEY`)
4. starts MySQL and waits for it 5. imports `database/wisewallet.sql` 6. generates `sitemap.xml`,
starts the server at `http://localhost:8000` and opens the browser.

> This quick-start server uses `php -S` directly, so URLs keep the `.php` extension.
> For **clean, extension-less URLs** (e.g. `/dashboard` instead of `/dashboard.php`),
> serve the app through Apache instead — see below.

### Demo accounts
| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@wisewallet.local` | `Admin@WiseWallet2026` |
| Demo (with data) | `demo@wisewallet.local` | `Demo@WiseWallet2026` |

> **Change these credentials** before any real-world use (see [SECURITY.md](SECURITY.md)).

### Manual start (alternative)
```bash
npm install
npm run build
cp .env.example .env          # then edit the DB credentials
mysql -u root < database/wisewallet.sql
php -S localhost:8000 -t public
```

### Clean URLs via Apache (recommended for a polished demo)
`public/.htaccess` already handles the rewriting (mod_rewrite); you only need a
vhost pointing at this project's `public/` folder. Example for XAMPP — add to
`xampp/apache/conf/extra/httpd-vhosts.conf` (and `Listen 8080` in `httpd.conf`
if you pick a new port):
```apache
<VirtualHost *:8080>
    DocumentRoot "C:/path/to/WiseWallet-2.0/public"
    ServerName localhost
    <Directory "C:/path/to/WiseWallet-2.0/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
Restart Apache, then open `http://localhost:8080` — every page resolves without
`.php` (e.g. `/login`, `/dashboard`, `/admin`), and visiting a `.php` URL
directly 301-redirects to its clean equivalent.

---

## 🧩 Funcionalidades

- **Transações** unificadas (receita/despesa/transferência) com categoria, conta,
  etiquetas, **notas** e adição rápida (FAB + `Ctrl/⌘+K`).
- **Contas múltiplas** (à ordem, poupança, cartão, dinheiro, cripto, investimento)
  com saldo auto-atualizado a cada movimento.
- **Orçamentos** por categoria com alertas verde/laranja/vermelho.
- **Objetivos** com prazos, contribuições e marcos automáticos (25/50/75%).
- **Faturas** (calendário, deteção de atrasos, marcar como paga) e **subscrições**
  (custo mensal/anual normalizado — o "vampiro silencioso").
- **Investimentos** (ações/ETF/cripto/obrigações/imobiliário/PPR) com ganho/perda,
  ROI e diversificação.
- **Análise**: fluxo de caixa 12 meses, despesas/receitas por categoria, gastos por
  dia da semana, top despesas e **Score de Saúde Financeira (0–100)** com gauge.
- **8 simuladores** com fórmulas reais: crédito habitação, crédito pessoal, poupança,
  reforma, investimento, **IRS (escalões PT)**, leasing e fundo de emergência.
- **Blog** educativo (gerível pelo admin) e **notícias** financeiras via RSS.
- **Gamificação**: 17 conquistas com raridade e pontos.
- **Exportação** CSV e PDF. **Tema** escuro/claro, **modo privado**, **command palette**.

---

## 🛠️ Stack

| Camada | Tecnologia |
|--------|-----------|
| Frontend | HTML5 semântico, **Tailwind CSS** (build npm), JavaScript ES6 vanilla, **ApexCharts** |
| Backend | **PHP 8.0** (PDO), arquitetura por serviços, sem framework pesado |
| Base de dados | **MySQL / MariaDB** (24 tabelas, InnoDB, utf8mb4) |
| PDF | `SimplePdf` — escritor PDF puro em PHP (sem GD) |
| Dev/Build | Node + npm, PostCSS, Autoprefixer |

### 🌐 APIs gratuitas usadas (sem chave, sem registo)
| Uso | API | Fallback |
|-----|-----|----------|
| Câmbios | [Frankfurter](https://www.frankfurter.app) (dados BCE) | cache + taxas estáticas |
| Cripto | [CoinGecko](https://www.coingecko.com/en/api) público | cache + valores estáticos |
| Notícias | RSS públicos (Yahoo Finance, etc.) | `news_cache` + cache |

Todas as chamadas são feitas **no servidor** (com cache em `data/` e degradação
graciosa quando a API está offline), mantendo a CSP em `connect-src 'self'`.

---

## 🔒 Segurança

- **Argon2id** (rehash automático no login) · **PDO prepared statements** em todas as queries.
- **CSRF** (token sincronizador) imposto a todas as mutações no bootstrap.
- **Sessões** HttpOnly + SameSite, timeout por inatividade, regeneração de id.
- **Anti-brute-force**: rate limiting por identificador/IP + log de tentativas.
- **Cabeçalhos**: CSP com nonce, HSTS, X-Frame-Options, X-Content-Type-Options,
  Referrer-Policy, Permissions-Policy.
- **XSS**: escape de todo o output. **IDOR**: verificação de posse em cada recurso.
- **RBAC** (user/admin) + **log de auditoria** de eventos sensíveis.

Detalhes e checklist de produção em **[SECURITY.md](SECURITY.md)**.

---

## 📈 SEO

Meta tags por página, **Open Graph + Twitter Cards**, **JSON-LD** (schema.org),
`sitemap.xml` (gerado de artigos + páginas), `robots.txt`, HTML semântico, headings
corretos, `canonical`, `lang`, alt text e CSS minificado.

---

## 🗂️ Estrutura do projeto

```
WiseWallet-2.0/
├── app/                      # Núcleo PHP (não servido diretamente)
│   ├── bootstrap.php         # Wiring: config, segurança, sessão, headers, CSRF
│   ├── config.php            # Loader de .env + constantes
│   ├── Database.php          # Gateway PDO (prepared statements)
│   ├── helpers.php · icons.php
│   ├── Security/             # Session, Headers, Csrf, Auth, RateLimit, Validator, Audit
│   ├── Services/             # Finance, Achievements, News, ApiClient, Cache
│   └── views/partials/       # Layouts (app/public) + SEO + admin nav
├── public/                   # Web root (DocumentRoot)
│   ├── index.php             # Landing cinematográfica
│   ├── dashboard.php · transactions.php · ... · settings.php
│   ├── admin/                # Painel de administração
│   ├── api/                  # Endpoints JSON (REST-ish)
│   ├── assets/               # CSS compilado, JS, imagens
│   ├── robots.txt · sitemap.xml · .htaccess
├── database/wisewallet.sql   # Esquema + índices + seed + admin pré-criado
├── scripts/                  # setup-env, wait-mysql, build-sitemap, vendor-assets
├── vendor/fpdf/SimplePdf.php  # Escritor PDF puro PHP
├── src/css/app.css           # Fonte Tailwind (design tokens)
├── setup.bat · setup.sh      # Automação 1-clique
└── .github/                  # CI, dependabot, templates de issues/PR
```

---

## 🏛️ Arquitetura

- **Bootstrap único**: cada página/endpoint faz `require app/bootstrap.php`, que
  carrega config, liga a segurança, arranca a sessão endurecida, envia os
  cabeçalhos e impõe CSRF.
- **Serviços de domínio**: `Finance` concentra leituras/escritas (transações,
  saldos, orçamentos, Score de Saúde…); `Achievements` avalia a gamificação;
  `News`/`ApiClient`/`Cache` tratam dados externos com fallback.
- **API JSON** em `public/api/*`: sessão obrigatória, CSRF em mutações e
  verificação de posse (`Auth::ownOr404`) em todos os recursos.
- **Frontend progressivo**: páginas renderizadas no servidor + JS declarativo
  (`data-api-form`, `data-del`, `data-modal-open`) e gráficos ApexCharts.

---

## 📋 GitHub Project board (sugerido)

Cria um **Project (board)** com três colunas e usa os labels incluídos:

| To do | Doing | Done |
|-------|-------|------|
| Issues novas (bug/feature) | Em desenvolvimento ativo | PR fundido + verificado |

Fluxo: *issue → branch `feat/…` ou `fix/…` → PR (template) → CI verde → review → merge → Done*.

### 🤖 Automations explicadas
- **CI** (`.github/workflows/ci.yml`): em cada push/PR corre **lint PHP** (8.0 e 8.2),
  **build dos assets** (verifica que o CSS é gerado) e **validação do SQL**
  (importa o schema num MySQL efémero e confirma tabelas + seed).
- **Dependabot** (`.github/dependabot.yml`): PRs semanais para atualizar
  dependências **npm** e **GitHub Actions**.

---

## 🤝 Contribuir

Ver **[CONTRIBUTING.md](CONTRIBUTING.md)**. Em resumo: fork → branch → `php -l` +
`npm run build` verdes → PR com o template. Sê gentil ([Código de Conduta](CODE_OF_CONDUCT.md)).

## 📜 Licença

[MIT](LICENSE) © 2026 Eduardo Fernandes e contribuidores da WiseWallet.

<div align="center"><sub>Construído com 💜 — finanças pessoais que se entendem.</sub></div>

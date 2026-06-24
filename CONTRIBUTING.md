# Contribuir para a WiseWallet

Obrigado pelo interesse! Contribuições são bem-vindas — código, documentação,
reporte de bugs ou ideias.

## Como começar

1. Faz **fork** e clona o repositório.
2. Garante os pré-requisitos: **PHP 8.0+** (com `pdo_mysql`, `openssl`, `curl`,
   `mbstring`), **MySQL/MariaDB**, **Node 18+**. (No Windows, o XAMPP já traz tudo.)
3. Corre o setup de 1 clique:
   - Windows: `setup.bat`
   - Linux/macOS/Git Bash: `bash setup.sh`
4. Abre `http://localhost:8000`.

## Fluxo de trabalho

1. Cria um branch a partir de `main`: `git checkout -b feat/o-meu-recurso`.
2. Faz as alterações seguindo o estilo existente (ver abaixo).
3. Garante que o CI passa localmente:
   ```bash
   # Lint de todos os ficheiros PHP
   find app public scripts -name "*.php" -print0 | xargs -0 -n1 php -l
   # Build dos assets
   npm run build
   ```
4. Faz commit com mensagens claras (recomendado: [Conventional Commits](https://www.conventionalcommits.org/)).
5. Abre um Pull Request usando o template e descreve o "porquê".

## Estilo de código

- **PHP**: `declare(strict_types=1)`, PSR-12 aproximado, sempre **prepared
  statements** via a classe `Database`, e **escapar todo o output** com `e()`.
- **Segurança primeiro**: qualquer endpoint que altere dados tem de validar
  input (classe `Validator`), respeitar CSRF (automático no bootstrap) e
  verificar a posse do recurso (`Auth::ownOr404`).
- **JS**: ES6 vanilla, sem dependências pesadas. Nada de inline scripts sem nonce.
- **CSS**: usar tokens/utilitários do Tailwind; cores via variáveis de tema.

## Estrutura

Ver a secção *Arquitetura* no [README](README.md).

## Reportar bugs / pedir features

Usa os templates em **Issues**. Inclui passos de reprodução e ambiente.

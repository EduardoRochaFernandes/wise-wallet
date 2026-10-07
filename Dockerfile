# syntax=docker/dockerfile:1
# WiseWallet -- PHP 8.2 + Apache image. Used by `docker compose up` and the Codespaces dev container.

# ── Stage 1: build the Tailwind CSS bundle and vendor ApexCharts ──────────────
FROM node:26-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY tailwind.config.js postcss.config.js ./
COPY src ./src
COPY scripts/vendor-assets.js ./scripts/vendor-assets.js
# Tailwind scans these paths for class names (see tailwind.config.js).
COPY public ./public
COPY app/views ./app/views
RUN npm run build

# ── Stage 2: runtime ──────────────────────────────────────────────────────────
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev curl \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/* \
    # Fail the build early if an extension the app needs is missing.
    && php -r 'foreach (["pdo_mysql","zip","mbstring","curl","openssl","iconv","SimpleXML"] as $e) { if (!extension_loaded($e)) { fwrite(STDERR, "missing ext: $e\n"); exit(1); } } if (!defined("PASSWORD_ARGON2ID")) { fwrite(STDERR, "no Argon2id\n"); exit(1); }'

# Apache serves public/ with .htaccess honoured (clean URLs, dotfile blocking).
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\nServerName localhost\nServerTokens Prod\nServerSignature Off\n' \
       > /etc/apache2/conf-available/wisewallet.conf \
    && a2enconf wisewallet \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/^expose_php = .*/expose_php = Off/' "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
COPY . .
COPY --from=assets /build/public/assets/css ./public/assets/css
COPY --from=assets /build/public/assets/js/apexcharts.min.js ./public/assets/js/apexcharts.min.js
COPY docker/entrypoint.sh /usr/local/bin/ww-entrypoint
RUN chmod +x /usr/local/bin/ww-entrypoint \
    && mkdir -p storage/logs data \
    && chown -R www-data:www-data storage data

EXPOSE 80
HEALTHCHECK --interval=10s --timeout=5s --retries=12 CMD curl -fsS http://localhost/login -o /dev/null || exit 1
ENTRYPOINT ["ww-entrypoint"]
CMD ["apache2-foreground"]

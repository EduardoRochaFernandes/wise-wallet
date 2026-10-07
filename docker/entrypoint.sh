#!/bin/sh
# Container entrypoint. Generates a random APP_KEY for this container if none was
# supplied (or if the .env.example placeholder was copied verbatim), then starts Apache.
# Apache inherits the container environment, and the app reads it via getenv().
set -e

case "${APP_KEY:-}" in
  ""|change-me*) APP_KEY="$(php -r 'echo bin2hex(random_bytes(32));')"; export APP_KEY ;;
esac

# Bind-mounted workspaces (dev container) may arrive with restrictive permissions.
mkdir -p /var/www/html/storage/logs /var/www/html/data
chown -R www-data:www-data /var/www/html/storage /var/www/html/data 2>/dev/null || true

exec docker-php-entrypoint "$@"

ARG PHP_VERSION=8.5

############################################
# Base Stage
############################################
FROM serversideup/php:${PHP_VERSION}-frankenphp AS base

USER root

RUN install-php-extensions bcmath

############################################
# Builder Stage
############################################
FROM base AS builder

COPY --from=oven/bun:1.3-debian /usr/local/bin/bun /usr/local/bin/bun

COPY --link composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

COPY --link package*.json bun.lock* ./

RUN bun install --frozen-lockfile

COPY --link . .

RUN composer dump-autoload --classmap-authoritative --no-dev

ARG ENV_HASH
RUN --mount=type=secret,id=dotenv \
    echo "Build with ENV_HASH=${ENV_HASH}" && \
    set -a && . /run/secrets/dotenv && set +a && \
    bun run build:ssr

############################################
# App Image
############################################
FROM base AS app

COPY --link --chown=33:33 --from=builder /var/www/html/vendor ./vendor

COPY --link --chown=33:33 . .

COPY --link --chown=33:33 --from=builder /var/www/html/public/build ./public/build

RUN mkdir -p \
    storage/logs \
    storage/app/public \
    storage/app/generated-templates \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    # Fix: Laravel Octane hardcodes "auto_https disable_redirects" which still triggers ACME \
    # This causes 30s timeouts. Replace with "auto_https off" to fully disable ACME.
    && sed -i 's/{\$CADDY_GLOBAL_OPTIONS}/auto_https off/' vendor/laravel/octane/src/Commands/stubs/Caddyfile

USER www-data

############################################
# SSR Image
############################################
FROM oven/bun:1.3-debian AS ssr

WORKDIR /app

COPY --from=builder /var/www/html/bootstrap/ssr ./bootstrap/ssr

EXPOSE 13714

CMD ["bun", "bootstrap/ssr/ssr.js"]

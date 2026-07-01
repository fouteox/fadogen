ARG PHP_VERSION=8.5

############################################
# Base Stage
############################################
FROM serversideup/php:${PHP_VERSION}-frankenphp AS base

USER root

RUN install-php-extensions bcmath

############################################
# Builder Stage (vendor only — JS assets are pre-built on the CI runner,
# so the build needs no .env secret and never bakes one into the image)
############################################
FROM base AS builder

COPY --link composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

COPY --link . .

RUN composer dump-autoload --classmap-authoritative --no-dev

############################################
# App Image
############################################
FROM base AS app

COPY --link --chown=33:33 --from=builder /var/www/html/vendor ./vendor

# The build context already carries public/build (pre-built on the runner).
COPY --link --chown=33:33 . .

RUN mkdir -p \
    storage/logs \
    storage/app/public \
    storage/app/generated-templates \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

############################################
# SSR Image
############################################
FROM oven/bun:1.3-debian AS ssr

WORKDIR /app

# bootstrap/ssr is produced by `bun run build:ssr` on the runner.
COPY --link bootstrap/ssr ./bootstrap/ssr

EXPOSE 13714

CMD ["bun", "bootstrap/ssr/ssr.js"]

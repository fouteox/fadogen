ARG PHP_VERSION=8.4

############################################
# Base Stage
############################################
FROM serversideup/php:beta-${PHP_VERSION}-frankenphp AS base

USER root

RUN install-php-extensions bcmath

############################################
# Builder Stage
############################################
FROM base AS builder

COPY --from=oven/bun:1 /usr/local/bin/bun /usr/local/bin/bun

COPY --link --chown=www-data:www-data composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

COPY --link --chown=www-data:www-data package*.json bun.lock* ./

RUN bun install --frozen-lockfile

COPY --link --chown=www-data:www-data . .

RUN composer dump-autoload --classmap-authoritative --no-dev

RUN bun run build:ssr

USER www-data

############################################
# App Image
############################################
FROM base AS app

COPY --link --chown=www-data:www-data --from=builder /var/www/html/vendor ./vendor

COPY --link --chown=www-data:www-data . .

COPY --link --chown=www-data:www-data --from=builder /var/www/html/public/build ./public/build

USER www-data

############################################
# SSR Image
############################################
FROM node:24-alpine AS ssr

WORKDIR /app

COPY --from=builder /var/www/html/bootstrap/ssr ./bootstrap/ssr

COPY --from=builder /var/www/html/node_modules ./node_modules

EXPOSE 13714

CMD ["node", "bootstrap/ssr/ssr.js"]

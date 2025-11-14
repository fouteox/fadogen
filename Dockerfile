ARG PHP_VERSION=8.4

############################################
# Base Stage
############################################
FROM serversideup/php:beta-${PHP_VERSION}-frankenphp AS base

USER root

RUN install-php-extensions bcmath

USER www-data

############################################
# Builder Stage
############################################
FROM base AS builder

USER root

COPY --from=oven/bun:1 /usr/local/bin/bun /usr/local/bin/bun

USER www-data

WORKDIR /var/www/html

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

############################################
# App Image
############################################
FROM base AS app

WORKDIR /var/www/html

COPY --link --chown=www-data:www-data --from=builder /var/www/html/vendor ./vendor

COPY --link --chown=www-data:www-data . .

COPY --link --chown=www-data:www-data --from=builder /var/www/html/public/build ./public/build

############################################
# SSR Image
############################################
FROM node:24-alpine AS ssr

WORKDIR /app

COPY --from=builder /var/www/html/bootstrap/ssr ./bootstrap/ssr

COPY --from=builder /var/www/html/node_modules ./node_modules

EXPOSE 13714

CMD ["node", "bootstrap/ssr/ssr.js"]

############################################
# Base Stage
############################################
# Digest-pinned (supply-chain): builds are reproducible and every upstream
# rebuild of the tag lands as a reviewable Dependabot PR (tag + digest kept
# in sync). PHP version bumps stay deliberate: Dockerfile + setup-php in
# build.yml + composer.json must move together.
FROM serversideup/php:8.5-frankenphp@sha256:a0f4447da7612f9bca3c982d0cf33a607cbddf828f4b96a44bfa9f6f037007b6 AS base

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
FROM oven/bun:1.3-debian@sha256:9dba1a1b43ce28c9d7931bfc4eb00feb63b0114720a0277a8f939ae4dfc9db6f AS ssr

WORKDIR /app

# bootstrap/ssr is produced by `vp run build:ssr` on the runner.
COPY --link bootstrap/ssr ./bootstrap/ssr

EXPOSE 13714

CMD ["bun", "bootstrap/ssr/app.js"]

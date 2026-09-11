############################################
# Base Stage
############################################
# Digest-pinned (supply-chain): builds are reproducible and every upstream
# rebuild of the tag lands as a reviewable Renovate PR (tag + digest kept
# in sync). Renovate moves this base and Composer's requirement/platform
# together; setup-php tracks the same minor compatibility line.
FROM serversideup/php:8.5.10-frankenphp@sha256:558d9d93d8f63a08ea6c99a48475faff557d8bd315bb81a876181e9ec1e50865 AS base

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
COPY --link --chown=33:33 --from=builder /var/www/html/bootstrap/cache ./bootstrap/cache

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

# The serversideup base grants cap_net_bind_service to frankenphp (bind <1024).
# Octane listens on 8000, and the file capability makes execve fail with
# "Operation not permitted" under no_new_privs (PSS restricted,
# allowPrivilegeEscalation: false). A plain cp does not preserve xattrs,
# which strips the capability without needing libcap2-bin.
RUN cp /usr/local/bin/frankenphp /tmp/frankenphp \
    && mv /tmp/frankenphp /usr/local/bin/frankenphp

USER www-data

############################################
# SSR Image
############################################
FROM oven/bun:1.4-distroless@sha256:1a0c31c7c5f9d193aedf60fe1cebdeb76ac8f6e29f24be8dd8cbd6df72df26ec AS ssr

WORKDIR /app

# bootstrap/ssr is produced by `vp run build:ssr` on the runner, under
# `umask 077` (build.yml) — the bundle lands 0600 in the context, so it
# must be chowned to the runtime user or the non-root server cannot read it.
COPY --link --chown=1000:1000 bootstrap/ssr ./bootstrap/ssr

# Keep the numeric non-root identity enforced by the GitOps deployment. Numeric
# IDs do not depend on a shell or a named /etc/passwd entry in distroless.
USER 1000:1000

EXPOSE 13714

ENTRYPOINT ["/usr/local/bin/bun"]
CMD ["bootstrap/ssr/app.js"]

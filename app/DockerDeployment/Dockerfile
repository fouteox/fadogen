ARG PHP_VERSION=8.4
ARG FRANKENPHP_VERSION=1.7
ARG APP_ENV=production
ARG NODE_VERSION=22
ARG BUN_VERSION=1.2.16
ARG SUPERCRONIC_VERSION=0.2.34

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS base

ARG TZ=UTC
ARG APP_DIR=/app
ARG SUPERCRONIC_VERSION
ARG APP_ENV=production
ARG TARGETARCH
ARG NODE_VERSION
ARG BUN_VERSION

ENV DEBIAN_FRONTEND=noninteractive \
    TERM=xterm-color \
    OCTANE_SERVER=frankenphp \
    TZ=${TZ} \
    ROOT=${APP_DIR} \
    APP_ENV=${APP_ENV} \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24 \
    XDG_CONFIG_HOME=${APP_DIR}/.config \
    XDG_DATA_HOME=${APP_DIR}/.data \
    PATH=/usr/local/bun/bin:/usr/local/node/bin:$PATH \
    SERVER_NAME=:80

WORKDIR ${ROOT}

SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

RUN apt-get update; \
    apt-get upgrade -yqq; \
    apt-get install -yqq --no-install-recommends --show-progress \
    apt-utils \
    curl \
    git \
    ca-certificates \
    supervisor \
    libsodium-dev \
    libbrotli-dev \
    && install-php-extensions \
    @composer \
    bcmath \
    bz2 \
    calendar \
    dba \
    exif \
    ffi \
    gd \
    gmp \
    igbinary \
    imagick \
    imap \
    intl \
    ldap \
    lz4 \
    memcached \
    mongodb \
    mysqli \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlsrv \
    pgsql \
    redis \
    shmop \
    soap \
    sockets \
    sqlsrv \
    sysvmsg \
    sysvsem \
    sysvshm \
    uv \
    vips \
    xsl \
    zip \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x -o nodesource_setup.sh \
    && bash nodesource_setup.sh \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && rm nodesource_setup.sh \
    && mkdir -p /usr/local/bun/bin \
    && curl -fsSL https://bun.sh/install | bash -s "bun-v${BUN_VERSION}" \
    && mkdir -p /usr/local/bun/bin \
    && cp /root/.bun/bin/bun /usr/local/bun/bin/ \
    && chmod +x /usr/local/bun/bin/bun \
    && case "${TARGETARCH}" in \
        arm64) _cronic_fname='supercronic-linux-arm64' ;; \
        amd64) _cronic_fname='supercronic-linux-amd64' ;; \
        *) echo >&2 "error: unsupported architecture: ${TARGETARCH}"; exit 1 ;; \
    esac \
    && curl -fsSL "https://github.com/aptible/supercronic/releases/download/v${SUPERCRONIC_VERSION}/${_cronic_fname}" -o /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel \
    && apt-get -y autoremove \
    && apt-get clean \
    && docker-php-source delete \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && rm -f /var/log/lastlog /var/log/faillog \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && setcap -r /usr/local/bin/frankenphp \
    && chmod -R a+rw ${ROOT} /var/{log,run}

COPY --link --chmod=755 deployment/start-container /usr/local/bin/docker-entrypoint
COPY --link --chmod=755 deployment/healthcheck /usr/local/bin/healthcheck
COPY --link deployment/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=5s --interval=2s --timeout=5s --retries=8 CMD healthcheck || exit 1

FROM php:8.4-cli-alpine

LABEL maintainer="Fadogen <contact@fadogen.app>" \
      description="Boîte à outils Laravel Prompts (Alpine)"

WORKDIR /app

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN set -eux; \
    apk add --no-cache \
        git \
        zip \
        unzip \
        libzip-dev \
        openssh-client \
        ca-certificates \
        tini; \
    install-php-extensions \
        @composer \
        pcntl \
        zip; \
    mkdir -p /root/.ssh && \
    chmod 700 /root/.ssh && \
    echo "StrictHostKeyChecking no" > /root/.ssh/config && \
    echo "UserKnownHostsFile /dev/null" >> /root/.ssh/config && \
    chmod 600 /root/.ssh/config && \
    rm -rf /var/cache/apk/*

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --no-progress

COPY . .

RUN composer dump-autoload --optimize && \
    chmod +x /app/entrypoint.sh && \
    chmod -R 775 storage bootstrap/cache && \
    rm -rf /root/.composer/cache

ENTRYPOINT ["/sbin/tini", "--", "/app/entrypoint.sh"]
CMD ["php", "artisan", "list"]

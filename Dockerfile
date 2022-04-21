FROM php:8.1-cli as dev

ENV COMPOSER_MEMORY_LIMIT=-1

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Very convenient PHP extensions installer: https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN mkdir /.composer \
    && chown 1000:1000 /.composer

RUN apt-get update && apt-get install -y \
    git \
    zip

RUN install-php-extensions \
    intl \
    zip \
    pdo_pgsql \
    xdebug

# Install docker, required for running demo
RUN apt-get update && apt-get install -y \
      ca-certificates \
      curl \
      gnupg \
      lsb-release \
  && curl -fsSL https://download.docker.com/linux/$(. /etc/os-release; echo "$ID")/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg \
  && echo \
       "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/$(. /etc/os-release; echo "$ID") \
       $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null \
  && apt-get update && apt-get -y install \
      docker-ce-cli

COPY .docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY .docker/wait-for-it.sh /usr/local/bin/wait-for-it
RUN chmod +x /usr/local/bin/wait-for-it

COPY .docker/docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

RUN mkdir /docker-entrypoint.d/
COPY .docker/entrypoints/*.sh /docker-entrypoint.d/
RUN chmod +x /docker-entrypoint.d/*.sh

USER 1000:1000



FROM dev as prod-composer

ENV APP_ENV="prod"
ENV APP_DEBUG=0

USER root

# Unload xdebug extension by deleting config
RUN rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN mkdir -p /peon/var/cache && chown -R 1000:1000 /peon

USER 1000:1000
WORKDIR /peon

# Intentionally split into multiple steps to leverage docker layer caching
COPY --chown=1000:1000 composer.json composer.lock symfony.lock ./

RUN composer install --no-interaction --no-scripts



FROM node:14 as js-builder

WORKDIR /build

# We need /vendor here
COPY --from=prod-composer /peon .

# Install npm packages
COPY package.json yarn.lock webpack.config.js ./
RUN yarn install

# Production yarn build
COPY ./assets ./assets

RUN yarn run build



FROM prod-composer as prod

# Copy js build
COPY --chown=1000:1000 --from=js-builder /build .

# Copy application source code
COPY --chown=1000:1000 . .

# Need to run again to trigger scripts with application code present
RUN composer install --no-interaction

# Provide composer.phar for subsequent build stages
FROM composer:latest as composer

COPY ./ /app

#########
#########
#########
FROM node:14.21.2-alpine as node

COPY --from=composer /app /app

RUN apk add python3 make g++

WORKDIR /app

RUN touch inc/config.ini
RUN yarn global add grunt-cli
RUN yarn install
RUN grunt deploy

#########
#########
#########
FROM php:7.4.26-apache-buster as runtime

ENV APP_ROOT /var/www/html

ENV STORAGE_PATH $APP_ROOT/storage
VOLUME $STORAGE_PATH

# Fetch composer.phar from composer stage
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY --from=node --chown=www-data:www-data /app $APP_ROOT

WORKDIR $APP_ROOT

RUN apt-get update

RUN apt-get install -y ca-certificates curl gnupg \
  && mkdir -p /etc/apt/keyrings \
  && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
  && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_18.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
  && apt-get update \
  && apt-get install nodejs -y

RUN apt-get install -y libzip-dev gnupg2 screen

RUN apt-get update \
     && apt-get install -y default-mysql-client

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install \
    pcntl \
    pdo_mysql \
    zip

# Set custom php.ini settings
RUN echo 'post_max_size = 512M' >> /usr/local/etc/php/conf.d/app-php.ini && \
    echo 'upload_max_filesize = 256M' >> /usr/local/etc/php/conf.d/app-php.ini && \
    echo 'memory_limit = 1G' >> /usr/local/etc/php/conf.d/app-php.ini

USER www-data
RUN composer install

USER root
RUN a2enmod rewrite filter deflate headers expires proxy_http ssl

RUN cd ${APP_ROOT}/nodejs/ \
  && npm install \
  && npm install sse-channel

# For supporting Matecat's forking mechanism to provision and manage background tasks.
# lib/Utils/TaskRunner/TaskManager.php#287
RUN ln -s /usr/local/bin/php /usr/bin/php

RUN apt-get install -y supervisor
RUN apt-get clean

COPY ./docker/entrypoint.sh /
RUN chmod u+x /entrypoint.sh

COPY ./docker/run.sh /
RUN chmod u+x /run.sh


ENTRYPOINT [ "/entrypoint.sh" ]

CMD [ "/run.sh" ]
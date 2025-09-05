FROM php:8.2-fpm-alpine3.17

ARG PHALCON_VERSION=5.9.3
ARG CACHE_BUST=1

# Установка Phalcon и базовых зависимостей
RUN set -xe && \
        cd / && \
        docker-php-source extract && \
        apk add --no-cache --virtual .build-deps \
            $PHPIZE_DEPS \
            pcre-dev \
        && \
        curl -sSLO https://github.com/phalcon/cphalcon/archive/v${PHALCON_VERSION}.tar.gz && \
        tar xzf /v${PHALCON_VERSION}.tar.gz && \
        docker-php-ext-install -j $(getconf _NPROCESSORS_ONLN) \
            /cphalcon-${PHALCON_VERSION}/build/phalcon \
        && \
        pecl install apcu && \
        docker-php-ext-enable apcu && \
        rm -r \
            /v${PHALCON_VERSION}.tar.gz \
            /cphalcon-${PHALCON_VERSION} \
        && \
        docker-php-source delete && \
        apk del .build-deps

# Установка базовых PHP расширений
RUN docker-php-ext-install opcache

# Установка Composer
RUN apk add --no-cache curl git unzip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установка дополнительных инструментов для разработки
RUN apk add --no-cache nginx linux-headers $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug

# Настройки PHP для разработки
RUN { \
  echo "display_errors=1"; \
  echo "error_reporting=E_ALL"; \
  echo "opcache.enable=0"; \
  echo "xdebug.mode=debug"; \
  echo "xdebug.start_with_request=trigger"; \
  echo "xdebug.discover_client_host=1"; \
  echo "xdebug.client_host=host.docker.internal"; \
  echo "apc.enable_cli=1"; \
} > /usr/local/etc/php/conf.d/dev.ini

# Настройка nginx
COPY config/nginx.conf /etc/nginx/nginx.conf

WORKDIR /local

# Копируем composer файлы и устанавливаем зависимости
COPY composer.json composer.lock ./
RUN composer dump-autoload || true

COPY phpunit.xml /local/phpunit.xml
COPY src/ /local/src/
COPY tests/ /local/tests/
RUN echo "Cache bust: $CACHE_BUST"
    
# Создаем точку монтирования для локальных данных
RUN mkdir -p /local/data
# Директория для логов
RUN mkdir -p /local/logs && chown -R www-data:www-data /local/logs

EXPOSE 8080
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
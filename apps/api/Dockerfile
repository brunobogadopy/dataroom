FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts
COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM php:8.4-cli-alpine
RUN apk add --no-cache libpq-dev icu-dev oniguruma-dev linux-headers \
    && docker-php-ext-install pdo_pgsql intl pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis
WORKDIR /app
COPY --from=vendor /app /app
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
EXPOSE 8080
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]

FROM dunglas/frankenphp:1.13.2-php8.5.12-alpine

WORKDIR /app

RUN install-php-extensions \
    pdo_pgsql \
    bcmath \
    zip \
    intl

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

RUN composer dump-autoload --optimize \
    && chmod +x docker/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

CMD ["docker/start.sh"]

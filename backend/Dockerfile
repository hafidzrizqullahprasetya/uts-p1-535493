# Base image dengan versi spesifik yang jelas (bukan :latest)
FROM php:8.4-cli-alpine

# Set working directory
WORKDIR /var/www/html

# Install dependensi sistem dan ekstensi PHP yang dibutuhkan Laravel
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    sqlite-dev \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    mbstring \
    xml \
    zip \
    bcmath

# Salin binary Composer resmi versi 2.8
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Optimasi layer caching: Salin composer.json dan composer.lock terlebih dahulu
COPY composer.json composer.lock ./

# Pasang dependensi composer sebelum menyalin seluruh kode sumber
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Salin seluruh sisa kode aplikasi
COPY . .

# Generate autoload teroptimasi dan setup permission
RUN composer dump-autoload --optimize \
    && cp -n .env.example .env || true \
    && touch database/database.sqlite \
    && php artisan key:generate --force \
    && chown -R www-data:www-data storage bootstrap/cache database

# Expose port layanan backend
EXPOSE 8000

# Jalankan server bawaan Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

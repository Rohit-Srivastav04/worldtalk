FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev \
    && docker-php-ext-install pdo pdo_mysql pcntl sockets \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --no-dev || composer install --no-interaction --prefer-dist

COPY . .
RUN mkdir -p storage/logs storage/cache public/uploads \
    && chmod -R 775 storage public/uploads

EXPOSE 8080 2346
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]

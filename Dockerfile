FROM php:8.4-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www

COPY composer.json composer.lock ./
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY artisan ./
# COPY .env .env

# После этого копируем остальной код
COPY . .
ENV ENVIRONMENT_OVERRIDES=true
# Установим зависимости
RUN composer install


# Даем права на storage и bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

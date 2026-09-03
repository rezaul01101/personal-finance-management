# =====================================================
# Base: system packages, PHP extensions, Node.js, Composer
# =====================================================

FROM php:8.4-fpm AS base

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        gd \
        zip \
        exif

# Node.js 22
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm --version \
    && node --version

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]


# =====================================================
# Dev: code arrives via bind mount, nothing baked in
# =====================================================

FROM base AS dev

RUN mkdir -p \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

USER www-data

CMD ["php-fpm"]


# =====================================================
# Vendor/asset build: bakes the app code, dependencies,
# and compiled frontend assets into the image
# =====================================================

FROM base AS vendor-build

COPY . .

# storage/** is excluded by .dockerignore (it holds runtime state, not
# code), but `artisan` needs these directories to exist to boot at all
# -- including for the wayfinder:generate call that `npm run build` below
# triggers -- so they must exist before any artisan invocation here.
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN php -r "file_exists('.env') || copy('.env.example', '.env');" \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan key:generate --ansi \
    && npm ci \
    && npm run build \
    && rm -rf node_modules


# =====================================================
# Nginx: static assets copied from the built image, no
# runtime dependency on the app container's filesystem
# =====================================================

FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=vendor-build /var/www/html/public /var/www/html/public


# =====================================================
# App: PHP-FPM serving the baked-in application
# =====================================================

FROM vendor-build AS app

RUN mkdir -p \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

USER www-data

CMD ["php-fpm"]

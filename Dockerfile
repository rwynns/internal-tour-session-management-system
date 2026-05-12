# ── Single-stage production image ────────────────────────────────────────────
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    nginx \
    nodejs \
    npm \
    oniguruma-dev \
    unzip \
    zip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        bcmath \
        gd \
        mbstring \
        opcache \
        pdo \
        pdo_mysql \
        pcntl \
        zip

# PHP opcache config for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies (production only)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Install Node dependencies and build frontend (needs PHP for wayfinder plugin)
RUN npm install && npm run build && rm -rf node_modules

# Cache routes and views
RUN php artisan route:cache \
    && php artisan view:cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx config
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

ENV PORT=80

CMD ["/start.sh"]

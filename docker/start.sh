#!/bin/sh
set -e

# Use PORT from Railway, default to 80
PORT="${PORT:-80}"

# Replace port in nginx config
sed -i "s/listen 80/listen ${PORT}/" /etc/nginx/http.d/default.conf

# Cache Laravel config (needs env vars available at runtime)
php /var/www/html/artisan config:cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"

#!/bin/bash
set -e

# Generate APP_KEY if not present
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "Generating Laravel APP_KEY..."
    php artisan key:generate --no-interaction
fi

# Storage link
if [ ! -L "public/storage" ]; then
    php artisan storage:link || true
fi

# If arguments are passed, execute them instead
if [ $# -gt 0 ]; then
    exec "$@"
fi

echo "Starting PHP-FPM and Nginx..."
php-fpm -D
exec nginx -g "daemon off;"

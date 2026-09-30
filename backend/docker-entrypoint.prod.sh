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

# Ensure PHP upload settings are sufficient for CSV imports
mkdir -p /usr/local/etc/php/conf.d
cat <<EOF > /usr/local/etc/php/conf.d/uploads.ini
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 512M
EOF

echo "Starting PHP-FPM and Nginx..."
php-fpm -D
exec nginx -g "daemon off;"

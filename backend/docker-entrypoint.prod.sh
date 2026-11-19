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
mkdir -p /usr/local/etc/php/conf.d /usr/local/etc/php-fpm.d
cat <<EOF > /usr/local/etc/php/conf.d/uploads.ini
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 512M
max_execution_time = 120
EOF

# Tune OPcache for Laravel + Filament production workloads
cat <<EOF > /usr/local/etc/php/conf.d/zz-opcache.ini
opcache.enable=1
opcache.memory_consumption=${PHP_OPCACHE_MEMORY:-256}
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=30000
opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS:-1}
opcache.revalidate_freq=${PHP_OPCACHE_REVALIDATE_FREQ:-10}
EOF

# Tune PHP-FPM pool for concurrent multi-branch traffic (replaces default pm.max_children=5)
cat <<EOF > /usr/local/etc/php-fpm.d/zz-sm-tuning.conf
[www]
pm = dynamic
pm.max_children = ${PHP_FPM_MAX_CHILDREN:-50}
pm.start_servers = ${PHP_FPM_START_SERVERS:-10}
pm.min_spare_servers = ${PHP_FPM_MIN_SPARE_SERVERS:-6}
pm.max_spare_servers = ${PHP_FPM_MAX_SPARE_SERVERS:-20}
pm.max_requests = ${PHP_FPM_MAX_REQUESTS:-500}
EOF

echo "Starting PHP-FPM and Nginx..."
php-fpm -D
exec nginx -g "daemon off;"

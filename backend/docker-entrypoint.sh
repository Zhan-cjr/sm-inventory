#!/bin/bash
set -e

# Copy .env.docker if .env doesn't exist
if [ ! -f .env ]; then
    if [ -f .env.docker ]; then
        cp .env.docker .env
    elif [ -f .env.example ]; then
        cp .env.example .env
    fi
fi

# Install dependencies if vendor is missing
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Generate APP_KEY if not present
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "Generating Laravel APP_KEY..."
    php artisan key:generate --no-interaction
fi

# Storage link
if [ ! -L "public/storage" ]; then
    php artisan storage:link || true
fi

echo "Backend ready!"
exec "$@"

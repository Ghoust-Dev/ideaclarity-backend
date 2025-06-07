#!/bin/bash

# Make sure we're in the right directory
cd /app

# Clear any existing cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

# Cache configuration for production
php artisan config:cache

# Try to run migrations (but don't fail if database is not ready)
echo "Attempting to run migrations..."
php artisan migrate --force || echo "Migration failed, continuing..."

# Start the server
echo "Starting PHP server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT 
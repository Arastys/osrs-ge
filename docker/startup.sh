#!/bin/bash

# Copy .env example if .env doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Install Dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Generate Key
php artisan key:generate

# Build frontend assets
npm install
npm run build

# Run migrations
php artisan migrate --force

# Start PHP-FPM (this corresponds to the CMD in the Dockerfile if set, or we can override)
# Since the Dockerfile doesn't define a CMD, php-fpm is the default for the image.
# We often use this script as a clearer way to bootstrap dev environments.
# For this setup, we'll let the container run, this is just a helper reference.

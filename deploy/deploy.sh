#!/bin/bash
# ==============================================================================
# Application Deploy / Update Script
# Usage: ./deploy/deploy.sh
# ==============================================================================

set -e

echo "🚀 Deploying latest updates..."

# Activate Maintenance Mode
php artisan down || true

# Pull latest code from Git
git pull origin main

# Install composer dependencies
composer install --no-dev --prefer-dist --optimize-autoloader

# Install npm dependencies and build production assets
npm install
npm run build

# Run database migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Bring Application out of Maintenance Mode
php artisan up

echo "✅ Deployment finished successfully!"


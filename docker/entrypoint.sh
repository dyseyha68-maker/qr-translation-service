#!/bin/sh
set -e

# Substitute PORT into Nginx config
PORT="${PORT:-80}"
echo "🔧 Configuring Nginx to listen on port ${PORT}..."
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/http.d/default.conf

# Ensure storage directories exist and have proper permissions
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php artisan storage:link || true

# Ensure target database exists in MySQL/TiDB before running migrations
if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ]; then
    echo "🔍 Verifying database '${DB_DATABASE}' on ${DB_HOST}..."
    php -r "
        \$host = getenv('DB_HOST');
        \$port = getenv('DB_PORT') ?: 3306;
        \$user = getenv('DB_USERNAME');
        \$pass = getenv('DB_PASSWORD');
        \$db   = getenv('DB_DATABASE');
        try {
            \$options = [
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];
            \$ca = getenv('MYSQL_ATTR_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt';
            if (file_exists(\$ca)) {
                \$options[PDO::MYSQL_ATTR_SSL_CA] = \$ca;
            }
            \$pdo = new PDO(\"mysql:host=\$host;port=\$port\", \$user, \$pass, \$options);
            \$pdo->exec(\"CREATE DATABASE IF NOT EXISTS \`\$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\");
            echo \"✅ Database '\$db' is confirmed and ready.\n\";
        } catch (Exception \$e) {
            echo \"⚠️ Database pre-check note: \" . \$e->getMessage() . \"\n\";
        }
    " || true
fi

# Run database migrations if DB_HOST is set and RUN_MIGRATIONS=true
if [ -n "$DB_HOST" ] && [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🗄️ Running database migrations..."
    php artisan migrate --force || echo "⚠️ Migration skipped or failed to connect."

    if [ "$RUN_SEEDERS" = "true" ]; then
        echo "🌱 Seeding default database users and roles..."
        php artisan db:seed --force || echo "⚠️ Seeding skipped or already populated."
    fi
fi

# Optimize Laravel caching if APP_KEY is set
if [ -n "$APP_KEY" ]; then
    echo "⚡ Optimizing Laravel caches..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Final permissions enforcement for storage and bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "🚀 Starting Supervisor (Nginx + PHP-FPM)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

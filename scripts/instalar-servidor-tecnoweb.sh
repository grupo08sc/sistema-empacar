\
#!/usr/bin/env bash
set -e
cd "$(dirname "$0")/.."
composer config audit.block-insecure false
composer update -W --no-security-blocking --no-scripts
composer dump-autoload --no-scripts
cp .env.tecnoweb.example .env
php artisan key:generate --force
mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache
php artisan package:discover --ansi || true
php artisan config:clear || true
php artisan view:clear || true
php artisan route:clear || true
npm install
npm run build

\
# Ejecutar desde la raiz del proyecto en PowerShell
composer config audit.block-insecure false
composer update -W --no-security-blocking --no-scripts
composer dump-autoload --no-scripts
Copy-Item .env.local.example .env -Force
New-Item -ItemType File -Path database\database.sqlite -Force | Out-Null
php artisan key:generate
php artisan package:discover --ansi
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan migrate:fresh --seed
npm install
npm run build

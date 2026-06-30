# Sistema EMPACAR - Dependencias compatibles PHP 8.4 / Tecnoweb

Este paquete fue ajustado para trabajar con PHP 8.4.22, Composer 2.10.1 y Node 22.x.

## Cambios aplicados

- `composer.json` actualizado para Laravel `^11.54`.
- `guzzlehttp/guzzle` actualizado de `7.8` fijo a `^7.9`.
- Dependencias dev actualizadas para Laravel 11 actual.
- Se retiró el `composer.lock` viejo porque bloqueaba `nette/schema v1.3.0` y `nette/utils v4.0.4`, incompatibles con PHP 8.4.
- El lock viejo queda guardado como `composer.lock.php83-original.bak` solo como respaldo.
- Se agregaron `.env.local.example` y `.env.tecnoweb.example`.
- Se cambió `CACHE_STORE=file` y `QUEUE_CONNECTION=sync` para evitar errores de tabla `cache` antes de migrar.

## Verificación esperada después de actualizar

Ejecuta:

```bash
composer show laravel/framework guzzlehttp/guzzle nette/schema nette/utils
```

Debe quedar parecido a:

```txt
laravel/framework v11.54.0
guzzlehttp/guzzle 7.9.x o superior dentro de 7.x
nette/schema v1.3.5 o superior
nette/utils v4.1.x
```

## Instalación local recomendada

En PowerShell, dentro de la raíz del proyecto:

```powershell
composer config audit.block-insecure false
composer update -W --no-security-blocking --no-scripts
composer dump-autoload --no-scripts
copy .env.local.example .env
New-Item -ItemType File -Path database\database.sqlite -Force
php artisan key:generate
php artisan package:discover --ansi
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

En otra terminal:

```powershell
npm run dev
```

## Instalación en Tecnoweb

```bash
composer config audit.block-insecure false
composer update -W --no-security-blocking --no-scripts
composer dump-autoload --no-scripts
cp .env.tecnoweb.example .env
php artisan key:generate --force
mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache
npm install
npm run build
```

Luego publicar `public/` en `/home/grupo08sc/proyecto2` y dejar el Laravel completo en `/home/grupo08sc/proyecto2_app`.

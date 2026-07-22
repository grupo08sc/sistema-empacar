# EMPACAR S.A. - EMPACAR S.A. con Ventas en Cuotas

Proyecto Laravel 11 + Inertia + Vue 3 adaptado para gestión comercial-administrativa.

## Estado de esta versión

Esta es una versión **base cero**. El sistema inicia limpio para que el administrador cargue la información desde el panel.

Al ejecutar:

```bash
php artisan migrate:fresh --seed
```

solo se crean:

- 1 empresa genérica.
- 1 rol: Administrador.
- 1 usuario administrador.
- privilegios completos para el administrador.
- contadores técnicos del sistema.

No se cargan clientes, productos, proveedores, compras, ventas, cuotas, pagos ni datos de inventario de ejemplo.

## Módulos principales

- Usuarios, roles y privilegios.
- Clientes.
- Productos, categorías e inventario.
- Proveedores, compras y pagos a proveedores.
- Ventas al contado, crédito y modalidad mixta.
- Planes de pago y cuotas.
- Pagos de clientes y PagoFácil.
- Solicitudes internas.
- Reportes financieros.
- Auditoría de acciones críticas.

## Módulos eliminados del proyecto anterior

- Citas.
- Servicios de estética.
- Promociones del modelo anterior.
- Roles Médico y Secretaría.

## Instalación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

## Usuario inicial

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@empacar.local | secret |

## Orden recomendado para cargar datos

1. Revisar o editar datos de empresa.
2. Crear categorías.
3. Crear productos.
4. Crear proveedores.
5. Registrar compras para cargar stock.
6. Crear clientes.
7. Registrar ventas al contado.
8. Registrar ventas en cuotas.
9. Registrar pagos de cuotas.
10. Revisar reportes y auditoría.


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

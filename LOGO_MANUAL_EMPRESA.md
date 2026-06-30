# Carga manual de logo de empresa

Se agregó la opción para cargar manualmente el logo del sistema desde:

**Administración > Configuración**

## Cambios realizados

- Nueva columna `logo_path` en la tabla `empresas`.
- Nueva migración: `2026_06_27_000004_add_logo_path_to_empresas_table.php`.
- Nuevo método `logo()` en `EmpresaController` para cargar imagen.
- Nuevo método `eliminarLogo()` para quitar el logo personalizado.
- El logo se guarda en `storage/app/public/logos`.
- El layout principal ahora muestra dinámicamente el logo de la empresa.
- Si no existe logo personalizado, se usa el logo por defecto de AdminLTE.

## Formatos permitidos

- JPG
- JPEG
- PNG
- WEBP

Tamaño máximo: **2 MB**.

## Comandos necesarios

Después de actualizar el sistema ejecutar:

```bash
php artisan migrate
php artisan storage:link
php artisan optimize:clear
npm run build
```

En desarrollo también puede usarse:

```bash
npm run dev
```

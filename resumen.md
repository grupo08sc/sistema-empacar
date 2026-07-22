# Resumen de cambios

## Archivos modificados

- `app/Http/Controllers/CompraController.php`
- `app/Http/Controllers/SolicitudController.php`
- `app/Http/Controllers/UsuarioController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Models/Compra.php`
- `app/Models/DetalleCompra.php`
- `app/Models/DetalleSolicitud.php`
- `app/Models/Solicitud.php`
- `resources/js/Layouts/AppLayout.vue`
- `resources/js/Pages/Auth/Login.vue`
- `resources/js/Pages/Compra/Index.vue`
- `resources/js/Pages/Landing.vue`
- `resources/js/Pages/Login.vue`
- `resources/js/Pages/Pago/Index.vue`
- `resources/js/Pages/Solicitud/Index.vue`
- `resources/js/Pages/Solicitud/Show.vue`
- `resources/js/Pages/Usuario/Create.vue`
- `resources/js/Pages/Usuario/Edit.vue`
- `resources/js/Pages/Usuario/Index.vue`
- `routes/web.php`
- `storage/logs/laravel.log`

## Archivos nuevos no versionados

- `database/migrations/2026_07_20_203847_add_solicitud_id_to_compras.php`
- `database/migrations/2026_07_20_211526_add_total_to_solicitudes.php`
- `resources/js/.DS_Store`
- `resources/js/Pages/.DS_Store`
- `resources/js/Composables/`

## Cambios principales

1. Backend
   - Se actualizaron los controladores de `Compra`, `Solicitud` y `Usuario` para manejar nuevas reglas de negocio o rutas.
   - Se modificaron los modelos `Compra`, `Solicitud`, `DetalleCompra` y `DetalleSolicitud` para incluir nueva lógica de relaciones o atributos.
   - Se ajustó el middleware `HandleInertiaRequests` para manejar correctamente las solicitudes Inertia.
   - Se añadió soporte a rutas nuevas/ajustadas en `routes/web.php`.

2. Frontend
   - Se cambiaron componentes y páginas principales en Vue: `AppLayout.vue`, `Login.vue`, `Landing.vue`, `Compra/Index.vue`, `Pago/Index.vue`, `Solicitud/Index.vue`, `Solicitud/Show.vue`, `Usuario/Create.vue`, `Usuario/Edit.vue`, `Usuario/Index.vue`.
   - Se mejoró el flujo visual/funcional de las páginas de compra, solicitud y usuario.

3. Base de datos
   - Hay dos migraciones nuevas pendientes de versionar y ejecutar:
     - `add_solicitud_id_to_compras`
     - `add_total_to_solicitudes`

4. Otros
   - El archivo de log `storage/logs/laravel.log` también presenta cambios.
   - Existen archivos de sistema `.DS_Store` y un directorio `resources/js/Composables/` que aún no están versionados.

## Recomendaciones

- Revisar y confirmar los cambios pendientes en `storage/logs/laravel.log` antes de incluirlos en el control de versiones.
- Agregar `.DS_Store` a `.gitignore` si no debe ser parte del repositorio.
- Ejecutar las migraciones nuevas en el entorno de desarrollo si corresponden a los cambios de modelo.

# Flujo de aprobación de compras a proveedor

## Objetivo

Se modificó el módulo de compras para que el usuario **Encargado de Inventario** ya no ejecute compras directamente. Ahora solo registra una **solicitud de compra**, que debe ser aprobada o rechazada por el **Administrador**.

## Funcionamiento nuevo

1. El usuario de inventario ingresa a **Compras y Proveedores → Solicitudes y compras**.
2. Presiona **Solicitar compra**.
3. Registra proveedor, productos, cantidades, precio de compra, descuento y pago inicial propuesto.
4. Al guardar, la compra queda con:
   - `estado_aprobacion = pendiente`
   - `estado = pendiente_aprobacion`
   - `stock_aplicado = false`
5. En ese momento **no aumenta stock** y **no se registra pago al proveedor**.
6. El administrador ingresa al mismo módulo y puede:
   - **Aprobar** la solicitud.
   - **Rechazar** la solicitud indicando un motivo.
7. Cuando el administrador aprueba:
   - la compra cambia a `estado_aprobacion = aprobada`;
   - se registra `aprobado_por` y `fecha_aprobacion`;
   - se actualiza stock de productos;
   - se crea movimiento de inventario tipo entrada;
   - se registra pago inicial al proveedor si corresponde;
   - se registra auditoría.
8. Cuando el administrador rechaza:
   - la compra cambia a `estado_aprobacion = rechazada`;
   - se guarda `motivo_rechazo`;
   - no se afecta stock;
   - no se registra pago.

## Campos agregados en compras

- `estado_aprobacion`
- `solicitado_por`
- `aprobado_por`
- `fecha_solicitud`
- `fecha_aprobacion`
- `metodo_pago_propuesto`
- `referencia_pago_propuesto`
- `motivo_rechazo`
- `observacion_aprobacion`
- `stock_aplicado`

## Rutas nuevas

- `PUT /compras/{compra}/aprobar` → `compras.aprobar`
- `PUT /compras/{compra}/rechazar` → `compras.rechazar`

Ambas rutas usan el permiso:

```txt
Compra → modificar
```

Por defecto, el Administrador tiene este permiso y el Encargado de Inventario no.

## Archivos modificados

- `app/Http/Controllers/CompraController.php`
- `app/Models/Compra.php`
- `app/Http/Controllers/PagoProveedorController.php`
- `app/Http/Controllers/ReporteController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/Pages/Compra/Index.vue`
- `resources/js/Pages/Compra/Show.vue`
- `resources/js/Pages/PagoProveedor/Index.vue`
- `database/migrations/2026_06_28_000010_add_aprobacion_to_compras_table.php`

## Comandos necesarios

Si el sistema ya está instalado:

```bash
php artisan migrate
php artisan optimize:clear
npm run build
```

En desarrollo:

```bash
php artisan migrate
php artisan optimize:clear
npm run dev
```

Si se desea reinstalar desde cero:

```bash
php artisan migrate:fresh --seed
npm run build
```

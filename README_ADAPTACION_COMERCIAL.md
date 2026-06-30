# Adaptación inicial a modelo comercial con pagos en cuotas

## Alcance de esta primera versión

Se inició la migración del sistema desde un dominio de estética/láser hacia un dominio comercial-administrativo. Esta versión no reemplaza todavía todas las pantallas Vue/Inertia; deja preparada la base técnica para continuar con las interfaces.

## Cambios aplicados

### 1. Autenticación

- Se unificó la autenticación sobre `App\Models\User`.
- `App\Models\Usuario` quedó como alias temporal para no romper controladores antiguos.
- Se corrigió el registro de usuario para crear usuarios autenticables y crear un cliente asociado.
- Se agregó compatibilidad con el atributo `name` de Laravel/Breeze usando internamente `nombre`.

### 2. Modelo comercial

Se agregaron modelos y migración para:

- Departamentos.
- Proveedores.
- Compras.
- Detalle de compra.
- Pagos a proveedores.
- Solicitudes.
- Detalle de solicitud.
- Tipos de acción.
- Acciones.
- Transacciones PagoFácil.

### 3. Ventas y cuotas

Se fortaleció el flujo:

```text
VENTA -> DETALLE_VENTA -> PLAN_PAGO -> CUOTAS -> PAGOS -> PAGOFACIL_TRANSACCIONES
```

Ahora la venta puede manejar:

- subtotal;
- descuento;
- total;
- monto pagado;
- saldo;
- tipo de pago: contado, crédito o mixto;
- pago inicial;
- generación de cuotas;
- actualización de stock al vender productos.

### 4. Pagos

Se creó `PagoCuotasService` para registrar pagos de forma transaccional:

- pago directo a venta;
- pago de cuota específica;
- actualización de saldo de cuota;
- actualización de saldo de venta;
- actualización del plan de pago;
- prevención básica de transacciones duplicadas.

### 5. PagoFácil

Se corrigió el monto de QR. Ya no queda fijo en `0.10`; ahora usa:

- saldo de la venta; o
- saldo de la cuota.

También se agregó registro de transacciones en `pagofacil_transacciones`.

El webhook ahora valida:

- formato del pedido;
- venta o cuota existente;
- monto notificado contra monto esperado, si el proveedor lo envía;
- transacción duplicada.

### 6. Compras, proveedores y solicitudes

Se agregaron controladores JSON iniciales:

- `ProveedorController`
- `CompraController`
- `SolicitudController`

Rutas disponibles dentro del middleware `auth`:

```text
/proveedores
/compras
/solicitudes
```

### 7. Correcciones generales

- Se agregó el método `DetalleVentaController::calcularTotal`.
- Se eliminó el `dd()` activo en `PlanPagoController`.
- Se corrigió `tailwind.config.js` para incluir archivos Vue.
- Se quitó el `base` hardcodeado de `vite.config.js`.
- Se ajustó `.env.example` para desarrollo local.

## Comandos recomendados

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

## Pendiente para la siguiente fase

1. Crear pantallas Vue/Inertia para proveedores, compras y solicitudes.
2. Adaptar la pantalla de ventas para capturar pago inicial, número de cuotas y frecuencia.
3. Crear pantalla de cobro de cuotas.
4. Agregar middleware real de permisos por rol.
5. Crear reportes de cuentas por cobrar, cuotas vencidas, compras y pagos a proveedores.
6. Revisar migraciones antiguas que siguen usando `double` para importes.
7. Sustituir completamente nombres y pantallas heredadas de estética.

## Nota técnica

La primera fase deja una base funcional de backend, pero todavía no debe considerarse despliegue final de producción. Falta completar UI, permisos y pruebas integrales de negocio.

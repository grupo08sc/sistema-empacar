# Correcciones aplicadas

## Seguridad de rutas y permisos
- Se reemplazaron rutas protegidas solo con `auth` por rutas con middleware `privilegio:{Modulo},{accion}`.
- Se protegieron operaciones de lectura, creación, modificación y baja lógica por módulo.
- Se movió `/cargar-estilo/{estilo}` dentro del grupo autenticado para evitar errores con usuarios no autenticados.
- Se separaron rutas de privilegios para evitar conflicto de nombres:
  - `privilegio.update`: actualización individual.
  - `privilegios.update`: actualización masiva por rol.
  - `privilegios.destroyByRol`: desactivación de privilegios por rol.
- El middleware `VerificarPrivilegio` permite al rol `Administrador` actuar como superadministrador para evitar bloqueo accidental del sistema.

## Validaciones y asignación masiva
- Se eliminaron actualizaciones inseguras con `$request->all()` en módulos críticos.
- Se agregaron validaciones explícitas en:
  - Categorías.
  - Roles.
  - Usuarios.
  - Empresa.
  - Inventario.
  - Privilegios.
- Se agregó hash seguro de contraseña al actualizar usuarios cuando se envía una nueva contraseña.
- Se impide eliminar el propio usuario autenticado.
- Se impide eliminar roles con usuarios activos asignados.

## Inventario y stock
- Los movimientos manuales de inventario ahora actualizan el stock del producto.
- Las entradas y producto terminado suman stock.
- Las salidas descuentan stock.
- La edición de un movimiento revierte el movimiento anterior y aplica el nuevo dentro de transacción.
- La eliminación lógica de un movimiento revierte su efecto sobre stock.
- Se evita que una salida o reversión deje stock negativo.

## Compras
- Al anular una compra se valida que exista stock suficiente para revertir la entrada.
- Si el stock comprado ya fue utilizado, se bloquea la anulación para evitar stock negativo.

## PagoFácil
- Se reforzó el webhook para validar un secreto interno opcional `PAGOFACIL_WEBHOOK_SECRET`.
- En producción, si no existe secreto, el webhook queda rechazado por seguridad.
- Se valida que exista una transacción PagoFácil generada y pendiente antes de registrar un pago.
- Se comparan monto del webhook, monto del QR generado y saldo esperado.
- Se corrige la búsqueda de transacciones para evitar coincidencias cruzadas por `orWhere`.
- El callback URL puede incluir automáticamente el secreto como parámetro `webhook_secret` cuando está configurado.

## Configuración
- En `.env.example`, `APP_DEBUG` fue cambiado a `false`.
- Se agregó `PAGOFACIL_WEBHOOK_SECRET` al ejemplo de entorno.

## Validación realizada
- Se ejecutó verificación de sintaxis PHP sobre `app`, `routes`, `config` y `database`.
- No se encontraron errores de sintaxis PHP.
- No se ejecutó `php artisan route:list` ni migraciones porque el ZIP no incluye la carpeta `vendor`.

## Pasos recomendados después de descomprimir
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

Para PagoFácil en producción, configurar obligatoriamente:

```env
PAGOFACIL_WEBHOOK_SECRET="un-secreto-largo-y-dificil"
URL_CALLBACK="https://tu-dominio.com/pagofacil/callback"
```

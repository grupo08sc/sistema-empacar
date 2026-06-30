# PagoFácil: consulta automática de estado

Se agregó consulta automática a `/query-transaction` para que la pantalla QR no dependa únicamente del callback.

## Motivo

Cuando el sistema corre en local (`127.0.0.1`), PagoFácil no puede notificar el callback porque esa dirección solo existe en la computadora del desarrollador. Por eso el QR podía pagarse, pero la pantalla seguía en `Esperando pago...`.

## Cambio aplicado

La pantalla de QR consulta cada 3 segundos al backend:

- `/pagofacil/venta/{venta}/estado`
- `/pagofacil/cuota/{cuota}/estado`

El backend consulta PagoFácil con `/query-transaction` usando el `pagofacilTransactionId` guardado. Si el estado devuelto equivale a pagado, el sistema registra el pago real de la venta o cuota, marca la transacción como confirmada y actualiza la pantalla a `¡Pago Realizado!`.

## Prototipo

El QR se mantiene por Bs 0.01 mediante `PAGOFACIL_MONTO_PRUEBA=0.01`, pero el pago interno registrado sigue siendo el monto real pendiente de la venta o cuota.

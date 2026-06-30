# PagoFácil - Estados implementados

El sistema EMPACAR interpreta los estados recibidos por callback y por consulta `/query-transaction`.

## Mapeo aplicado

| Estado PagoFácil | Significado | Estado interno | Acción del sistema |
|---|---|---|---|
| 1 | En proceso / pendiente | `pendiente` | No registra pago. Mantiene la venta/cuota esperando confirmación. |
| 2 | Pagado | `confirmado` | Registra el pago interno por el monto real, actualiza venta/cuota, saldo y bitácora. |
| 4 | Anulado / caducado / no se recibió dinero | `anulado` | No registra pago. Marca la transacción como anulada y permite generar otro QR. |
| 5 | Revisión | `revision` | No registra pago. Marca la transacción como revisión y mantiene la consulta de estado. |

## Particularidad del prototipo

El QR se genera por el monto de prueba configurado en `.env`:

```env
PAGOFACIL_MONTO_PRUEBA=0.01
```

Sin embargo, cuando PagoFácil confirma `Estado = 2`, el sistema registra internamente el monto real de la venta o cuota.

## PaymentNumber único

Para evitar errores por número de pago repetido, cada nuevo QR se genera con sufijo de reintento:

```txt
V6-C4-R20260629235010
```

El sistema reconoce igualmente que pertenece a:

```txt
Venta 6 - Cuota 4
```

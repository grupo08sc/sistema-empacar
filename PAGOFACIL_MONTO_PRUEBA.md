# PagoFácil en modo prototipo: QR por Bs 0.01

Esta versión mantiene el flujo normal de PagoFácil del proyecto, pero fuerza el monto enviado a PagoFácil a **Bs 0.01** para fines de prueba académica.

## Qué se mantiene igual

- Se solicita token a PagoFácil.
- Se genera QR desde la API de PagoFácil.
- Se guarda la transacción en `pagofacil_transacciones`.
- Se usa `paymentNumber` para venta y cuota:
  - `V{idVenta}` para venta.
  - `V{idVenta}-C{idCuota}` para cuota.
- Se recibe callback en `/pagofacil/callback`.
- Se valida `transactionId`, `paymentNumber` y monto del QR.
- Se registra el pago en la tabla `pagos`.
- Se actualiza la venta o cuota correspondiente.
- Se guarda auditoría y webhook recibido.

## Qué cambia

Solo cambia el monto enviado al QR:

```env
PAGOFACIL_MONTO_PRUEBA=0.01
```

Aunque la venta real sea de Bs 1.250 o una cuota sea de Bs 533.33, el QR se genera por Bs 0.01.

## Cómo se registra internamente

Cuando el callback confirma el pago:

- La transacción PagoFácil conserva el monto QR confirmado: Bs 0.01.
- El pago interno del sistema se registra por el monto real pendiente de la venta o cuota.

Ejemplo:

- Venta real: Bs 1.250.00
- QR PagoFácil: Bs 0.01
- Callback confirmado: Bs 0.01
- Pago registrado en el sistema: Bs 1.250.00
- Venta queda pagada.

## Archivos modificados

- `app/Services/PagoFacilService.php`
- `app/Http/Controllers/PagoFacilWebHookController.php`
- `app/Http/Controllers/PlanPagoController.php`
- `config/services.php`
- `.env.example`
- `resources/js/Pages/Venta/ShowQr.vue`
- `resources/js/Pages/Venta/ShowQrVenta.vue`

## Configuración

En `.env` debe existir:

```env
PAGOFACIL_MONTO_PRUEBA=0.01
```

Si deseas cambiar el monto de prueba, modifica ese valor. Para este prototipo académico se recomienda mantenerlo en `0.01`.

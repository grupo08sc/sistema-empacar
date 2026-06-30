# Corrección PagoFácil Callback Estado 2

## Problema detectado

PagoFácil sí estaba llegando al sistema mediante ngrok con una petición:

```txt
POST /pagofacil/callback
```

El cuerpo real recibido tenía esta estructura:

```json
{
  "PedidoID": "V6-C4",
  "Fecha": "2026/06/28",
  "Hora": "23:37:28",
  "Estado": 2,
  "MetodoPago": 34
}
```

Pero el sistema no procesaba correctamente ese callback y la pantalla QR seguía en **Esperando pago**.

## Correcciones aplicadas

1. El callback ahora interpreta `Estado = 2` como pago confirmado.
2. Se acepta el formato real `PedidoID = V{idVenta}-C{idCuota}`.
3. El callback responde siempre con JSON de éxito cuando recibe y procesa correctamente la notificación.
4. Se evita duplicar el parámetro `webhook_secret` en la URL callback.
5. Se agregaron timeouts a las peticiones HTTP hacia PagoFácil para evitar bloqueos.
6. La consulta de estado ya no vuelve a consultar PagoFácil si la transacción local está confirmada o la cuota/venta ya fue pagada.

## Configuración recomendada con ngrok

En `.env`:

```env
APP_URL=https://TU_URL_NGROK.ngrok-free.dev
URL_CALLBACK="https://TU_URL_NGROK.ngrok-free.dev/pagofacil/callback"
PAGOFACIL_WEBHOOK_SECRET="empacar-webhook-2026-seguro"
PAGOFACIL_PAYMENT_METHOD=auto
PAGOFACIL_MONTO_PRUEBA=0.01
```

No agregues `webhook_secret` manualmente a `URL_CALLBACK`; el sistema lo añade automáticamente si `PAGOFACIL_WEBHOOK_SECRET` está configurado.

Después de cambiar `.env` ejecutar:

```bash
php artisan optimize:clear
```

## Resultado esperado

Después de pagar el QR por Bs 0.01:

1. Ngrok debe mostrar `POST /pagofacil/callback`.
2. Laravel debe responder HTTP 200 con JSON.
3. La cuota o venta debe quedar pagada en el sistema.
4. La pantalla debe cambiar de `Esperando pago` a `Pago realizado`.

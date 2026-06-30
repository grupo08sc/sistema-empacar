# PagoFácil: método habilitado automático + monto QR de prueba

Esta versión mantiene la integración por API QR de PagoFácil, pero corrige el error:

```txt
Payment Method ID 4 not found or not enabled for the company.
```

## Cambio aplicado

Antes el sistema enviaba siempre:

```json
"paymentMethod": 4
```

Ahora el sistema funciona así:

1. Se autentica contra `/login` usando `tcTokenService` y `tcTokenSecret`.
2. Consulta `/list-enabled-services`.
3. Obtiene automáticamente el `paymentMethodId` habilitado para el comercio.
4. Usa ese `paymentMethodId` para generar QR en `/generate-qr`.
5. Mantiene el monto QR de prototipo en `0.01`.
6. Conserva internamente el monto real de la venta/cuota para registrar el pago en el sistema.

## Variables `.env`

```env
PAGOFACIL_COMMERCE_ID=""
PAGOFACIL_SERVICE_TOKEN=""
PAGOFACIL_SECRET_TOKEN=""
PAGOFACIL_BASE_URL="https://masterqr.pagofacil.com.bo/api/services/v2"

PAGOFACIL_PAYMENT_METHOD=auto
PAGOFACIL_CACHE_SEGUNDOS=600
PAGOFACIL_MONTO_PRUEBA=0.01

URL_CALLBACK="https://tu-dominio.com/pagofacil/callback"
PAGOFACIL_WEBHOOK_SECRET="empacar-webhook-2026-seguro"
```

## Uso de `PAGOFACIL_PAYMENT_METHOD`

- `auto`: recomendado. Consulta PagoFácil y toma el método habilitado.
- `34`: ejemplo de valor fijo si PagoFácil confirma que ese es tu método habilitado.

## Importante

Si cambias credenciales o método habilitado, ejecuta:

```bash
php artisan optimize:clear
```

El método habilitado se guarda temporalmente en caché para evitar consultar PagoFácil en cada QR.

<?php

namespace App\Services;

use App\Models\PagoFacilTransaccion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PagoFacilService
{
    public function generarQr($venta)
    {
        $token = $this->getAccessToken();
        $paymentMethodId = $this->getPaymentMethodId($token);
        $venta->loadMissing('cliente.user');
        $cliente = $venta->cliente;

        $montoReal = round($venta->saldoActual() > 0 ? $venta->saldoActual() : (float) $venta->total, 2);
        $montoQr = $this->montoPrueba();
        $basePaymentNumber = 'V' . $venta->id;

        if ($existente = $this->transaccionPendiente($venta->id, null, $basePaymentNumber)) {
            return $this->respuestaDesdeTransaccion($existente, $montoReal, $montoQr, $paymentMethodId);
        }

        $paymentNumber = $this->nuevoPaymentNumber($basePaymentNumber);

        $data = [
            'paymentMethod' => $paymentMethodId,
            'clientName' => $cliente->nombreCompleto(),
            'documentType' => 1,
            'documentId' => ($cliente->documento ?? $cliente->id_user ?? $cliente->id) . '',
            'phoneNumber' => $cliente->telefono . '',
            'email' => $cliente->email ?? $cliente->user->email ?? 'sin_correo@admin.com',
            'paymentNumber' => $paymentNumber,
            'amount' => $montoQr,
            'currency' => 2,
            'clientCode' => 'CLI-' . $cliente->id,
            'callbackUrl' => $this->callbackUrl(),
            'orderDetail' => [
                [
                    'serial' => $venta->id,
                    'product' => 'Venta #' . $venta->id . ' - ' . $venta->fecha_venta,
                    'quantity' => 1,
                    'price' => $montoQr,
                    'discount' => 0,
                    'total' => $montoQr,
                ],
            ],
        ];

        $response = $this->postPagoFacil($token, '/generate-qr', $data);

        if ($response->successful() && (int) $response->json('error') === 0) {
            $values = $response->json('values');

            PagoFacilTransaccion::create([
                'id_venta' => $venta->id,
                'transaction_id' => $values['transactionId'] ?? null,
                'payment_number' => $paymentNumber,
                'monto' => $montoQr,
                'estado' => 'generado',
                'qr_url' => $this->extraerUrlQr($values),
                'qr_base64' => $values['qrBase64'] ?? null,
                'request_json' => [
                    'enviado_pagofacil' => $data,
                    'control_sistema' => [
                        'modo' => 'prototipo_monto_prueba',
                        'monto_real_sistema' => $montoReal,
                        'monto_qr_prueba' => $montoQr,
                        'payment_method_id_usado' => $paymentMethodId,
                    ],
                ],
                'response_json' => $values,
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);

            $values['montoRealSistema'] = $montoReal;
            $values['montoQrPrueba'] = $montoQr;
            $values['paymentMethodIdUsado'] = $paymentMethodId;

            return $values;
        }

        $this->olvidarCacheMetodoSiCorresponde($response);

        throw new \Exception('Error al generar el QR de PagoFácil: ' . $this->mensajeError($response));
    }

    public function generarQrParaCuota($cuota)
    {
        $token = $this->getAccessToken();
        $paymentMethodId = $this->getPaymentMethodId($token);
        $cuota->loadMissing('venta.cliente.user');
        $cliente = $cuota->venta->cliente;

        $montoReal = round($cuota->saldo(), 2);
        $montoQr = $this->montoPrueba();
        $basePaymentNumber = 'V' . $cuota->venta->id . '-C' . $cuota->id;

        if ($existente = $this->transaccionPendiente($cuota->venta->id, $cuota->id, $basePaymentNumber)) {
            return $this->respuestaDesdeTransaccion($existente, $montoReal, $montoQr, $paymentMethodId);
        }

        $paymentNumber = $this->nuevoPaymentNumber($basePaymentNumber);

        $data = [
            'paymentMethod' => $paymentMethodId,
            'clientName' => $cliente->nombreCompleto(),
            'documentType' => 1,
            'documentId' => ($cliente->documento ?? $cliente->id_user ?? $cliente->id) . '',
            'phoneNumber' => $cliente->telefono . '',
            'email' => $cliente->email ?? $cliente->user->email ?? 'sin_correo@admin.com',
            'paymentNumber' => $paymentNumber,
            'amount' => $montoQr,
            'currency' => 2,
            'clientCode' => 'CLI-' . $cliente->id,
            'callbackUrl' => $this->callbackUrl(),
            'orderDetail' => [
                [
                    'serial' => $cuota->id,
                    'product' => 'Venta #' . $cuota->venta->id . ' - Cuota #' . $cuota->id,
                    'quantity' => 1,
                    'price' => $montoQr,
                    'discount' => 0,
                    'total' => $montoQr,
                ],
            ],
        ];

        $response = $this->postPagoFacil($token, '/generate-qr', $data);

        if ($response->successful() && (int) $response->json('error') === 0) {
            $values = $response->json('values');

            PagoFacilTransaccion::create([
                'id_venta' => $cuota->venta->id,
                'id_cuota' => $cuota->id,
                'transaction_id' => $values['transactionId'] ?? null,
                'payment_number' => $paymentNumber,
                'monto' => $montoQr,
                'estado' => 'generado',
                'qr_url' => $this->extraerUrlQr($values),
                'qr_base64' => $values['qrBase64'] ?? null,
                'request_json' => [
                    'enviado_pagofacil' => $data,
                    'control_sistema' => [
                        'modo' => 'prototipo_monto_prueba',
                        'monto_real_sistema' => $montoReal,
                        'monto_qr_prueba' => $montoQr,
                        'payment_method_id_usado' => $paymentMethodId,
                    ],
                ],
                'response_json' => $values,
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);

            $values['montoRealSistema'] = $montoReal;
            $values['montoQrPrueba'] = $montoQr;
            $values['paymentMethodIdUsado'] = $paymentMethodId;

            return $values;
        }

        $this->olvidarCacheMetodoSiCorresponde($response);

        throw new \Exception('Error al generar el QR de PagoFácil: ' . $this->mensajeError($response));
    }

    protected function transaccionPendiente(int $ventaId, ?int $cuotaId, string $paymentNumber): ?PagoFacilTransaccion
    {
        return PagoFacilTransaccion::query()
            ->where('id_venta', $ventaId)
            ->when($cuotaId, fn ($query) => $query->where('id_cuota', $cuotaId), fn ($query) => $query->whereNull('id_cuota'))
            ->where(function ($query) use ($paymentNumber) {
                $query->where('payment_number', $paymentNumber)
                    ->orWhere('payment_number', 'like', $paymentNumber . '-R%');
            })
            ->whereIn('estado', ['generado', 'pendiente', 'revision'])
            ->latest('id')
            ->first();
    }


    protected function nuevoPaymentNumber(string $basePaymentNumber): string
    {
        return $basePaymentNumber . '-R' . now()->format('YmdHis');
    }

    protected function respuestaDesdeTransaccion(PagoFacilTransaccion $transaccion, float $montoReal, float $montoQr, int $paymentMethodId): array
    {
        $values = is_array($transaccion->response_json) ? $transaccion->response_json : [];

        if (isset($values['ultima_consulta_estado'])) {
            unset($values['ultima_consulta_estado'], $values['fecha_ultima_consulta']);
        }

        $values['transactionId'] = $transaccion->transaction_id;
        $values['qrBase64'] = $transaccion->qr_base64;
        $values['qrContentUrl'] = $transaccion->qr_url;
        $values['montoRealSistema'] = $montoReal;
        $values['montoQrPrueba'] = $montoQr;
        $values['paymentMethodIdUsado'] = $paymentMethodId;
        $values['transaccionExistente'] = true;

        return $values;
    }

    /**
     * Obtiene el método QR habilitado por PagoFácil.
     *
     * Si PAGOFACIL_PAYMENT_METHOD es numérico, usa ese valor.
     * Si está vacío o vale "auto", consulta /list-enabled-services y toma el primer método habilitado en BOB.
     */
    public function getPaymentMethodId(?string $token = null): int
    {
        $configurado = config('services.pagofacil.payment_method', 'auto');

        if (is_numeric($configurado)) {
            return (int) $configurado;
        }

        return Cache::remember('pagofacil_payment_method_id', $this->cacheSegundos(), function () use ($token) {
            $token = $token ?: $this->getAccessToken();
            $response = $this->postPagoFacil($token, '/list-enabled-services');

            if (! $response->successful() || (int) $response->json('error') !== 0) {
                throw new \Exception('No se pudieron obtener los métodos habilitados de PagoFácil: ' . $this->mensajeError($response));
            }

            $metodos = $response->json('values', []);

            if (! is_array($metodos) || count($metodos) === 0) {
                throw new \Exception('PagoFácil no devolvió métodos QR habilitados para este comercio.');
            }

            $metodoBob = collect($metodos)->first(function ($metodo) {
                $currency = strtoupper((string) ($metodo['currencyName'] ?? $metodo['currency'] ?? ''));
                return $currency === 'BOB' || $currency === 'BS' || $currency === 'BOLIVIANOS';
            });

            $metodo = $metodoBob ?: collect($metodos)->first();
            $id = $metodo['paymentMethodId'] ?? $metodo['payment_method_id'] ?? $metodo['id'] ?? null;

            if (! $id || ! is_numeric($id)) {
                throw new \Exception('La respuesta de PagoFácil no contiene un paymentMethodId válido.');
            }

            return (int) $id;
        });
    }

    public function consultarTransaccion(PagoFacilTransaccion $transaccion): array
    {
        $token = $this->getAccessToken();
        $payload = [];

        if ($transaccion->transaction_id) {
            $payload['pagofacilTransactionId'] = is_numeric($transaccion->transaction_id)
                ? (int) $transaccion->transaction_id
                : $transaccion->transaction_id;
        } else {
            $payload['companyTransactionId'] = $transaccion->payment_number;
        }

        $response = $this->postPagoFacil($token, '/query-transaction', $payload);

        if (! $response->successful() || (int) $response->json('error') !== 0) {
            throw new \Exception('No se pudo consultar el estado de la transacción PagoFácil: ' . $this->mensajeError($response));
        }

        $respuestaJson = $response->json();
        $estadoNormalizado = $this->estadoTransaccion($respuestaJson);

        $datosActualizacion = [
            'response_json' => array_merge($transaccion->response_json ?? [], [
                'ultima_consulta_estado' => $respuestaJson,
                'fecha_ultima_consulta' => now()->toDateTimeString(),
                'estado_normalizado' => $estadoNormalizado,
                'estado_descripcion' => $this->descripcionEstado($respuestaJson),
            ]),
            'fecha_actualizacion' => now(),
        ];

        if ($transaccion->estado !== 'confirmado') {
            $datosActualizacion['estado'] = $estadoNormalizado;
        }

        $transaccion->update($datosActualizacion);

        return $respuestaJson;
    }

    public function pagoConfirmado(array $respuesta): bool
    {
        return $this->estadoTransaccion($respuesta) === 'confirmado';
    }

    public function estadoTransaccion(array $respuesta): string
    {
        $estado = $this->valorEstado($respuesta);

        if (is_numeric($estado)) {
            return match ((int) $estado) {
                1 => 'pendiente',
                2 => 'confirmado',
                4 => 'anulado',
                5 => 'revision',
                default => 'desconocido',
            };
        }

        $normalizado = strtolower(trim((string) $estado));
        $normalizado = str_replace(['ó', 'á', 'é', 'í', 'ú'], ['o', 'a', 'e', 'i', 'u'], $normalizado);

        return match (true) {
            in_array($normalizado, ['2', 'pagado', 'pago realizado', 'paid', 'confirmado', 'confirmed', 'aprobado', 'success', 'successful'], true) => 'confirmado',
            in_array($normalizado, ['1', 'pendiente', 'en proceso', 'procesando', 'pending', 'in process'], true) => 'pendiente',
            in_array($normalizado, ['4', 'anulado', 'cancelado', 'expirado', 'caducado', 'revertido', 'void', 'canceled', 'cancelled', 'expired'], true) => 'anulado',
            in_array($normalizado, ['5', 'revision', 'en revision', 'review'], true) => 'revision',
            default => 'desconocido',
        };
    }

    public function descripcionEstado(array $respuesta): ?string
    {
        return $respuesta['values']['paymentStatusDescription']
            ?? $respuesta['values']['statusDescription']
            ?? $respuesta['values']['EstadoDescripcion']
            ?? $respuesta['paymentStatusDescription']
            ?? $respuesta['EstadoDescripcion']
            ?? null;
    }

    public function mensajeEstado(string $estado): string
    {
        return match ($estado) {
            'pendiente' => 'PagoFácil indica que el pago está pendiente o en proceso.',
            'confirmado' => 'Pago confirmado por PagoFácil.',
            'anulado' => 'PagoFácil indica que el QR fue anulado, caducó o no recibió dinero.',
            'revision' => 'PagoFácil indica que la transacción está en revisión.',
            default => 'Estado PagoFácil no reconocido por el sistema.',
        };
    }

    public function valorEstado(array $respuesta): mixed
    {
        return $respuesta['values']['paymentStatus']
            ?? $respuesta['values']['status']
            ?? $respuesta['values']['Estado']
            ?? $respuesta['values']['estado']
            ?? $respuesta['Estado']
            ?? $respuesta['estado']
            ?? $respuesta['paymentStatus']
            ?? $respuesta['payment_status']
            ?? null;
    }

    public function consultarMetodosHabilitados(): array
    {
        $token = $this->getAccessToken();
        $response = $this->postPagoFacil($token, '/list-enabled-services');

        if (! $response->successful() || (int) $response->json('error') !== 0) {
            throw new \Exception('No se pudieron consultar los métodos habilitados de PagoFácil: ' . $this->mensajeError($response));
        }

        return $response->json('values', []);
    }

    protected function postPagoFacil(string $token, string $endpoint, array $data = [])
    {
        return Http::timeout(10)->retry(1, 500)->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Response-Language' => 'es',
            'Accept' => 'application/json',
        ])->post(rtrim(config('services.pagofacil.base_url'), '/') . $endpoint, $data);
    }

    protected function montoPrueba(): float
    {
        return round((float) config('services.pagofacil.monto_prueba', 0.01), 2);
    }

    public function callbackUrl(): string
    {
        $url = config('services.pagofacil.callback_url') ?: route('pagofacil.callback');
        $secret = config('services.pagofacil.webhook_secret');

        if (! $secret) {
            return $url;
        }

        if (str_contains($url, 'webhook_secret=')) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'webhook_secret=' . urlencode($secret);
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('pagofacil_token', $this->cacheSegundos(), function () {
            $response = Http::timeout(10)->retry(1, 500)->withHeaders([
                'tcTokenService' => config('services.pagofacil.service_token'),
                'tcTokenSecret' => config('services.pagofacil.secret_token'),
                'Response-Language' => 'es',
                'Accept' => 'application/json',
            ])->post(rtrim(config('services.pagofacil.base_url'), '/') . '/login');

            if (! $response->successful() || (int) $response->json('error') !== 0) {
                throw new \Exception('Error de autenticación con PagoFácil: ' . $this->mensajeError($response));
            }

            $token = $response->json('values.accessToken');

            if (! $token) {
                throw new \Exception('PagoFácil no devolvió accessToken. Respuesta: ' . $response->body());
            }

            return $token;
        });
    }

    protected function extraerUrlQr(array $values): ?string
    {
        return $values['qrUrl']
            ?? $values['qrContentUrl']
            ?? $values['checkoutUrl']
            ?? $values['universalUrl']
            ?? $values['deepLink']
            ?? null;
    }

    protected function mensajeError($response): string
    {
        $message = $response->json('message');

        if ($message) {
            return (string) $message;
        }

        return Str::limit($response->body(), 1000);
    }

    protected function olvidarCacheMetodoSiCorresponde($response): void
    {
        $body = strtolower($response->body());

        if (str_contains($body, 'payment method') || str_contains($body, 'método') || str_contains($body, 'metodo')) {
            Cache::forget('pagofacil_payment_method_id');
        }
    }

    protected function cacheSegundos(): int
    {
        return max(60, (int) config('services.pagofacil.cache_segundos', 600));
    }
}

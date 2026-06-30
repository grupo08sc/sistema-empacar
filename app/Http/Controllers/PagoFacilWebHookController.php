<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Pago;
use App\Models\PagoFacilTransaccion;
use App\Models\Venta;
use App\Services\PagoCuotasService;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PagoFacilWebHookController extends Controller
{
    public function callback(Request $request, PagoCuotasService $pagoCuotasService, PagoFacilService $pagoFacilService)
    {
        Log::info('Webhook PagoFácil recibido.', [
            'payload' => $request->except(['token', 'secret']),
            'ip' => $request->ip(),
        ]);

        if (! $this->webhookAutorizado($request)) {
            Log::warning('Webhook PagoFácil rechazado por secreto inválido.', ['ip' => $request->ip()]);

            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'Webhook no autorizado.',
            ], 401);
        }

        $data = $request->all();
        $pedidoID = $this->valor($data, ['PedidoID', 'paymentNumber', 'payment_number', 'PaymentNumber']);

        if (! $pedidoID) {
            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'Webhook sin PedidoID/paymentNumber.',
            ], 422);
        }

        $transactionId = $this->valor($data, ['TransactionId', 'transactionId', 'transaction_id', 'TransaccionID']);
        $montoNotificado = $this->montoNotificado($data);

        if (! $this->estadoEsPagado($data)) {
            $estadoNormalizado = $pagoFacilService->estadoTransaccion($data);
            $this->actualizarTransaccionNoPagada($pedidoID, $transactionId, $montoNotificado, $data, $estadoNormalizado, $pagoFacilService);

            Log::info('Webhook PagoFácil recibido con estado no pagado; se confirma recepción sin aplicar pago.', [
                'pedidoID' => $pedidoID,
                'estado' => $this->valor($data, ['Estado', 'estado', 'paymentStatus', 'payment_status']),
                'estado_normalizado' => $estadoNormalizado,
            ]);

            return $this->respuestaExitosa('Webhook recibido. ' . $pagoFacilService->mensajeEstado($estadoNormalizado));
        }

        try {
            if (preg_match('/^V(\d+)-C(\d+)(?:-.+)?$/', $pedidoID, $m)) {
                return $this->procesarPagoCuota(
                    (int) $m[1],
                    (int) $m[2],
                    $pedidoID,
                    $transactionId,
                    $montoNotificado,
                    $data,
                    $pagoCuotasService
                );
            }

            if (preg_match('/^V(\d+)(?:-.+)?$/', $pedidoID, $m)) {
                return $this->procesarPagoVenta(
                    (int) $m[1],
                    $pedidoID,
                    $transactionId,
                    $montoNotificado,
                    $data,
                    $pagoCuotasService
                );
            }
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error al procesar webhook PagoFácil.', [
                'message' => $e->getMessage(),
                'payload' => $request->except(['token', 'secret']),
            ]);

            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'No se pudo procesar el webhook.',
            ], 500);
        }

        return response()->json([
            'error' => 1,
            'status' => 0,
            'message' => "Formato de PedidoID no reconocido: {$pedidoID}",
        ], 422);
    }

    protected function procesarPagoCuota(
        int $ventaId,
        int $cuotaId,
        string $pedidoID,
        ?string $transactionId,
        ?float $montoNotificado,
        array $data,
        PagoCuotasService $pagoCuotasService
    ) {
        $cuota = Cuota::where('id_venta', $ventaId)->find($cuotaId);

        if (! $cuota) {
            Log::warning('Webhook PagoFácil: cuota no encontrada.', compact('ventaId', 'cuotaId', 'pedidoID'));
            return $this->respuestaExitosa("Webhook recibido, pero la cuota {$cuotaId} no fue encontrada.");
        }

        $transaccion = $this->validarTransaccionGenerada($pedidoID, $transactionId, $montoNotificado);

        if ($transaccion->estado === 'confirmado' || $cuota->estado === 'pagado' || $cuota->saldo() <= 0) {
            return $this->respuestaExitosa("Pago ya registrado para cuota {$cuotaId} de la venta {$ventaId}.");
        }

        try {
            $montoRealSistema = round($cuota->saldo(), 2);
            $montoQrConfirmado = $montoNotificado ?? (float) $transaccion->monto;

            $pago = $pagoCuotasService->registrarPagoVenta($ventaId, [
                'id_cuota' => $cuota->id,
                'monto' => $montoRealSistema,
                'tipo_pago' => 'PagoFacil',
                'referencia' => $pedidoID,
                'transaction_id' => $transactionId,
                'observaciones' => 'Pago confirmado por callback PagoFácil. QR de prototipo cobrado por Bs ' . number_format($montoQrConfirmado, 2, '.', '') . '.',
            ]);

            $this->registrarWebhook($transaccion, $pedidoID, $transactionId, $montoQrConfirmado, $data, $pago->id, $ventaId, $cuota->id, $montoRealSistema);

            return $this->respuestaExitosa("Pago registrado para cuota {$cuotaId} de la venta {$ventaId}.");
        } catch (ValidationException $e) {
            Log::warning('Webhook PagoFácil: pago de cuota no aplicado por validación.', [
                'pedidoID' => $pedidoID,
                'message' => $e->validator->errors()->first(),
            ]);

            return $this->respuestaExitosa('Webhook recibido. ' . $e->validator->errors()->first());
        }
    }

    protected function procesarPagoVenta(
        int $ventaId,
        string $pedidoID,
        ?string $transactionId,
        ?float $montoNotificado,
        array $data,
        PagoCuotasService $pagoCuotasService
    ) {
        $venta = Venta::find($ventaId);

        if (! $venta) {
            Log::warning('Webhook PagoFácil: venta no encontrada.', compact('ventaId', 'pedidoID'));
            return $this->respuestaExitosa("Webhook recibido, pero la venta {$ventaId} no fue encontrada.");
        }

        $transaccion = $this->validarTransaccionGenerada($pedidoID, $transactionId, $montoNotificado);

        if ($transaccion->estado === 'confirmado' || $venta->estado === 'pagado' || $venta->saldoActual() <= 0) {
            return $this->respuestaExitosa("Pago ya registrado para venta {$ventaId}.");
        }

        try {
            $montoRealSistema = round($venta->saldoActual(), 2);

            if ($montoRealSistema <= 0) {
                $montoRealSistema = round((float) $venta->total, 2);
            }

            $montoQrConfirmado = $montoNotificado ?? (float) $transaccion->monto;

            $pago = $pagoCuotasService->registrarPagoVenta($ventaId, [
                'monto' => $montoRealSistema,
                'tipo_pago' => 'PagoFacil',
                'referencia' => $pedidoID,
                'transaction_id' => $transactionId,
                'observaciones' => 'Pago confirmado por callback PagoFácil. QR de prototipo cobrado por Bs ' . number_format($montoQrConfirmado, 2, '.', '') . '.',
            ]);

            $this->registrarWebhook($transaccion, $pedidoID, $transactionId, $montoQrConfirmado, $data, $pago->id, $ventaId, null, $montoRealSistema);

            return $this->respuestaExitosa("Pago registrado para venta {$ventaId}.");
        } catch (ValidationException $e) {
            Log::warning('Webhook PagoFácil: pago de venta no aplicado por validación.', [
                'pedidoID' => $pedidoID,
                'message' => $e->validator->errors()->first(),
            ]);

            return $this->respuestaExitosa('Webhook recibido. ' . $e->validator->errors()->first());
        }
    }

    protected function validarTransaccionGenerada(string $paymentNumber, ?string $transactionId, ?float $monto): PagoFacilTransaccion
    {
        if ($transactionId && Pago::where('transaction_id', $transactionId)->exists()) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Transacción ya procesada anteriormente.',
            ]);
        }

        $transaccion = PagoFacilTransaccion::where('payment_number', $paymentNumber)
            ->when($transactionId, function ($query) use ($transactionId) {
                $query->where(function ($subQuery) use ($transactionId) {
                    $subQuery->whereNull('transaction_id')
                        ->orWhere('transaction_id', $transactionId);
                });
            })
            ->latest('id')
            ->first();

        if (! $transaccion) {
            throw ValidationException::withMessages([
                'paymentNumber' => 'No existe una transacción PagoFácil generada y pendiente para este pedido.',
            ]);
        }

        if ($transactionId && $transaccion->transaction_id && $transaccion->transaction_id !== $transactionId) {
            throw ValidationException::withMessages([
                'transaction_id' => 'El ID de transacción no coincide con el QR generado.',
            ]);
        }

        if ($monto !== null && abs($monto - (float) $transaccion->monto) > 0.01) {
            throw ValidationException::withMessages([
                'monto' => 'El monto notificado no coincide con el monto del QR generado.',
            ]);
        }

        return $transaccion;
    }

    protected function registrarWebhook(
        PagoFacilTransaccion $transaccion,
        string $paymentNumber,
        ?string $transactionId,
        float $montoQrConfirmado,
        array $data,
        int $pagoId,
        int $ventaId,
        ?int $cuotaId,
        ?float $montoRealSistema = null
    ): void {
        $transaccion->fill([
            'id_venta' => $ventaId,
            'id_cuota' => $cuotaId,
            'id_pago' => $pagoId,
            'payment_number' => $paymentNumber,
            'transaction_id' => $transactionId ?? $transaccion->transaction_id,
            'monto' => $montoQrConfirmado,
            'estado' => 'confirmado',
            'webhook_json' => [
                'recibido_pagofacil' => $data,
                'control_sistema' => [
                    'modo' => 'prototipo_monto_prueba',
                    'monto_qr_confirmado' => $montoQrConfirmado,
                    'monto_real_registrado' => $montoRealSistema,
                ],
            ],
            'fecha_actualizacion' => now(),
        ]);

        $transaccion->save();
    }


    protected function actualizarTransaccionNoPagada(
        string $paymentNumber,
        ?string $transactionId,
        ?float $montoNotificado,
        array $data,
        string $estadoNormalizado,
        PagoFacilService $pagoFacilService
    ): void {
        $transaccion = PagoFacilTransaccion::where('payment_number', $paymentNumber)
            ->latest('id')
            ->first();

        if (! $transaccion) {
            Log::warning('Webhook PagoFácil no pagado sin transacción local asociada.', [
                'payment_number' => $paymentNumber,
                'estado_normalizado' => $estadoNormalizado,
            ]);
            return;
        }

        if ($transaccion->estado === 'confirmado') {
            return;
        }

        $webhookJson = $transaccion->webhook_json ?? [];
        $webhookJson['recibido_pagofacil'] = $data;
        $webhookJson['control_sistema'] = array_merge($webhookJson['control_sistema'] ?? [], [
            'modo' => 'prototipo_monto_prueba',
            'estado_normalizado' => $estadoNormalizado,
            'estado_descripcion' => $pagoFacilService->descripcionEstado($data),
            'monto_notificado' => $montoNotificado,
            'actualizado_por' => 'callback_pagofacil',
        ]);

        $transaccion->update([
            'transaction_id' => $transactionId ?? $transaccion->transaction_id,
            'monto' => $montoNotificado ?? $transaccion->monto,
            'estado' => $estadoNormalizado,
            'webhook_json' => $webhookJson,
            'fecha_actualizacion' => now(),
        ]);
    }

    protected function estadoEsPagado(array $data): bool
    {
        $estado = $this->valor($data, ['Estado', 'estado', 'paymentStatus', 'payment_status']);

        if (is_numeric($estado)) {
            return (int) $estado === 2;
        }

        $normalizado = strtolower(trim((string) $estado));

        return in_array($normalizado, [
            '2',
            'pagado',
            'pago realizado',
            'paid',
            'confirmado',
            'confirmed',
            'aprobado',
            'success',
            'successful',
        ], true);
    }

    protected function respuestaExitosa(string $message)
    {
        return response()->json([
            'error' => 0,
            'status' => 1,
            'message' => $message,
            'values' => true,
        ], 200);
    }

    protected function webhookAutorizado(Request $request): bool
    {
        $secret = config('services.pagofacil.webhook_secret');

        if (! $secret) {
            return app()->environment(['local', 'testing']);
        }

        $recibido = $request->header('X-PagoFacil-Webhook-Secret')
            ?? $request->input('webhook_secret')
            ?? $request->query('webhook_secret');

        return is_string($recibido) && hash_equals($secret, $recibido);
    }

    protected function valor(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return $data[$key];
            }
        }

        return null;
    }

    protected function montoNotificado(array $data): ?float
    {
        $monto = $this->valor($data, ['Amount', 'amount', 'Monto', 'monto']);

        return $monto !== null ? round((float) $monto, 2) : null;
    }
}

<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Inventario;
use App\Models\Pago;
use App\Models\PagoFacilTransaccion;
use App\Models\PagoProveedor;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnulacionService
{
    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    public function anularPagoCliente(Pago $pago, ?string $motivo = null): Pago
    {
        return DB::transaction(function () use ($pago, $motivo) {
            $pago = Pago::lockForUpdate()->with(['venta.plan', 'cuota'])->findOrFail($pago->id);

            if ($pago->state !== 'a') {
                throw ValidationException::withMessages([
                    'pago' => 'El pago ya está anulado o inactivo.',
                ]);
            }

            $estadoAnterior = $pago->toArray();
            $monto = round((float) $pago->monto, 2);

            if ($pago->cuota) {
                $cuota = $pago->cuota()->lockForUpdate()->first();
                $nuevoPagado = max(0, round((float) $cuota->monto_pagado - $monto, 2));
                $cuota->update([
                    'monto_pagado' => $nuevoPagado,
                    'fecha_pago' => $nuevoPagado >= (float) $cuota->monto ? $cuota->fecha_pago : null,
                    'estado' => $nuevoPagado >= (float) $cuota->monto ? 'pagado' : 'pendiente',
                    'id_pago' => $nuevoPagado > 0 ? $cuota->id_pago : null,
                ]);
            }

            $pago->update([
                'state' => 'i',
                'estado_pago' => 'pendiente',
                'observaciones' => trim(($pago->observaciones ? $pago->observaciones . "\n" : '') . 'Pago anulado. Motivo: ' . ($motivo ?: 'No especificado')),
            ]);

            if ($pago->venta) {
                $venta = Venta::lockForUpdate()->with('plan')->findOrFail($pago->id_venta);
                $montoPagado = round((float) $venta->pagos()
                    ->where('state', 'a')
                    ->where('estado_pago', 'pagado')
                    ->sum('monto'), 2);
                $saldo = max(0, round((float) $venta->total - $montoPagado, 2));

                $venta->update([
                    'monto_pagado' => $montoPagado,
                    'saldo' => $saldo,
                    'estado' => $saldo <= 0 ? 'pagado' : ($montoPagado > 0 ? 'parcial' : 'pendiente'),
                ]);

                if ($venta->plan) {
                    $venta->plan->update([
                        'saldo_restante' => $saldo,
                        'estado' => $saldo <= 0 ? 'finalizado' : 'en_curso',
                    ]);
                }
            }

            if ($pago->transaction_id) {
                PagoFacilTransaccion::where('transaction_id', $pago->transaction_id)
                    ->update(['estado' => 'anulado', 'fecha_actualizacion' => now()]);
            }

            $this->auditoria->registrar(
                'Pago',
                'anular_pago_cliente',
                $pago,
                'Anulación segura de pago de cliente. Motivo: ' . ($motivo ?: 'No especificado'),
                $estadoAnterior,
                $pago->fresh()->toArray(),
                'warning'
            );

            return $pago->fresh(['venta', 'cuota', 'cliente']);
        });
    }

    public function anularVenta(Venta $venta, ?string $motivo = null): Venta
    {
        return DB::transaction(function () use ($venta, $motivo) {
            $venta = Venta::lockForUpdate()->with(['detalles.producto', 'pagos', 'cuotas', 'plan'])->findOrFail($venta->id);

            if ($venta->state !== 'a') {
                throw ValidationException::withMessages([
                    'venta' => 'La venta ya está anulada o inactiva.',
                ]);
            }

            $estadoAnterior = $venta->toArray();

            foreach ($venta->detalles as $detalle) {
                if ($detalle->state === 'a' && $detalle->producto) {
                    $detalle->producto->increment('stock', (int) $detalle->cantidad);

                    Inventario::create([
                        'id_producto' => $detalle->producto->id,
                        'cantidad' => (int) $detalle->cantidad,
                        'fecha' => now()->toDateString(),
                        'tipo' => 'entrada',
                        'descripcion' => 'Reversión automática por anulación de venta #' . $venta->id,
                        'state' => 'a',
                    ]);
                }

                $detalle->update(['state' => 'i']);
            }

            foreach ($venta->pagos as $pago) {
                if ($pago->state === 'a') {
                    $pago->update([
                        'state' => 'i',
                        'estado_pago' => 'pendiente',
                        'observaciones' => trim(($pago->observaciones ? $pago->observaciones . "\n" : '') . 'Pago anulado por anulación de venta. Motivo: ' . ($motivo ?: 'No especificado')),
                    ]);
                }
            }

            foreach ($venta->cuotas as $cuota) {
                $cuota->update([
                    'monto_pagado' => 0,
                    'fecha_pago' => null,
                    'estado' => 'pendiente',
                    'id_pago' => null,
                    'state' => 'i',
                ]);
            }

            if ($venta->plan) {
                $venta->plan->update([
                    'saldo_restante' => 0,
                    'estado' => 'pendiente',
                    'state' => 'i',
                ]);
            }

            PagoFacilTransaccion::where('id_venta', $venta->id)
                ->whereIn('estado', ['generado', 'pendiente'])
                ->update(['estado' => 'anulado', 'fecha_actualizacion' => now()]);

            $venta->update([
                'state' => 'i',
                'estado' => 'anulado',
                'monto_pagado' => 0,
                'saldo' => 0,
                'observaciones' => trim(($venta->observaciones ? $venta->observaciones . "\n" : '') . 'Venta anulada. Motivo: ' . ($motivo ?: 'No especificado')),
            ]);

            $this->auditoria->registrar(
                'Venta',
                'anular_venta',
                $venta,
                'Anulación segura de venta con reversión de stock, cuotas y pagos. Motivo: ' . ($motivo ?: 'No especificado'),
                $estadoAnterior,
                $venta->fresh(['detalles', 'pagos', 'cuotas', 'plan'])->toArray(),
                'warning'
            );

            return $venta->fresh(['cliente', 'detalles.producto', 'pagos', 'cuotas', 'plan']);
        });
    }

    public function anularPagoProveedor(PagoProveedor $pago, ?string $motivo = null): PagoProveedor
    {
        return DB::transaction(function () use ($pago, $motivo) {
            $pago = PagoProveedor::lockForUpdate()->with('compra')->findOrFail($pago->id);

            if ($pago->state !== 'a') {
                throw ValidationException::withMessages([
                    'pago' => 'El pago a proveedor ya está anulado o inactivo.',
                ]);
            }

            $estadoAnterior = $pago->toArray();

            if ($pago->compra && $pago->estado === 'confirmado') {
                $compra = Compra::lockForUpdate()->findOrFail($pago->id_compra);
                $nuevoPagado = max(0, round((float) $compra->monto_pagado - (float) $pago->monto, 2));
                $nuevoSaldo = max(0, round((float) $compra->total - $nuevoPagado, 2));

                $compra->update([
                    'monto_pagado' => $nuevoPagado,
                    'saldo' => $nuevoSaldo,
                    'estado' => $nuevoSaldo <= 0 ? 'pagado' : ($nuevoPagado > 0 ? 'parcial' : 'pendiente'),
                ]);
            }

            $pago->update([
                'state' => 'i',
                'estado' => 'anulado',
                'observaciones' => trim(($pago->observaciones ? $pago->observaciones . "\n" : '') . 'Pago a proveedor anulado. Motivo: ' . ($motivo ?: 'No especificado')),
            ]);

            $this->auditoria->registrar(
                'PagoProveedor',
                'anular_pago_proveedor',
                $pago,
                'Anulación segura de pago a proveedor. Motivo: ' . ($motivo ?: 'No especificado'),
                $estadoAnterior,
                $pago->fresh()->toArray(),
                'warning'
            );

            return $pago->fresh(['proveedor', 'compra', 'usuario']);
        });
    }
}

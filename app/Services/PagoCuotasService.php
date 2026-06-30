<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoCuotasService
{
    public function registrarPagoVenta(int $ventaId, array $data): Pago
    {
        return DB::transaction(function () use ($ventaId, $data) {
            $venta = Venta::lockForUpdate()->with('plan')->findOrFail($ventaId);
            $cuota = null;

            if (! empty($data['id_cuota'])) {
                $cuota = Cuota::lockForUpdate()->where('id_venta', $venta->id)->findOrFail($data['id_cuota']);
            }

            $monto = round((float) ($data['monto'] ?? 0), 2);
            if ($monto <= 0) {
                throw ValidationException::withMessages(['monto' => 'El monto del pago debe ser mayor a cero.']);
            }

            if ($cuota && $monto > $cuota->saldo() + 0.01) {
                throw ValidationException::withMessages(['monto' => 'El monto no puede ser mayor al saldo de la cuota.']);
            }

            if (! $cuota && $monto > $venta->saldoActual() + 0.01) {
                throw ValidationException::withMessages(['monto' => 'El monto no puede ser mayor al saldo de la venta.']);
            }

            if (! empty($data['transaction_id']) && Pago::where('transaction_id', $data['transaction_id'])->exists()) {
                throw ValidationException::withMessages(['transaction_id' => 'La transacción ya fue registrada anteriormente.']);
            }

            $pago = Pago::create([
                'id_venta' => $venta->id,
                'id_cliente' => $venta->id_cliente,
                'id_plan' => $cuota?->id_plan_pago ?? $venta->plan?->id,
                'id_cuota' => $cuota?->id,
                'monto' => $monto,
                'tipo_pago' => $data['tipo_pago'] ?? $data['metodo_pago'] ?? 'efectivo',
                'estado_pago' => $data['estado_pago'] ?? 'pagado',
                'fecha_pago' => $data['fecha_pago'] ?? now()->toDateString(),
                'referencia' => $data['referencia'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'state' => 'a',
            ]);

            if ($cuota) {
                $nuevoPagado = round((float) $cuota->monto_pagado + $monto, 2);
                $cuota->update([
                    'monto_pagado' => $nuevoPagado,
                    'fecha_pago' => $nuevoPagado >= (float) $cuota->monto ? now()->toDateString() : $cuota->fecha_pago,
                    'estado' => $nuevoPagado >= (float) $cuota->monto ? 'pagado' : 'pendiente',
                    'id_pago' => $pago->id,
                ]);
            }

            $montoPagadoVenta = round((float) $venta->pagos()->where('estado_pago', 'pagado')->sum('monto'), 2);
            $saldoVenta = max(0, round((float) $venta->total - $montoPagadoVenta, 2));

            $venta->update([
                'monto_pagado' => $montoPagadoVenta,
                'saldo' => $saldoVenta,
                'estado' => $saldoVenta <= 0 ? 'pagado' : ($montoPagadoVenta > 0 ? 'parcial' : 'pendiente'),
            ]);

            if ($venta->plan) {
                $venta->plan->update([
                    'saldo_restante' => $saldoVenta,
                    'estado' => $saldoVenta <= 0 ? 'finalizado' : 'en_curso',
                ]);
            }

            app(AuditoriaService::class)->registrar(
                'Pago',
                'registrar_pago_cliente',
                $pago,
                'Registro de pago de cliente por Bs ' . number_format($monto, 2, '.', ''),
                null,
                $pago->fresh(['venta', 'cuota', 'cliente'])->toArray()
            );

            return $pago->fresh(['venta', 'cuota', 'cliente']);
        });
    }
}

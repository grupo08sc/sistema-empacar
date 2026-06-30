<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\Pago;
use App\Models\PlanPago;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaCuotasService
{
    /**
     * Crea una venta comercial con productos, pago inicial opcional y plan de cuotas opcional.
     *
     * @param array<int, array<string, mixed>> $detalles
     */
    public function crearVenta(array $data, array $detalles): Venta
    {
        return DB::transaction(function () use ($data, $detalles) {
            if (count($detalles) === 0) {
                throw ValidationException::withMessages([
                    'detalles' => 'Debe agregar al menos un producto a la venta.',
                ]);
            }

            $subtotal = 0.0;
            $lineas = [];

            foreach ($detalles as $index => $detalle) {
                $cantidad = (int) ($detalle['cantidad'] ?? 0);

                if ($cantidad < 1) {
                    throw ValidationException::withMessages([
                        "detalles.$index.cantidad" => 'La cantidad debe ser mayor o igual a 1.',
                    ]);
                }

                $productoId = $detalle['producto_id'] ?? $detalle['id_producto'] ?? null;

                if (! $productoId) {
                    throw ValidationException::withMessages([
                        "detalles.$index.producto_id" => 'Cada detalle debe tener un producto.',
                    ]);
                }

                $producto = Producto::lockForUpdate()->findOrFail($productoId);

                if ((int) $producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        "detalles.$index.cantidad" => "Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock}.",
                    ]);
                }

                $precio = round((float) ($detalle['precio'] ?? $producto->precioConDescuento()), 2);
                $lineaSubtotal = round($precio * $cantidad, 2);
                $subtotal += $lineaSubtotal;

                $lineas[] = [
                    'producto' => $producto,
                    'id_producto' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $lineaSubtotal,
                ];
            }

            $descuento = round((float) ($data['descuento'] ?? 0), 2);
            $total = max(0, round($subtotal - $descuento, 2));
            $montoInicial = min($total, round((float) ($data['monto_inicial'] ?? 0), 2));
            $saldo = round($total - $montoInicial, 2);
            $tipoPago = $data['tipo_pago'] ?? ($saldo > 0 ? 'credito' : 'contado');

            $venta = Venta::create([
                'id_cliente' => $data['id_cliente'],
                'id_usuario' => $data['id_usuario'],
                'fecha_venta' => $data['fecha_venta'] ?? now()->toDateString(),
                'estado' => $saldo <= 0 ? 'pagado' : ($montoInicial > 0 ? 'parcial' : 'pendiente'),
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'monto_pagado' => $montoInicial,
                'saldo' => $saldo,
                'tipo_pago' => $tipoPago,
                'observaciones' => $data['observaciones'] ?? null,
                'state' => 'a',
            ]);

            foreach ($lineas as $linea) {
                DetalleVenta::create([
                    'id_venta' => $venta->id,
                    'id_producto' => $linea['id_producto'],
                    'cantidad' => $linea['cantidad'],
                    'precio' => $linea['precio'],
                    'subtotal' => $linea['subtotal'],
                    'state' => 'a',
                ]);

                $linea['producto']->decrement('stock', $linea['cantidad']);

                Inventario::create([
                    'id_producto' => $linea['id_producto'],
                    'cantidad' => $linea['cantidad'],
                    'fecha' => $venta->fecha_venta,
                    'tipo' => 'salida',
                    'descripcion' => 'Salida automática por venta #' . $venta->id,
                    'state' => 'a',
                ]);
            }

            if ($montoInicial > 0) {
                Pago::create([
                    'id_venta' => $venta->id,
                    'id_cliente' => $venta->id_cliente,
                    'monto' => $montoInicial,
                    'tipo_pago' => $data['metodo_pago_inicial'] ?? $data['tipo_pago_inicial'] ?? 'efectivo',
                    'estado_pago' => 'pagado',
                    'fecha_pago' => $data['fecha_pago_inicial'] ?? now()->toDateString(),
                    'referencia' => $data['referencia_inicial'] ?? null,
                    'observaciones' => 'Pago inicial de la venta',
                    'state' => 'a',
                ]);
            }

            $cantidadCuotas = (int) ($data['cantidad_cuotas'] ?? 0);

            if ($saldo > 0 && $cantidadCuotas > 0) {
                $this->crearPlanCuotas($venta, $saldo, $montoInicial, $cantidadCuotas, $data);
            }

            return $venta->fresh(['cliente', 'detalles.producto', 'plan.cuotas', 'pagos']);
        });
    }

    protected function crearPlanCuotas(Venta $venta, float $saldo, float $montoInicial, int $cantidadCuotas, array $data): PlanPago
    {
        $fechaInicio = Carbon::parse($data['fecha_inicio'] ?? now()->toDateString());
        $frecuencia = $data['frecuencia'] ?? 'mensual';
        $montoCuota = round($saldo / $cantidadCuotas, 2);

        $plan = PlanPago::create([
            'id_venta' => $venta->id,
            'cantidad_cuotas' => $cantidadCuotas,
            'monto_cuota' => $montoCuota,
            'total_deuda' => $saldo,
            'monto_inicial' => $montoInicial,
            'saldo_financiado' => $saldo,
            'saldo_restante' => $saldo,
            'fecha_inicio' => $fechaInicio->toDateString(),
            'frecuencia' => $frecuencia,
            'estado' => 'en_curso',
            'observaciones' => $data['observaciones_plan'] ?? null,
            'state' => 'a',
        ]);

        $acumulado = 0.0;
        for ($i = 1; $i <= $cantidadCuotas; $i++) {
            $monto = $i === $cantidadCuotas
                ? round($saldo - $acumulado, 2)
                : $montoCuota;

            $acumulado += $monto;

            Cuota::create([
                'id_venta' => $venta->id,
                'id_plan_pago' => $plan->id,
                'monto' => $monto,
                'monto_pagado' => 0,
                'fecha_vencimiento' => $this->calcularVencimiento($fechaInicio, $frecuencia, $i)->toDateString(),
                'estado' => 'pendiente',
                'state' => 'a',
            ]);
        }

        return $plan;
    }

    protected function calcularVencimiento(Carbon $fechaInicio, string $frecuencia, int $numeroCuota): Carbon
    {
        return match ($frecuencia) {
            'semanal' => $fechaInicio->copy()->addWeeks($numeroCuota - 1),
            'quincenal' => $fechaInicio->copy()->addDays(15 * ($numeroCuota - 1)),
            default => $fechaInicio->copy()->addMonthsNoOverflow($numeroCuota - 1),
        };
    }
}

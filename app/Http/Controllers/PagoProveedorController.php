<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\PagoProveedor;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\AuditoriaService;
use App\Services\AnulacionService;

class PagoProveedorController extends Controller
{
    public function index(Request $request)
    {
        $pagos = PagoProveedor::with(['proveedor', 'compra', 'usuario'])
            ->where('state', 'a')
            ->latest('id')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['pagos' => $pagos]);
        }

        return Inertia::render('PagoProveedor/Index', [
            'pagos' => $pagos,
            'proveedores' => Proveedor::where('state', 'a')->where('estado', 'activo')->orderBy('nombre')->get(),
            'comprasPendientes' => Compra::with('proveedor')
                ->where('state', 'a')
                ->where('estado_aprobacion', 'aprobada')
                ->where('saldo', '>', 0)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_proveedor' => 'required|exists:proveedores,id',
            'id_compra' => 'nullable|exists:compras,id',
            'monto' => 'required|numeric|min:0.01',
            'fecha_pago' => 'nullable|date',
            'metodo_pago' => 'required|in:efectivo,transferencia,qr,pagofacil,tarjeta,cheque,otro',
            'referencia' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $monto = round((float) $validated['monto'], 2);
            $compra = null;

            if (! empty($validated['id_compra'])) {
                $compra = Compra::lockForUpdate()->findOrFail($validated['id_compra']);

                if ($compra->estado_aprobacion !== 'aprobada') {
                    throw ValidationException::withMessages([
                        'id_compra' => 'Solo se pueden registrar pagos sobre compras aprobadas.',
                    ]);
                }

                if ((int) $compra->id_proveedor !== (int) $validated['id_proveedor']) {
                    throw ValidationException::withMessages([
                        'id_compra' => 'La compra seleccionada no corresponde al proveedor indicado.',
                    ]);
                }

                if ($monto > (float) $compra->saldo) {
                    throw ValidationException::withMessages([
                        'monto' => 'El monto no puede ser mayor al saldo pendiente de la compra.',
                    ]);
                }
            }

            $pago = PagoProveedor::create([
                'id_proveedor' => $validated['id_proveedor'],
                'id_compra' => $validated['id_compra'] ?? null,
                'id_usuario' => auth()->id(),
                'monto' => $monto,
                'fecha_pago' => $validated['fecha_pago'] ?? now()->toDateString(),
                'metodo_pago' => $validated['metodo_pago'],
                'referencia' => $validated['referencia'] ?? null,
                'estado' => 'confirmado',
                'observaciones' => $validated['observaciones'] ?? null,
                'state' => 'a',
            ]);

            app(AuditoriaService::class)->registrar(
                'PagoProveedor',
                'registrar_pago_proveedor',
                $pago,
                'Registro de pago a proveedor por Bs ' . number_format($monto, 2, '.', ''),
                null,
                $pago->toArray()
            );

            if ($compra) {
                $nuevoPagado = round((float) $compra->monto_pagado + $monto, 2);
                $nuevoSaldo = max(0, round((float) $compra->total - $nuevoPagado, 2));

                $compra->update([
                    'monto_pagado' => $nuevoPagado,
                    'saldo' => $nuevoSaldo,
                    'estado' => $nuevoSaldo <= 0 ? 'pagado' : 'parcial',
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Pago a proveedor registrado correctamente.'], 201);
        }

        return to_route('pagos-proveedor.index')->with('success', 'Pago a proveedor registrado correctamente.');
    }

    public function destroy(Request $request, PagoProveedor $pagos_proveedor, AnulacionService $anulacionService)
    {
        $validated = $request->validate([
            'motivo' => 'nullable|string|min:5|max:500',
        ], [
            'motivo.min' => 'El motivo de anulación debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo de anulación no debe superar 500 caracteres.',
        ]);

        $motivo = $validated['motivo'] ?? 'Anulación solicitada desde pagos a proveedores.';

        try {
            $anulacionService->anularPagoProveedor($pagos_proveedor, $motivo);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'No se pudo anular el pago: ' . $e->getMessage()], 422);
            }

            return back()->withErrors(['pago' => 'No se pudo anular el pago: ' . $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Pago a proveedor anulado correctamente.']);
        }

        return to_route('pagos-proveedor.index')->with('success', 'Pago a proveedor anulado correctamente y saldo recalculado.');
    }
}

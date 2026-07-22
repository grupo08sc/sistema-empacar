<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Departamento;
use App\Models\DetalleCompra;
use App\Models\DetalleSolicitud;
use App\Models\Inventario;
use App\Models\PagoProveedor;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Solicitud;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SolicitudController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Obtener el usuario a partir de la request
            $usuario = auth()->user();
            // Filtrar las solicitudes por la relacion de departamento del usuario
            $solicitudes = Solicitud::with(['usuario', 'departamento', 'detalles.producto'])
                ->where('state', 'a')
                ->when($usuario->id_departamento, function ($query) use ($usuario) {
                    $query->where('id_departamento', $usuario->id_departamento);
                })
                ->latest('id')
                ->get();

            // if (!$solicitudes->isEmpty()) {
            //     $solicitudes->transform(function ($solicitud) {
            //         $solicitud->fecha_requerida = Carbon::parse($solicitud->fecha_requerida)->format('d/m/Y');
            //         return $solicitud;
            //     });
            // }

            if ($request->wantsJson()) {
                return response()->json(['solicitudes' => $solicitudes]);
            }

            return Inertia::render('Solicitud/Index', [
                'solicitudes' => $solicitudes,
                'departamentos' => Departamento::where('state', 'a')->orderBy('nombre')->get(),
                'productos' => Producto::where('state', 'a')->orderBy('nombre')->get(),
                'proveedores' => Proveedor::where('state', 'a')->where('estado', 'activo')->orderBy('nombre')->get(),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_departamento' => 'exists:departamentos,id',
            'id_proveedor' => 'required|exists:proveedores,id',
            'metodo_pago' => 'nullable|in:efectivo,transferencia,qr,tarjeta,cheque,otro,pagofacil',
            'descripcion' => 'required|string',
            'justificacion' => 'nullable|string',
            'fecha_requerida' => 'nullable|date',
            'moneda' => 'nullable|string|max:10',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'nullable|exists:productos,id',
            'detalles.*.nombre_articulo' => 'nullable|string|max:255',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_estimado' => 'nullable|numeric|min:0',
        ]);

        $solicitud = DB::transaction(function () use ($validated) {
            $subtotal = 0;
            foreach ($validated['detalles'] as $detalle) {
                $subtotal += round((float) $detalle['precio_estimado'] * (int) $detalle['cantidad'], 2);
            }

            $total = max(0, round($subtotal, 2));
            $solicitud = Solicitud::create([
                'id_usuario' => auth()->id(),
                'id_departamento' => $validated['id_departamento'] ?? null,
                'id_proveedor' => $validated['id_proveedor'],
                'metodo_pago_propuesto' => $validated['metodo_pago'] ?? 'efectivo',
                'descripcion' => $validated['descripcion'],
                'justificacion' => $validated['justificacion'] ?? null,
                'fecha_solicitud' => Carbon::now(),
                'fecha_requerida' => $validated['fecha_requerida'] ?? null,
                'estado' => 'pendiente',
                'moneda' => $validated['moneda'] ?? 'BOB',
                'observaciones' => $validated['observaciones'] ?? null,
                'state' => 'a',
                'total' => $total,
            ]);

            foreach ($validated['detalles'] as $detalle) {
                $cantidad = (int) $detalle['cantidad'];
                $precio = round((float) ($detalle['precio_estimado'] ?? 0), 2);

                DetalleSolicitud::create([
                    'id_solicitud' => $solicitud->id,
                    'id_producto' => $detalle['id_producto'] ?? null,
                    'nombre_articulo' => $detalle['nombre_articulo'] ?? null,
                    'cantidad' => $cantidad,
                    'precio_estimado' => $precio,
                    'importe' => round($cantidad * $precio, 2),
                    'state' => 'a',
                ]);
            }

            return $solicitud->fresh(['usuario', 'departamento', 'detalles.producto']);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Solicitud registrada correctamente.',
                'solicitud' => $solicitud,
            ], 201);
        }

        return to_route('solicitudes.index')->with('success', 'Solicitud registrada correctamente.');
    }

    public function show(Request $request, Solicitud $solicitud)
    {
        $solicitud->load(['usuario', 'departamento', 'detalles.producto']);

        if ($request->wantsJson()) {
            return response()->json(['solicitud' => $solicitud]);
        }

        return Inertia::render('Solicitud/Show', [
            'solicitud' => $solicitud,
        ]);
    }

    public function update(Request $request, Solicitud $solicitud)
    {
        $validated = $request->validate([
            'estado' => 'sometimes|required|in:pendiente,aprobada,rechazada,atendida,anulada',
            'observaciones' => 'nullable|string',
        ]);

        $solicitud->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Solicitud actualizada correctamente.',
                'solicitud' => $solicitud->fresh(['usuario', 'departamento', 'detalles.producto']),
            ]);
        }

        return to_route('solicitudes.index')->with('success', 'Solicitud actualizada correctamente.');
    }

    public function destroy(Request $request, Solicitud $solicitud)
    {
        $solicitud->update(['state' => 'i', 'estado' => 'anulada']);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Solicitud anulada correctamente.']);
        }

        return to_route('solicitudes.index')->with('success', 'Solicitud anulada correctamente.');
    }

    public function aprobar(Request $request, Solicitud $solicitud)
    {
        try {
            $validated = $request->validate([
                'observaciones' => 'nullable|string|max:500',
            ], [
                'observaciones.max' => 'La observación de aprobación no debe superar 500 caracteres.',
            ]);

            $solicitudAprobada = DB::transaction(function () use ($solicitud, $validated) {
                $solicitud = Solicitud::lockForUpdate()
                    ->with(['detalles.producto', 'departamento'])
                    ->findOrFail($solicitud->id);

                if ($solicitud->estado !== 'pendiente') {
                    throw ValidationException::withMessages([
                        'solicitud' => 'Solo se pueden aprobar solicitudes pendientes.',
                    ]);
                }
                $solicitud->update([
                    'estado' => 'aprobada',
                ]);

                $compra = Compra::create([
                    'id_proveedor' => $solicitud->id_proveedor,
                    'id_usuario' => auth()->id(),
                    'solicitado_por' => auth()->id(),
                    'fecha_compra' => $solicitud->fecha_compra ?? now()->toDateString(),
                    'fecha_solicitud' => now(),
                    'subtotal' => 0,
                    'descuento' => 0,
                    'total' => $solicitud->total,
                    'monto_pagado' => 0,
                    'saldo' => $solicitud->total,
                    'estado' => 'pendiente',
                    'estado_aprobacion' => 'aprobada',
                    'observaciones' => $solicitud->observaciones ?? null,
                    'metodo_pago_propuesto' => $solicitud->metodo_pago ?? 'efectivo',
                    'referencia_pago_propuesto' => $solicitud->referencia ?? null,
                    'stock_aplicado' => false,
                    'state' => 'a',
                ]);

                foreach ($solicitud->detalles as $detalle) {
                    $producto = Producto::where('state', 'a')->findOrFail($detalle['id_producto']);
                    $cantidad = (int) $detalle['cantidad'];
                    $precio = round((float) $detalle['precio_unitario'], 2);
                    $lineaSubtotal = round($cantidad * $precio, 2);

                    DetalleCompra::create([
                        'id_compra' => $compra->id,
                        'id_producto' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $lineaSubtotal,
                        'state' => 'a',
                    ]);
                }

                $solicitudFinal = $solicitud->fresh([
                    'proveedor',
                    // 'solicitante',
                    // 'aprobador',
                    'detalles.producto',
                    // 'pagos',
                    'compras'
                ]);
                $compraFinal = $compra->fresh(['proveedor', 'solicitante', 'detalles.producto', 'solicitud']);

                app(AuditoriaService::class)->registrar(
                    'Solicitud',
                    'aprobar_solicitud',
                    $solicitudFinal,
                    'Solicitud de Compra aprobada. Se generó el registro de compras.',
                    null,
                    $solicitudFinal->toArray()
                );

                return $solicitudFinal;
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Solicitud de compra aprobada correctamente. Se generó el registro de compras.',
                    'solicitud' => $solicitudAprobada,
                ]);
            }

            return to_route('solicitudes.index')->with('success', 'Solicitud de compra aprobada correctamente. Se generó el registro de compras.');
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function rechazar(Request $request, Solicitud $compra)
    {
        try {
            $validated = $request->validate([
                'motivo_rechazo' => 'required|string|min:5|max:500',
            ], [
                'motivo_rechazo.required' => 'Debe indicar el motivo de rechazo.',
                'motivo_rechazo.min' => 'El motivo de rechazo debe tener al menos 5 caracteres.',
                'motivo_rechazo.max' => 'El motivo de rechazo no debe superar 500 caracteres.',
            ]);

            DB::transaction(function () use ($compra, $validated) {
                $compra = Solicitud::lockForUpdate()->findOrFail($compra->id);

                if ($compra->estado_aprobacion !== 'pendiente') {
                    throw ValidationException::withMessages([
                        'compra' => 'Solo se pueden rechazar solicitudes de compra pendientes.',
                    ]);
                }

                $estadoAnterior = $compra->toArray();

                $compra->update([
                    'estado_aprobacion' => 'rechazada',
                    'aprobado_por' => auth()->id(),
                    'fecha_aprobacion' => now(),
                    'motivo_rechazo' => $validated['motivo_rechazo'],
                    'stock_aplicado' => false,
                    'estado' => 'rechazada',
                ]);

                app(AuditoriaService::class)->registrar(
                    'Compra',
                    'rechazar_compra',
                    $compra,
                    'Solicitud de compra rechazada. Motivo: ' . $validated['motivo_rechazo'],
                    $estadoAnterior,
                    $compra->fresh()->toArray(),
                    'warning'
                );
            });

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Solicitud de compra rechazada correctamente.']);
            }

            return to_route('solicitudes.index')->with('success', 'Solicitud de compra rechazada correctamente.');
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Inventario;
use App\Models\PagoProveedor;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\AuditoriaService;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $usuario = auth()->user();
        $rol = $usuario?->rol?->nombre;

        $comprasQuery = Compra::with([
                'proveedor',
                'usuario',
                'solicitante',
                'aprobador',
                'detalles.producto',
                'pagos',
            ])
            ->where('state', 'a')
            ->latest('id');

        // El encargado de inventario visualiza sus propias solicitudes de compra.
        // El administrador visualiza todas para poder aprobarlas o rechazarlas.
        if ($rol === 'Encargado de Inventario') {
            $comprasQuery->where(function ($query) use ($usuario) {
                $query->where('solicitado_por', $usuario->id)
                    ->orWhere('id_usuario', $usuario->id);
            });
        }

        $compras = $comprasQuery->get();

        if ($request->wantsJson()) {
            return response()->json(['compras' => $compras]);
        }

        return Inertia::render('Compra/Index', [
            'compras' => $compras,
            'proveedores' => Proveedor::where('state', 'a')->where('estado', 'activo')->orderBy('nombre')->get(),
            'productos' => Producto::where('state', 'a')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_proveedor' => 'required|exists:proveedores,id',
            'fecha_compra' => 'nullable|date',
            'descuento' => 'nullable|numeric|min:0',
            'monto_pagado' => 'nullable|numeric|min:0',
            'metodo_pago' => 'nullable|in:efectivo,transferencia,qr,tarjeta,cheque,otro,pagofacil',
            'referencia' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0.01',
        ], [
            'id_proveedor.required' => 'Debe seleccionar un proveedor.',
            'id_proveedor.exists' => 'El proveedor seleccionado no existe.',
            'fecha_compra.date' => 'La fecha de compra no tiene un formato válido.',
            'descuento.numeric' => 'El descuento debe ser numérico.',
            'descuento.min' => 'El descuento no puede ser negativo.',
            'monto_pagado.numeric' => 'El pago inicial debe ser numérico.',
            'monto_pagado.min' => 'El pago inicial no puede ser negativo.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',
            'referencia.max' => 'La referencia no debe superar 255 caracteres.',
            'observaciones.max' => 'Las observaciones no deben superar 1000 caracteres.',
            'detalles.required' => 'Debe agregar al menos un producto.',
            'detalles.array' => 'El detalle de productos no tiene un formato válido.',
            'detalles.min' => 'Debe agregar al menos un producto.',
            'detalles.*.id_producto.required' => 'Debe seleccionar un producto.',
            'detalles.*.id_producto.exists' => 'Uno de los productos seleccionados no existe.',
            'detalles.*.cantidad.required' => 'Debe indicar la cantidad.',
            'detalles.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'detalles.*.cantidad.min' => 'La cantidad mínima es 1.',
            'detalles.*.precio_unitario.required' => 'Debe indicar el precio de compra.',
            'detalles.*.precio_unitario.numeric' => 'El precio de compra debe ser numérico.',
            'detalles.*.precio_unitario.min' => 'El precio de compra debe ser mayor a cero.',
        ]);

        $compra = DB::transaction(function () use ($validated) {
            $subtotal = 0;
            foreach ($validated['detalles'] as $detalle) {
                $subtotal += round((float) $detalle['precio_unitario'] * (int) $detalle['cantidad'], 2);
            }

            $descuento = round((float) ($validated['descuento'] ?? 0), 2);
            if ($descuento > $subtotal) {
                throw ValidationException::withMessages([
                    'descuento' => 'El descuento no puede ser mayor al subtotal de la compra.',
                ]);
            }

            $total = max(0, round($subtotal - $descuento, 2));
            $montoPagado = round((float) ($validated['monto_pagado'] ?? 0), 2);
            if ($montoPagado > $total) {
                throw ValidationException::withMessages([
                    'monto_pagado' => 'El pago inicial propuesto no puede ser mayor al total de la compra.',
                ]);
            }

            $saldo = round($total - $montoPagado, 2);

            $compra = Compra::create([
                'id_proveedor' => $validated['id_proveedor'],
                'id_usuario' => auth()->id(),
                'solicitado_por' => auth()->id(),
                'fecha_compra' => $validated['fecha_compra'] ?? now()->toDateString(),
                'fecha_solicitud' => now(),
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'monto_pagado' => $montoPagado,
                'saldo' => $saldo,
                'estado' => 'pendiente_aprobacion',
                'estado_aprobacion' => 'pendiente',
                'observaciones' => $validated['observaciones'] ?? null,
                'metodo_pago_propuesto' => $validated['metodo_pago'] ?? 'efectivo',
                'referencia_pago_propuesto' => $validated['referencia'] ?? null,
                'stock_aplicado' => false,
                'state' => 'a',
            ]);

            foreach ($validated['detalles'] as $detalle) {
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

            $compraFinal = $compra->fresh(['proveedor', 'solicitante', 'detalles.producto']);

            app(AuditoriaService::class)->registrar(
                'Compra',
                'solicitar_compra',
                $compraFinal,
                'Solicitud de compra registrada. Pendiente de aprobación administrativa. No se actualizó stock.',
                null,
                $compraFinal->toArray()
            );

            return $compraFinal;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Solicitud de compra registrada correctamente. Pendiente de aprobación del administrador.',
                'compra' => $compra,
            ], 201);
        }

        return to_route('compras.index')->with('success', 'Solicitud de compra registrada correctamente. Pendiente de aprobación del administrador.');
    }

    public function show(Request $request, Compra $compra)
    {
        $compra->load(['proveedor', 'usuario', 'solicitante', 'aprobador', 'detalles.producto', 'pagos.usuario']);

        if ($request->wantsJson()) {
            return response()->json(['compra' => $compra]);
        }

        return Inertia::render('Compra/Show', [
            'compra' => $compra,
        ]);
    }

    public function aprobar(Request $request, Compra $compra)
    {
        $validated = $request->validate([
            'observacion_aprobacion' => 'nullable|string|max:500',
        ], [
            'observacion_aprobacion.max' => 'La observación de aprobación no debe superar 500 caracteres.',
        ]);

        $compraAprobada = DB::transaction(function () use ($compra, $validated) {
            $compra = Compra::lockForUpdate()
                ->with(['detalles.producto', 'proveedor'])
                ->findOrFail($compra->id);

            if ($compra->estado_aprobacion !== 'pendiente') {
                throw ValidationException::withMessages([
                    'compra' => 'Solo se pueden aprobar solicitudes de compra pendientes.',
                ]);
            }

            $estadoAnterior = $compra->toArray();

            foreach ($compra->detalles as $detalle) {
                if (! $detalle->producto) {
                    throw ValidationException::withMessages([
                        'compra' => 'La solicitud contiene un producto inexistente o inactivo.',
                    ]);
                }

                $producto = Producto::lockForUpdate()->findOrFail($detalle->producto->id);
                $cantidad = (int) $detalle->cantidad;
                $precio = round((float) $detalle->precio_unitario, 2);

                $producto->increment('stock', $cantidad);
                $producto->update([
                    'precio_compra' => $precio,
                    'fecha_ingreso' => now()->toDateString(),
                ]);

                Inventario::create([
                    'id_producto' => $producto->id,
                    'cantidad' => $cantidad,
                    'fecha' => $compra->fecha_compra ?? now()->toDateString(),
                    'tipo' => 'entrada',
                    'descripcion' => 'Entrada automática por aprobación de solicitud de compra #' . $compra->id,
                    'state' => 'a',
                ]);
            }

            if ((float) $compra->monto_pagado > 0) {
                PagoProveedor::create([
                    'id_proveedor' => $compra->id_proveedor,
                    'id_compra' => $compra->id,
                    'id_usuario' => auth()->id(),
                    'monto' => (float) $compra->monto_pagado,
                    'fecha_pago' => now()->toDateString(),
                    'metodo_pago' => $compra->metodo_pago_propuesto ?: 'efectivo',
                    'referencia' => $compra->referencia_pago_propuesto,
                    'estado' => 'confirmado',
                    'observaciones' => 'Pago inicial aplicado al aprobar la solicitud de compra.',
                    'state' => 'a',
                ]);
            }

            $nuevoEstadoPago = (float) $compra->saldo <= 0
                ? 'pagado'
                : ((float) $compra->monto_pagado > 0 ? 'parcial' : 'pendiente');

            $compra->update([
                'estado_aprobacion' => 'aprobada',
                'aprobado_por' => auth()->id(),
                'fecha_aprobacion' => now(),
                'observacion_aprobacion' => $validated['observacion_aprobacion'] ?? null,
                'stock_aplicado' => true,
                'estado' => $nuevoEstadoPago,
            ]);

            $compraFinal = $compra->fresh(['proveedor', 'solicitante', 'aprobador', 'detalles.producto', 'pagos']);

            app(AuditoriaService::class)->registrar(
                'Compra',
                'aprobar_compra',
                $compraFinal,
                'Solicitud de compra aprobada. Se actualizó stock, inventario y pago inicial si correspondía.',
                $estadoAnterior,
                $compraFinal->toArray()
            );

            return $compraFinal;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Solicitud de compra aprobada correctamente. El stock fue actualizado.',
                'compra' => $compraAprobada,
            ]);
        }

        return to_route('compras.index')->with('success', 'Solicitud de compra aprobada correctamente. El stock fue actualizado.');
    }

    public function rechazar(Request $request, Compra $compra)
    {
        $validated = $request->validate([
            'motivo_rechazo' => 'required|string|min:5|max:500',
        ], [
            'motivo_rechazo.required' => 'Debe indicar el motivo de rechazo.',
            'motivo_rechazo.min' => 'El motivo de rechazo debe tener al menos 5 caracteres.',
            'motivo_rechazo.max' => 'El motivo de rechazo no debe superar 500 caracteres.',
        ]);

        DB::transaction(function () use ($compra, $validated) {
            $compra = Compra::lockForUpdate()->findOrFail($compra->id);

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

        return to_route('compras.index')->with('success', 'Solicitud de compra rechazada correctamente.');
    }

    public function destroy(Request $request, Compra $compra)
    {
        DB::transaction(function () use ($compra) {
            $compra = Compra::lockForUpdate()->with('detalles.producto', 'pagos')->findOrFail($compra->id);

            if ($compra->estado_aprobacion === 'aprobada' && $compra->pagos()->where('state', 'a')->exists()) {
                throw ValidationException::withMessages([
                    'compra' => 'No se puede anular una compra aprobada con pagos registrados desde esta pantalla.',
                ]);
            }

            $estadoAnterior = $compra->toArray();

            if ($compra->estado_aprobacion === 'aprobada' && $compra->stock_aplicado) {
                foreach ($compra->detalles as $detalle) {
                    if (! $detalle->producto) {
                        continue;
                    }

                    $producto = Producto::lockForUpdate()->findOrFail($detalle->producto->id);
                    $cantidad = (int) $detalle->cantidad;

                    if ((int) $producto->stock < $cantidad) {
                        throw ValidationException::withMessages([
                            'compra' => 'No se puede anular la compra porque el stock del producto "' . $producto->nombre . '" ya fue utilizado.',
                        ]);
                    }

                    $producto->decrement('stock', $cantidad);

                    Inventario::create([
                        'id_producto' => $producto->id,
                        'cantidad' => $cantidad,
                        'fecha' => now()->toDateString(),
                        'tipo' => 'salida',
                        'descripcion' => 'Reversión automática por anulación de compra aprobada #' . $compra->id,
                        'state' => 'a',
                    ]);
                }
            }

            $compra->update([
                'state' => 'i',
                'estado' => 'anulado',
                'estado_aprobacion' => 'anulada',
            ]);

            app(AuditoriaService::class)->registrar(
                'Compra',
                'anular_compra',
                $compra,
                $compra->stock_aplicado
                    ? 'Anulación de compra aprobada con reversión automática de stock.'
                    : 'Anulación de solicitud de compra sin afectación de stock.',
                $estadoAnterior,
                $compra->fresh()->toArray(),
                'warning'
            );
        });

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Compra o solicitud anulada correctamente.']);
        }

        return to_route('compras.index')->with('success', 'Compra o solicitud anulada correctamente.');
    }
}

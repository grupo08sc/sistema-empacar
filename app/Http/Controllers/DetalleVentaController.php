<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\Producto;
use Illuminate\Http\Request;

class DetalleVentaController extends Controller
{
    public function index()
    {
        // No se usa con Inertia.
    }

    public function create()
    {
        // No se usa con Inertia.
    }

    public function store(Request $request)
    {
        // Los detalles se registran desde VentaCuotasService.
    }

    /**
     * Calcula el total de una lista de productos sin registrar la venta.
     */
    public function calcularTotal(Request $request)
    {
        $validated = $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'descuento' => 'nullable|numeric|min:0',
        ]);

        $subtotal = 0;
        $lineas = [];

        foreach ($validated['detalles'] as $detalle) {
            $cantidad = (int) $detalle['cantidad'];
            $producto = Producto::findOrFail($detalle['producto_id']);
            $precio = $producto->precioConDescuento();
            $importe = round($precio * $cantidad, 2);
            $subtotal += $importe;

            $lineas[] = [
                'descripcion' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'importe' => $importe,
            ];
        }

        $descuento = round((float) ($validated['descuento'] ?? 0), 2);
        $total = max(0, round($subtotal - $descuento, 2));

        return response()->json([
            'subtotal' => round($subtotal, 2),
            'descuento' => $descuento,
            'total' => $total,
            'lineas' => $lineas,
        ]);
    }

    public function show(DetalleVenta $detalleVenta)
    {
        // No se usa con Inertia.
    }

    public function edit(DetalleVenta $detalleVenta)
    {
        // No se usa con Inertia.
    }

    public function update(Request $request, DetalleVenta $detalleVenta)
    {
        // No se usa con Inertia.
    }

    public function destroy(DetalleVenta $detalleVenta)
    {
        // No se usa con Inertia.
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\DetalleSolicitud;
use App\Models\Producto;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SolicitudController extends Controller
{
    public function index(Request $request)
    {
        $solicitudes = Solicitud::with(['usuario', 'departamento', 'detalles.producto'])
            ->where('state', 'a')
            ->latest('id')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['solicitudes' => $solicitudes]);
        }

        return Inertia::render('Solicitud/Index', [
            'solicitudes' => $solicitudes,
            'departamentos' => Departamento::where('state', 'a')->orderBy('nombre')->get(),
            'productos' => Producto::where('state', 'a')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_departamento' => 'nullable|exists:departamentos,id',
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
            $solicitud = Solicitud::create([
                'id_usuario' => auth()->id(),
                'id_departamento' => $validated['id_departamento'] ?? null,
                'descripcion' => $validated['descripcion'],
                'justificacion' => $validated['justificacion'] ?? null,
                'fecha_solicitud' => now()->toDateString(),
                'fecha_requerida' => $validated['fecha_requerida'] ?? null,
                'estado' => 'pendiente',
                'moneda' => $validated['moneda'] ?? 'BOB',
                'observaciones' => $validated['observaciones'] ?? null,
                'state' => 'a',
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
}

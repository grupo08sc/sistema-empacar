<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\AnulacionService;
use App\Services\AuditoriaService;
use App\Services\VentaCuotasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class VentaController extends Controller
{
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(12);

        $user = Auth::user();
        $rol = $user->rol->nombre;

        $query = Venta::with(['cliente', 'usuario', 'detalles.producto', 'planes.cuotas', 'pagos'])
            ->where('state', 'a')
            ->orderBy('id', 'asc');

        if ($rol === 'Cliente') {
            if ($user->cliente) {
                $query->where('id_cliente', $user->cliente->id);
            } else {
                $query->where('id', -1);
            }
        }

        return Inertia::render('Venta/Index', [
            'ventas' => $query->get(),
            'clientes' => Cliente::where('state', 'a')->get(),
            'productos' => Producto::where('state', 'a')->get(),
            'num' => $num,
        ]);
    }

    public function create()
    {
        // No se usa con Inertia.
    }

    public function store(Request $request, VentaCuotasService $ventaCuotasService)
    {
        $usuario = auth()->user();

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'detalles' => 'required|array|min:1',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.precio' => 'nullable|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'tipo_pago' => 'nullable|in:contado,credito,mixto',
            'monto_inicial' => 'nullable|numeric|min:0',
            'cantidad_cuotas' => 'nullable|integer',
            'fecha_inicio' => 'nullable|date',
            'frecuencia' => 'nullable|in:semanal,quincenal,mensual',
            'metodo_pago_inicial' => 'nullable|string|max:50',
            'referencia_inicial' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $venta = $ventaCuotasService->crearVenta([
                'id_cliente' => $validated['cliente_id'],
                'id_usuario' => $usuario->id,
                'descuento' => $validated['descuento'] ?? 0,
                'tipo_pago' => $validated['tipo_pago'] ?? null,
                'monto_inicial' => $validated['monto_inicial'] ?? 0,
                'cantidad_cuotas' => $validated['cantidad_cuotas'] ?? 0,
                'fecha_inicio' => $validated['fecha_inicio'] ?? now()->toDateString(),
                'frecuencia' => $validated['frecuencia'] ?? 'mensual',
                'metodo_pago_inicial' => $validated['metodo_pago_inicial'] ?? 'efectivo',
                'referencia_inicial' => $validated['referencia_inicial'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ], $validated['detalles']);

            app(AuditoriaService::class)->registrar(
                'Venta',
                'crear_venta',
                $venta,
                'Registro de venta con modalidad de pago ' . ($validated['tipo_pago'] ?? 'contado'),
                null,
                $venta->toArray()
            );
        } catch (\Throwable $e) {
            // return back()->withErrors(['venta' => 'No se pudo registrar la venta: ' . $e->getMessage()]);
            return response()->json(['error' => 'No se pudo registrar la venta: ' . $e->getMessage()], 500);
        }

        return to_route('venta.index')->with('success', 'Venta creada correctamente.');
    }

    public function show(Venta $venta)
    {
        // No se usa con Inertia.
    }

    public function edit(Venta $venta)
    {
        // No se usa con Inertia.
    }

    public function update(Request $request, Venta $venta)
    {
        // Las ventas cerradas se corrigen con anulación y nuevo registro.
    }

    public function destroy(Request $request, $id, AnulacionService $anulacionService)
    {
        $validated = $request->validate([
            'motivo' => 'nullable|string|min:5|max:500',
        ], [
            'motivo.min' => 'El motivo de anulación debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo de anulación no debe superar 500 caracteres.',
        ]);

        $venta = Venta::findOrFail($id);
        $motivo = $validated['motivo'] ?? 'Anulación solicitada desde el módulo de ventas.';

        try {
            $anulacionService->anularVenta($venta, $motivo);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors(['venta' => 'No se pudo anular la venta: ' . $e->getMessage()]);
        }

        return to_route('venta.index')->with('success', 'Venta anulada correctamente con reversión de stock, pagos y cuotas.');
    }

    public function detalles(Venta $venta)
    {
        $venta->loadMissing(['cliente', 'detalles.producto', 'pagos', 'planes.cuotas']);

        $html = '<div class="container">';
        $html .= '<h2>Venta #' . $venta->id . '</h2>';
        $html .= '<p><strong>Cliente:</strong> ' . ($venta->cliente->nombre ?? 'Sin cliente') . '</p>';
        $html .= '<p><strong>Fecha:</strong> ' . $venta->fecha_venta . '</p>';
        $html .= '<p><strong>Estado:</strong> ' . $venta->estado . '</p>';
        $html .= '<h3>Detalle de venta</h3>';

        if ($venta->detalles->count() > 0) {
            $html .= '<table class="table"><thead><tr><th>Producto</th><th>P/U</th><th>Cantidad</th><th>Subtotal</th></tr></thead><tbody>';

            foreach ($venta->detalles as $detalle) {
                $precioUnitario = (float) ($detalle->precio ?? $detalle->producto->precioConDescuento());
                $subtotal = (float) ($detalle->subtotal ?? ($detalle->cantidad * $precioUnitario));

                $html .= '<tr>';
                $html .= '<td>' . ($detalle->producto->nombre ?? 'Producto eliminado') . '</td>';
                $html .= '<td>' . number_format($precioUnitario, 2) . '</td>';
                $html .= '<td>' . $detalle->cantidad . '</td>';
                $html .= '<td>' . number_format($subtotal, 2) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No hay productos registrados en esta venta.</p>';
        }

        $html .= '<h4 class="text-right">Total: ' . number_format((float) $venta->total, 2) . '</h4>';
        $html .= '<h3>Pagos realizados</h3>';

        if ($venta->pagos->count() === 0) {
            $html .= '<p>No hay pagos registrados.</p>';
        } else {
            $html .= '<table class="table table-bordered"><thead><tr><th>Fecha</th><th>Monto</th><th>Tipo</th><th>Estado</th></tr></thead><tbody>';

            foreach ($venta->pagos as $pago) {
                $html .= '<tr><td>' . $pago->fecha_pago . '</td><td>' . number_format((float) $pago->monto, 2) . '</td><td>' . ucfirst($pago->tipo_pago) . '</td><td>' . $pago->estado_pago . '</td></tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '<h3>Cuotas del plan de pago</h3>';

        if ($venta->planes->count() === 0) {
            $html .= '<p>No hay planes de pago para esta venta.</p>';
        } else {
            foreach ($venta->planes as $plan) {
                $html .= '<h5>Plan #' . $plan->id . ' - Total deuda: ' . number_format((float) $plan->total_deuda, 2) . '</h5>';
                $html .= '<table class="table table-bordered"><thead><tr><th># Cuota</th><th>Monto</th><th>Vencimiento</th><th>Saldo</th><th>Estado</th></tr></thead><tbody>';

                foreach ($plan->cuotas as $index => $cuota) {
                    $html .= '<tr><td>' . ($index + 1) . '</td><td>' . number_format((float) $cuota->monto, 2) . '</td><td>' . $cuota->fecha_vencimiento . '</td><td>' . number_format($cuota->saldo(), 2) . '</td><td>' . ucfirst($cuota->estado) . '</td></tr>';
                }

                $html .= '</tbody></table>';
            }
        }

        $html .= '</div>';

        return $html;
    }
}

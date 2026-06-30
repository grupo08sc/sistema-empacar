<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\Contador;
use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Venta;
use App\Services\PagoFacilService;
use App\Services\PagoCuotasService;
use App\Services\AnulacionService;
use Inertia\Inertia;

class PagoController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $usuario = auth()->user();

        $contar = new Contador();
        $num = $contar->contarModel(5);

        $rol = $usuario->rol->nombre;
        $pagosQuery = Pago::with(['venta.cliente', 'cliente', 'cuota'])
            ->where('state', 'a')
            ->latest('id');

        if ($rol === 'Cliente') {
            $clienteId = $usuario->cliente?->id;
            $pagosQuery->where(function ($query) use ($clienteId) {
                $query->where('id_cliente', $clienteId ?: -1)
                    ->orWhereHas('venta', function ($ventaQuery) use ($clienteId) {
                        $ventaQuery->where('id_cliente', $clienteId ?: -1);
                    });
            });
        }

        $pagos = $pagosQuery->get();

        return Inertia::render('Pago/Index', [
            'pagos' => $pagos,
            'num' => $num,
            // 'rol' => $rol,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $ventaId = null, ?PagoCuotasService $pagoCuotasService = null)
    {
        $ventaId = $ventaId ?: $request->input('id_venta');

        $validated = $request->validate([
            'id_venta' => $ventaId ? 'nullable|exists:ventas,id' : 'required|exists:ventas,id',
            'monto' => 'required|numeric|min:0.01',
            'tipo_pago' => 'required|string|max:50',
            'id_cuota' => 'nullable|exists:cuotas,id',
            'referencia' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $pagoCuotasService ??= app(PagoCuotasService::class);

        $venta = Venta::with('cliente')->findOrFail((int) $ventaId);
        if (auth()->user()?->rol?->nombre === 'Cliente') {
            $clienteId = auth()->user()->cliente?->id;
            if (! $clienteId || (int) $venta->id_cliente !== (int) $clienteId) {
                return back()->withErrors(['pago' => 'No puedes registrar pagos sobre ventas de otro cliente.']);
            }
        }

        try {
            $pagoCuotasService->registrarPagoVenta((int) $ventaId, $validated);
        } catch (\Throwable $e) {
            return back()->withErrors(['pago' => 'No se pudo registrar el pago: ' . $e->getMessage()]);
        }

        return to_route('venta.index')->with('success', 'Pago registrado correctamente.');
    }


    public function confirmarPago(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(Pago $pago)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pago $pago)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pago $pago)
    {
        /*$request->validate([
            'rol_id' => 'required|exists:roles,id', // Validamos que el rol exista
            'funcion' => 'required',
            'estado' => 'required|in:a,i',
        ]);*/

        $validated = $request->validate([
            'tipo_pago' => 'required|string|max:50',
            'estado_pago' => 'required|in:pendiente,parcial,pagado,excedente',
            'referencia' => 'nullable|string|max:255',
        ]);

        $pago->update($validated);

        return to_route('pago.index')->with('success', 'Pago actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Pago $pago, AnulacionService $anulacionService)
    {
        $validated = $request->validate([
            'motivo' => 'nullable|string|min:5|max:500',
        ], [
            'motivo.min' => 'El motivo de anulación debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo de anulación no debe superar 500 caracteres.',
        ]);

        $motivo = $validated['motivo'] ?? 'Anulación solicitada desde el módulo de pagos.';

        try {
            $anulacionService->anularPagoCliente($pago, $motivo);
        } catch (\Throwable $e) {
            return back()->withErrors(['pago' => 'No se pudo anular el pago: ' . $e->getMessage()]);
        }

        return to_route('pago.index')->with('success', 'Pago anulado correctamente y saldos recalculados.');
    }
}

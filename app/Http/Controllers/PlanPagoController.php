<?php

namespace App\Http\Controllers;

use App\Models\PlanPago;
use App\Models\Venta;
use Illuminate\Http\Request;
use App\Models\Contador;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\PagoFacilTransaccion;
use App\Services\PagoCuotasService;
use App\Services\PagoFacilService;
use Inertia\Inertia;

use function Symfony\Component\String\u;

class PlanPagoController extends Controller
{
    protected $pagoService;

    public function __construct(PagoFacilService $pagoService)
    {
        $this->pagoService = $pagoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(8);

        $user = Auth::user();
        $planesQuery = PlanPago::with(['venta.cliente', 'cuotas'])
            ->where('state', 'a');

        if ($user?->rol?->nombre === 'Cliente') {
            $clienteId = $user->cliente?->id;
            $planesQuery->whereHas('venta', function ($query) use ($clienteId) {
                $query->where('id_cliente', $clienteId ?: -1);
            });
        }

        $planes = $planesQuery->get();

        return Inertia::render('Planes/Index', [
            'planes' => $planes,
            'num' => $num,
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cantidad_cuota' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'monto_cuota' => 'required|numeric|min:0.01',
            'total_deuda' => 'required|numeric|min:0.01',
            'saldo_restante' => 'nullable|numeric|min:0',
            'estado' => 'required|in:pendiente,en_curso,finalizado',
        ], [
            'cantidad_cuota.required' => 'Debe ingresar la cantidad de cuotas.',
            'fecha_inicio.required' => 'Debe ingresar la fecha de inicio.',
            'monto_cuota.required' => 'Debe ingresar el monto de la cuota.',
            'total_deuda.required' => 'Debe ingresar el total de la deuda.',
            'estado.required' => 'Debe seleccionar el estado del plan.',
        ]);

        try {

            $plan = PlanPago::create([
                'cantidad_cuotas' => $validated['cantidad_cuota'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'monto_cuota' => $validated['monto_cuota'],
                'total_deuda' => $validated['total_deuda'],
                'saldo_restante' => $validated['saldo_restante'] ?? 0,
                'estado' => $validated['estado'],
                'state' => 'a',
            ]);

            $plan->save();

            Session::flash('success', 'Plan agregado exitosamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Ocurrió un error al guardar Plan.');
        }

        return to_route('planes.index');
    }

    public function guardarPlan(Request $request, $id)
    {
        $venta = Venta::with('plan')->findOrFail($id);

        if ($venta->plan) {
            return back()->withErrors(['plan' => 'La venta ya tiene un plan de pago registrado.']);
        }

        $validated = $request->validate([
            'cantidad_cuotas' => 'required|integer|min:1',
            'total_deuda' => 'required|numeric|min:0.01',
            'fecha_inicio' => 'required|date',
            'frecuencia' => 'nullable|in:semanal,quincenal,mensual',
            'estado' => 'required|in:pendiente,en_curso,finalizado',
            'cuotas' => 'required|array|min:1',
            'cuotas.*.monto' => 'required|numeric|min:0.01',
            'cuotas.*.fecha' => 'required|date',
        ]);

        $saldoVenta = round((float) ($venta->saldo ?? $venta->saldoActual()), 2);
        $totalDeuda = round((float) $validated['total_deuda'], 2);

        if (abs($saldoVenta - $totalDeuda) > 0.01) {
            return back()->withErrors([
                'total_deuda' => 'El total de la deuda debe coincidir con el saldo pendiente de la venta.',
            ]);
        }

        $sumaCuotas = round(array_sum(array_map(fn ($cuota) => (float) $cuota['monto'], $validated['cuotas'])), 2);
        if (abs($sumaCuotas - $totalDeuda) > 0.01) {
            return back()->withErrors([
                'cuotas' => 'La suma de las cuotas debe coincidir con el total de la deuda.',
            ]);
        }

        DB::transaction(function () use ($venta, $validated, $totalDeuda) {
            $plan = PlanPago::create([
                'id_venta' => $venta->id,
                'cantidad_cuotas' => $validated['cantidad_cuotas'],
                'total_deuda' => $totalDeuda,
                'monto_cuota' => round($totalDeuda / $validated['cantidad_cuotas'], 2),
                'monto_inicial' => (float) ($venta->monto_pagado ?? 0),
                'saldo_financiado' => $totalDeuda,
                'saldo_restante' => $totalDeuda,
                'fecha_inicio' => $validated['fecha_inicio'],
                'frecuencia' => $validated['frecuencia'] ?? 'mensual',
                'estado' => $validated['estado'],
                'state' => 'a',
            ]);

            foreach ($validated['cuotas'] as $cuota) {
                Cuota::create([
                    'id_venta' => $venta->id,
                    'id_plan_pago' => $plan->id,
                    'monto' => $cuota['monto'],
                    'monto_pagado' => 0,
                    'fecha_vencimiento' => $cuota['fecha'],
                    'estado' => 'pendiente',
                    'state' => 'a',
                ]);
            }

            $venta->update([
                'tipo_pago' => $venta->tipo_pago === 'contado' ? 'credito' : $venta->tipo_pago,
                'estado' => (float) $venta->monto_pagado > 0 ? 'parcial' : 'pendiente',
            ]);
        });

        return to_route('venta.index')->with('success', 'Plan de pago creado y cuotas generadas.');
    }

    public function pagarCuota(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'cuotas' => 'required|array|min:1',
            'cuotas.*' => 'integer|exists:cuotas,id',
        ], [
            'cuotas.required' => 'Debe seleccionar al menos una cuota.',
            'cuotas.array' => 'La selección de cuotas no es válida.',
            'cuotas.min' => 'Debe seleccionar al menos una cuota.',
            'cuotas.*.integer' => 'La cuota seleccionada no es válida.',
            'cuotas.*.exists' => 'Una de las cuotas seleccionadas no existe.',
        ]);

        $cuota = Cuota::whereIn('id', $validated['cuotas'])
            ->where('id_venta', $venta->id)
            ->where('estado', 'pendiente')
            ->first();

        if (! $cuota) {
            return back()->withErrors(['cuotas' => 'No se encontró una cuota pendiente válida.']);
        }

        return to_route('planes.pagarCuota2', ['cuota' => $cuota->id]);
    }

    public function pagarQR(Venta $venta)
    {
        $resultado = ['qrBase64' => null, 'transactionId' => null];
        $venta->load('cliente');
        // dd($venta);
        try {
            $resultado = $this->pagoService->generarQr($venta);
            $venta->update([
                'pagofacil_transaction_id' => $resultado['transactionId'],
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al conectar con PagoFácil: ' . $e->getMessage()]);
        }

        // dd($venta);
        return Inertia::render('Venta/ShowQrVenta', [
            'venta' => $venta,
            'qrImage' => $resultado['qrBase64'],
            'callbackUrl' => $this->pagoService->callbackUrl(),
            'montoRealSistema' => $resultado['montoRealSistema'] ?? $venta->total,
            'montoQrPrueba' => $resultado['montoQrPrueba'] ?? config('services.pagofacil.monto_prueba', 0.01),
            // 'callbackUrl' => "https://thepross.xyz/pagos/callback",
        ]);
    }

    public function pagarCuota2(Cuota $cuota)
    {
        $resultado = ['qrBase64' => null, 'transactionId' => null];
        $cuota->load('venta.cliente');
        if ($cuota->estado !== 'pagado') {
            try {
                $resultado = $this->pagoService->generarQrParaCuota($cuota);
                $cuota->update([
                    'pagofacil_transaction_id' => $resultado['transactionId'],
                    // 'qr_image' => $resultado['qrBase64'],
                    // 'estado' => 'procesando',
                ]);
            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'Error al conectar con PagoFácil: ' . $e->getMessage()]);
            }
        } else {
            // dd('Ya tiene QR o está pagado');
        }
        return Inertia::render('Venta/ShowQr', [
            'cuota' => $cuota,
            'qrImage' => $resultado['qrBase64'],
            'callbackUrl' => $this->pagoService->callbackUrl(),
            'montoRealSistema' => $resultado['montoRealSistema'] ?? $cuota->monto,
            'montoQrPrueba' => $resultado['montoQrPrueba'] ?? config('services.pagofacil.monto_prueba', 0.01),
            // 'callbackUrl' => "https://thepross.xyz/pagos/callback",
        ]);
    }



    public function consultarEstadoPagoFacilVenta(Venta $venta, PagoCuotasService $pagoCuotasService)
    {
        $transaccion = PagoFacilTransaccion::where('id_venta', $venta->id)
            ->whereNull('id_cuota')
            ->latest('id')
            ->first();

        if (! $transaccion) {
            return response()->json([
                'pagado' => $venta->estado === 'pagado',
                'estado' => $venta->estado,
                'message' => 'No existe una transacción PagoFácil asociada a esta venta.',
                'venta' => $venta->fresh(['cliente']),
            ], 404);
        }

        $ventaActual = $venta->fresh(['cliente', 'plan', 'cuotas']);
        if ($transaccion->estado === 'confirmado' || $ventaActual->estado === 'pagado' || $ventaActual->saldoActual() <= 0) {
            return response()->json([
                'pagado' => true,
                'estado_pagofacil' => 'confirmado_local',
                'estado_transaccion' => $transaccion->estado,
                'message' => 'Pago confirmado por callback o registro local.',
                'venta' => $ventaActual,
            ]);
        }

        return $this->consultarYProcesarTransaccionPagoFacil($transaccion, $pagoCuotasService);
    }

    public function consultarEstadoPagoFacilCuota(Cuota $cuota, PagoCuotasService $pagoCuotasService)
    {
        $transaccion = PagoFacilTransaccion::where('id_cuota', $cuota->id)
            ->latest('id')
            ->first();

        if (! $transaccion) {
            return response()->json([
                'pagado' => $cuota->estado === 'pagado',
                'estado' => $cuota->estado,
                'message' => 'No existe una transacción PagoFácil asociada a esta cuota.',
                'cuota' => $cuota->fresh(['venta.cliente']),
            ], 404);
        }

        $cuotaActual = $cuota->fresh(['venta.cliente']);
        if ($transaccion->estado === 'confirmado' || $cuotaActual->estado === 'pagado' || $cuotaActual->saldo() <= 0) {
            return response()->json([
                'pagado' => true,
                'estado_pagofacil' => 'confirmado_local',
                'estado_transaccion' => $transaccion->estado,
                'message' => 'Pago confirmado por callback o registro local.',
                'cuota' => $cuotaActual,
                'venta' => $cuotaActual->venta,
            ]);
        }

        return $this->consultarYProcesarTransaccionPagoFacil($transaccion, $pagoCuotasService);
    }

    protected function consultarYProcesarTransaccionPagoFacil(PagoFacilTransaccion $transaccion, PagoCuotasService $pagoCuotasService)
    {
        try {
            $respuesta = $this->pagoService->consultarTransaccion($transaccion);
            $pagadoPagoFacil = $this->pagoService->pagoConfirmado($respuesta);
            $estadoNormalizado = $this->pagoService->estadoTransaccion($respuesta);
            $descripcionEstado = $this->pagoService->descripcionEstado($respuesta);

            if ($pagadoPagoFacil && $transaccion->estado !== 'confirmado') {
                $this->registrarPagoDesdeConsultaPagoFacil($transaccion, $respuesta, $pagoCuotasService);
            }

            $venta = $transaccion->venta?->fresh(['cliente', 'plan', 'cuotas']);
            $cuota = $transaccion->cuota?->fresh(['venta.cliente']);
            $transaccionActual = $transaccion->fresh();

            return response()->json([
                'pagado' => ($venta?->estado === 'pagado') || ($cuota?->estado === 'pagado'),
                'estado_pagofacil' => $respuesta['values']['paymentStatus'] ?? $respuesta['values']['status'] ?? null,
                'descripcion_pagofacil' => $descripcionEstado,
                'estado_transaccion' => $transaccionActual->estado,
                'estado_normalizado' => $estadoNormalizado,
                'message' => $this->pagoService->mensajeEstado($estadoNormalizado),
                'respuesta' => $respuesta,
                'venta' => $venta,
                'cuota' => $cuota,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'pagado' => false,
                'estado_transaccion' => $transaccion->estado,
                'message' => $e->getMessage(),
                'venta' => $transaccion->venta?->fresh(['cliente', 'plan', 'cuotas']),
                'cuota' => $transaccion->cuota?->fresh(['venta.cliente']),
            ], 200);
        }
    }

    protected function registrarPagoDesdeConsultaPagoFacil(PagoFacilTransaccion $transaccion, array $respuesta, PagoCuotasService $pagoCuotasService): void
    {
        $transactionId = $transaccion->transaction_id;

        if ($transactionId && Pago::where('transaction_id', $transactionId)->exists()) {
            $transaccion->update([
                'estado' => 'confirmado',
                'fecha_actualizacion' => now(),
            ]);
            return;
        }

        if ($transaccion->id_cuota) {
            $cuota = Cuota::with('venta')->findOrFail($transaccion->id_cuota);

            if ($cuota->estado === 'pagado' || $cuota->saldo() <= 0) {
                $transaccion->update([
                    'estado' => 'confirmado',
                    'fecha_actualizacion' => now(),
                ]);
                return;
            }

            $montoRealSistema = round($cuota->saldo(), 2);
            $pago = $pagoCuotasService->registrarPagoVenta($cuota->id_venta, [
                'id_cuota' => $cuota->id,
                'monto' => $montoRealSistema,
                'tipo_pago' => 'PagoFacil',
                'referencia' => $transaccion->payment_number,
                'transaction_id' => $transactionId,
                'observaciones' => 'Pago confirmado mediante consulta de estado PagoFácil. QR de prototipo cobrado por Bs ' . number_format((float) $transaccion->monto, 2, '.', '') . '.',
            ]);

            $this->marcarTransaccionConfirmada($transaccion, $respuesta, $pago->id, $montoRealSistema);
            return;
        }

        $venta = Venta::findOrFail($transaccion->id_venta);

        if ($venta->estado === 'pagado' || $venta->saldoActual() <= 0) {
            $transaccion->update([
                'estado' => 'confirmado',
                'fecha_actualizacion' => now(),
            ]);
            return;
        }

        $montoRealSistema = round($venta->saldoActual(), 2);
        $pago = $pagoCuotasService->registrarPagoVenta($venta->id, [
            'monto' => $montoRealSistema,
            'tipo_pago' => 'PagoFacil',
            'referencia' => $transaccion->payment_number,
            'transaction_id' => $transactionId,
            'observaciones' => 'Pago confirmado mediante consulta de estado PagoFácil. QR de prototipo cobrado por Bs ' . number_format((float) $transaccion->monto, 2, '.', '') . '.',
        ]);

        $this->marcarTransaccionConfirmada($transaccion, $respuesta, $pago->id, $montoRealSistema);
    }

    protected function marcarTransaccionConfirmada(PagoFacilTransaccion $transaccion, array $respuesta, int $pagoId, float $montoRealSistema): void
    {
        $webhookJson = $transaccion->webhook_json ?? [];
        $webhookJson['consulta_estado_pagofacil'] = $respuesta;
        $webhookJson['control_sistema'] = [
            'modo' => 'prototipo_monto_prueba',
            'monto_qr_confirmado' => (float) $transaccion->monto,
            'monto_real_registrado' => $montoRealSistema,
            'confirmado_por' => 'consulta_query_transaction',
        ];

        $transaccion->update([
            'id_pago' => $pagoId,
            'estado' => 'confirmado',
            'webhook_json' => $webhookJson,
            'fecha_actualizacion' => now(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PlanPago $planPago)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PlanPago $planPago)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $plan = PlanPago::findOrFail($id);
        $validated = $request->validate([
            'cantidad_cuotas' => 'sometimes|integer|min:1',
            'monto_cuota' => 'sometimes|numeric|min:0.01',
            'total_deuda' => 'sometimes|numeric|min:0.01',
            'saldo_restante' => 'sometimes|numeric|min:0',
            'fecha_inicio' => 'sometimes|date',
            'frecuencia' => 'sometimes|in:semanal,quincenal,mensual',
            'estado' => 'sometimes|in:pendiente,en_curso,finalizado',
            'observaciones' => 'nullable|string',
        ]);

        $plan->update($validated);

        return to_route('planes.index')->with('success', 'Plan de Pago actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $plan = PlanPago::findOrFail($id);
        $plan->update(['state' => 'i']);

        return to_route('planes.index')->with('success', 'Plan de pago eliminado exitosamente.');
    }
}

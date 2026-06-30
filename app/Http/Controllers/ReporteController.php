<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Contador;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\User;
use App\Models\Pago;
use App\Models\Cuota;
use App\Models\Producto;
use App\Models\Cliente;
use Carbon\Carbon;
use App\Models\Inventario;
use App\Models\PagoProveedor;
use App\Models\Compra;
use App\Models\AuditoriaAccion;
use DateTime;
use Inertia\Inertia;

class ReporteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // ============================
        // 1. Cantidad de ventas
        // ============================
        $cantidadVentas = Venta::where('state', 'a')->count();

        // ============================
        // 2. Total realmente recaudado
        // ============================

        // Pagos individuales
        $recaudoPagos = Pago::where('state', 'a')
            ->sum('monto');

        // Cuotas pagadas
        $recaudoCuotas = Cuota::where('state', 'a')
            ->where('estado', 'pagado')
            ->sum('monto');

        $cantidadVendida = $recaudoPagos + $recaudoCuotas;

        // ============================
        // 3. Cantidad de clientes
        // ============================
        $cantidadClientes = Cliente::where('state', 'a')->count();

        // ============================
        // 4. Cantidad de visitas
        // ============================
        $cantidadVisitas = Contador::sum('visitas');

        // ============================
        // 5. Ventas por mes
        // ============================
        $ventasMes = Venta::selectRaw("EXTRACT(MONTH FROM fecha_venta) AS mes, COUNT(*) AS cantidad")
            ->where('state', 'a')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $mes = [];
        $cantidad = [];

        foreach ($ventasMes as $item) {
            $fecha = DateTime::createFromFormat('!m', $item->mes);
            $mes[] = $fecha->format('F');
            $cantidad[] = $item->cantidad;
        }

        // ============================
        // 6. Ventas por día
        // ============================
        $ventasDia = Venta::selectRaw("TO_CHAR(fecha_venta, 'YYYY-MM-DD') AS dia, COUNT(*) AS cantidad")
            ->where('state', 'a')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $dias = [];
        $cantidadDias = [];

        foreach ($ventasDia as $item) {
            $dias[] = $item->dia;
            $cantidadDias[] = $item->cantidad;
        }

        // ============================
        // 7. Productos más vendidos
        // ============================
        $productosTop = DB::select("
            SELECT p.nombre AS producto, SUM(dv.cantidad) AS total_vendido
            FROM productos p
            INNER JOIN detalle_venta dv ON p.id = dv.id_producto
            INNER JOIN ventas v ON dv.id_venta = v.id
            WHERE v.state = 'a'
            GROUP BY p.id, p.nombre
            ORDER BY total_vendido DESC
            LIMIT 5
        ");

        // ============================
        // RESPUESTA FINAL
        // ============================
        return Inertia::render('Reportes/Index', [
            'cantidadVentas' => $cantidadVentas,
            'cantidadVendida' => $cantidadVendida,
            'cantidadClientes' => $cantidadClientes,
            'cantidadVisitas' => $cantidadVisitas,
            'mes' => $mes,
            'cantidad' => $cantidad,
            'dias' => $dias,
            'cantidadDias' => $cantidadDias,
            'productosTop' => $productosTop,
        ]);
    }

    public function indexVue()
    {
        $cantidadVentas = DB::table('ventas')
            ->where('state', 'a')
            ->count();

        $cantidadVendida = DB::table('ventas')
            ->where('state', 'a')
            ->sum('total');

        $cantidadClientes = DB::table('clientes')
            ->where('state', 'a')
            ->count();

        $cantidadVisitas = DB::table('contadors')
            ->sum('visitas');

        $ventasMes = DB::select("select date_part('month', fecha_venta) as mes, count(*) as cantidad 
                                from ventas 
                                where state='a' 
                                group by mes 
                                order by mes asc");

        $mes = [];
        $cantidad = [];
        foreach ($ventasMes as $item) {
            $fecha = DateTime::createFromFormat('!m', $item->mes);
            array_push($mes, $fecha->format('F'));
            array_push($cantidad, $item->cantidad);
        };
        //dd($mes, $cantidad);

        $ventasDia = DB::select("select TO_CHAR(fecha_venta, 'YYYY-MM-DD') as dia, count(*) as cantidad 
                                from ventas 
                                where state='a' 
                                group by dia 
                                order by dia asc");

        $dias = [];
        $cantidadDias = [];
        foreach ($ventasDia as $item) {
            array_push($dias, $item->dia);
            array_push($cantidadDias, $item->cantidad);
        }
        //dd($dias, $cantidadDias);

        $productosTop = DB::select("
            SELECT p.nombre AS producto, SUM(dv.cantidad) AS total_vendido
            FROM productos p
            INNER JOIN detalle_venta dv ON p.id = dv.id_producto
            INNER JOIN ventas v ON dv.id_venta = v.id
            WHERE v.state = 'a'
            GROUP BY p.id, p.nombre
            ORDER BY total_vendido DESC
            LIMIT 5
        ");

        // return view('Reportes.index', compact('cantidadVentas','cantidadVendida','cantidadClientes','cantidadVisitas','mes','cantidad','dias','cantidadDias','productosTop'));

        return Inertia::render('Reportes/Index', [
            'cantidadVentas' => $cantidadVentas,
            'cantidadVendida' => $cantidadVendida,
            'cantidadClientes' => $cantidadClientes,
            'cantidadVisitas' => $cantidadVisitas,
            'mes' => $mes,
            'cantidad' => $cantidad,
            'dias' => $dias,
            'cantidadDias' => $cantidadDias,
            'productosTop' => $productosTop,
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    /**
     * Panel financiero-comercial: cuentas por cobrar, cuentas por pagar,
     * cuotas vencidas, stock bajo y movimientos recientes de inventario.
     */
    public function financiero(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = $validated['fecha_inicio'] ?? now()->startOfMonth()->toDateString();
        $fechaFin = $validated['fecha_fin'] ?? now()->toDateString();

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();
        $hoy = now()->toDateString();

        $ventasBase = Venta::where('state', 'a')
            ->whereBetween('fecha_venta', [$inicio->toDateString(), $fin->toDateString()]);

        $pagosBase = Pago::where('state', 'a')
            ->whereIn('estado_pago', ['pagado', 'parcial', 'excedente'])
            ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()]);

        $comprasBase = Compra::where('state', 'a')->where('estado_aprobacion', 'aprobada')
            ->whereBetween('fecha_compra', [$inicio->toDateString(), $fin->toDateString()]);

        $pagosProveedorBase = PagoProveedor::where('state', 'a')
            ->where('estado', 'confirmado')
            ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()]);

        $cuotasVencidasQuery = Cuota::with(['venta.cliente', 'venta.usuario', 'planPago'])
            ->where('state', 'a')
            ->where('estado', '!=', 'pagado')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->orderBy('fecha_vencimiento');

        $cuotasVencidas = $cuotasVencidasQuery->get()->map(function (Cuota $cuota) use ($hoy) {
            $saldo = $cuota->saldo();
            return [
                'id' => $cuota->id,
                'id_venta' => $cuota->id_venta,
                'cliente' => $cuota->venta?->cliente?->nombreCompleto() ?? 'Sin cliente',
                'vendedor' => $cuota->venta?->usuario?->nombre ?? null,
                'fecha_vencimiento' => optional($cuota->fecha_vencimiento)->format('Y-m-d'),
                'monto' => (float) $cuota->monto,
                'monto_pagado' => (float) ($cuota->monto_pagado ?? 0),
                'saldo' => $saldo,
                'estado' => $cuota->estado,
                'dias_mora' => $cuota->fecha_vencimiento
                    ? Carbon::parse($cuota->fecha_vencimiento)->diffInDays(Carbon::parse($hoy))
                    : 0,
            ];
        })->filter(fn ($cuota) => $cuota['saldo'] > 0)->values();

        $cuentasPorCobrar = Venta::with(['cliente', 'usuario'])
            ->where('state', 'a')
            ->where('saldo', '>', 0)
            ->orderByDesc('saldo')
            ->limit(50)
            ->get()
            ->map(fn (Venta $venta) => [
                'id' => $venta->id,
                'fecha_venta' => optional($venta->fecha_venta)->format('Y-m-d'),
                'cliente' => $venta->cliente?->nombreCompleto() ?? 'Sin cliente',
                'vendedor' => $venta->usuario?->nombre ?? null,
                'total' => (float) $venta->total,
                'monto_pagado' => (float) $venta->monto_pagado,
                'saldo' => (float) $venta->saldo,
                'tipo_pago' => $venta->tipo_pago,
                'estado' => $venta->estado,
            ]);

        $cuentasPorPagar = Compra::with(['proveedor', 'usuario'])
            ->where('state', 'a')
            ->where('estado_aprobacion', 'aprobada')
            ->where('saldo', '>', 0)
            ->orderByDesc('saldo')
            ->limit(50)
            ->get()
            ->map(fn (Compra $compra) => [
                'id' => $compra->id,
                'fecha_compra' => optional($compra->fecha_compra)->format('Y-m-d'),
                'proveedor' => $compra->proveedor?->nombre ?? 'Sin proveedor',
                'usuario' => $compra->usuario?->nombre ?? null,
                'total' => (float) $compra->total,
                'monto_pagado' => (float) $compra->monto_pagado,
                'saldo' => (float) $compra->saldo,
                'estado' => $compra->estado,
            ]);

        $stockBajo = Producto::where('state', 'a')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->limit(50)
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'stock' => (int) $producto->stock,
                'stock_minimo' => (int) $producto->stock_minimo,
                'precio_compra' => (float) ($producto->precio_compra ?? 0),
                'precio_venta' => (float) ($producto->precio_venta ?? $producto->precio ?? 0),
            ]);

        $movimientosInventario = Inventario::with('producto')
            ->where('state', 'a')
            ->latest('fecha')
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (Inventario $movimiento) => [
                'id' => $movimiento->id,
                'fecha' => optional($movimiento->fecha)->format('Y-m-d'),
                'producto' => $movimiento->producto?->nombre ?? 'Sin producto',
                'tipo' => $movimiento->tipo,
                'cantidad' => (int) $movimiento->cantidad,
                'descripcion' => $movimiento->descripcion,
            ]);

        $pagosRecientes = Pago::with(['cliente', 'venta', 'cuota'])
            ->where('state', 'a')
            ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
            ->latest('fecha_pago')
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(fn (Pago $pago) => [
                'id' => $pago->id,
                'fecha_pago' => optional($pago->fecha_pago)->format('Y-m-d'),
                'cliente' => $pago->cliente?->nombreCompleto() ?? $pago->venta?->cliente?->nombreCompleto() ?? 'Sin cliente',
                'id_venta' => $pago->id_venta,
                'id_cuota' => $pago->id_cuota,
                'monto' => (float) $pago->monto,
                'tipo_pago' => $pago->tipo_pago,
                'estado_pago' => $pago->estado_pago,
                'referencia' => $pago->referencia,
            ]);

        $pagosProveedorRecientes = PagoProveedor::with(['proveedor', 'compra', 'usuario'])
            ->where('state', 'a')
            ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
            ->latest('fecha_pago')
            ->latest('id')
            ->limit(15)
            ->get()
            ->map(fn (PagoProveedor $pago) => [
                'id' => $pago->id,
                'fecha_pago' => optional($pago->fecha_pago)->format('Y-m-d'),
                'proveedor' => $pago->proveedor?->nombre ?? 'Sin proveedor',
                'id_compra' => $pago->id_compra,
                'monto' => (float) $pago->monto,
                'metodo_pago' => $pago->metodo_pago,
                'estado' => $pago->estado,
                'referencia' => $pago->referencia,
            ]);

        $resumen = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'ventas_total' => (float) (clone $ventasBase)->sum('total'),
            'ventas_recaudado' => (float) (clone $pagosBase)->sum('monto'),
            'cuentas_por_cobrar' => (float) Venta::where('state', 'a')->where('saldo', '>', 0)->sum('saldo'),
            'cuotas_vencidas_total' => round($cuotasVencidas->sum('saldo'), 2),
            'compras_total' => (float) (clone $comprasBase)->sum('total'),
            'proveedores_pagado' => (float) (clone $pagosProveedorBase)->sum('monto'),
            'cuentas_por_pagar' => (float) Compra::where('state', 'a')->where('estado_aprobacion', 'aprobada')->where('saldo', '>', 0)->sum('saldo'),
            'ventas_pendientes' => Venta::where('state', 'a')->where('saldo', '>', 0)->count(),
            'compras_pendientes' => Compra::where('state', 'a')->where('estado_aprobacion', 'aprobada')->where('saldo', '>', 0)->count(),
            'cuotas_vencidas' => $cuotasVencidas->count(),
            'productos_stock_bajo' => $stockBajo->count(),
        ];

        return Inertia::render('Reportes/Financiero', [
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
            'resumen' => $resumen,
            'cuentasPorCobrar' => $cuentasPorCobrar,
            'cuentasPorPagar' => $cuentasPorPagar,
            'cuotasVencidas' => $cuotasVencidas,
            'stockBajo' => $stockBajo,
            'movimientosInventario' => $movimientosInventario,
            'pagosRecientes' => $pagosRecientes,
            'pagosProveedorRecientes' => $pagosProveedorRecientes,
        ]);
    }

    public function exportarFinancieroCsv(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = $validated['fecha_inicio'] ?? now()->startOfMonth()->toDateString();
        $fechaFin = $validated['fecha_fin'] ?? now()->toDateString();
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();
        $hoy = now()->toDateString();

        $filename = 'reporte_financiero_' . $fechaInicio . '_a_' . $fechaFin . '.csv';

        return response()->streamDownload(function () use ($inicio, $fin, $fechaInicio, $fechaFin, $hoy) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $writeTitle = function (string $title) use ($out) {
                fputcsv($out, []);
                fputcsv($out, [$title]);
            };

            fputcsv($out, ['REPORTE FINANCIERO']);
            fputcsv($out, ['Periodo', $fechaInicio . ' al ' . $fechaFin]);
            fputcsv($out, ['Generado', now()->format('Y-m-d H:i:s')]);

            $ventasTotal = Venta::where('state', 'a')
                ->whereBetween('fecha_venta', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('total');
            $recaudado = Pago::where('state', 'a')
                ->where('estado_pago', 'pagado')
                ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('monto');
            $cuentasCobrar = Venta::where('state', 'a')->where('saldo', '>', 0)->sum('saldo');
            $cuotasVencidasTotal = Cuota::where('state', 'a')
                ->where('estado', '!=', 'pagado')
                ->whereDate('fecha_vencimiento', '<', $hoy)
                ->get()
                ->sum(fn (Cuota $cuota) => $cuota->saldo());
            $comprasTotal = Compra::where('state', 'a')->where('estado_aprobacion', 'aprobada')
                ->whereBetween('fecha_compra', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('total');
            $cuentasPagar = Compra::where('state', 'a')->where('estado_aprobacion', 'aprobada')->where('saldo', '>', 0)->sum('saldo');
            $pagadoProveedores = PagoProveedor::where('state', 'a')
                ->where('estado', 'confirmado')
                ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('monto');

            $writeTitle('RESUMEN');
            fputcsv($out, ['Indicador', 'Valor']);
            fputcsv($out, ['Ventas del periodo', number_format((float) $ventasTotal, 2, '.', '')]);
            fputcsv($out, ['Recaudado de clientes', number_format((float) $recaudado, 2, '.', '')]);
            fputcsv($out, ['Cuentas por cobrar', number_format((float) $cuentasCobrar, 2, '.', '')]);
            fputcsv($out, ['Cuotas vencidas', number_format((float) $cuotasVencidasTotal, 2, '.', '')]);
            fputcsv($out, ['Compras del periodo', number_format((float) $comprasTotal, 2, '.', '')]);
            fputcsv($out, ['Cuentas por pagar', number_format((float) $cuentasPagar, 2, '.', '')]);
            fputcsv($out, ['Pagado a proveedores', number_format((float) $pagadoProveedores, 2, '.', '')]);

            $writeTitle('CUENTAS POR COBRAR');
            fputcsv($out, ['Venta', 'Fecha', 'Cliente', 'Total', 'Pagado', 'Saldo', 'Estado']);
            Venta::with('cliente')
                ->where('state', 'a')
                ->where('saldo', '>', 0)
                ->orderByDesc('saldo')
                ->cursor()
                ->each(function (Venta $venta) use ($out) {
                    fputcsv($out, [
                        $venta->id,
                        optional($venta->fecha_venta)->format('Y-m-d'),
                        $venta->cliente?->nombreCompleto() ?? 'Sin cliente',
                        number_format((float) $venta->total, 2, '.', ''),
                        number_format((float) $venta->monto_pagado, 2, '.', ''),
                        number_format((float) $venta->saldo, 2, '.', ''),
                        $venta->estado,
                    ]);
                });

            $writeTitle('CUOTAS VENCIDAS');
            fputcsv($out, ['Cuota', 'Venta', 'Cliente', 'Vencimiento', 'Monto', 'Pagado', 'Saldo', 'Estado']);
            Cuota::with('venta.cliente')
                ->where('state', 'a')
                ->where('estado', '!=', 'pagado')
                ->whereDate('fecha_vencimiento', '<', $hoy)
                ->orderBy('fecha_vencimiento')
                ->cursor()
                ->each(function (Cuota $cuota) use ($out) {
                    fputcsv($out, [
                        $cuota->id,
                        $cuota->id_venta,
                        $cuota->venta?->cliente?->nombreCompleto() ?? 'Sin cliente',
                        optional($cuota->fecha_vencimiento)->format('Y-m-d'),
                        number_format((float) $cuota->monto, 2, '.', ''),
                        number_format((float) $cuota->monto_pagado, 2, '.', ''),
                        number_format((float) $cuota->saldo(), 2, '.', ''),
                        $cuota->estado,
                    ]);
                });

            $writeTitle('CUENTAS POR PAGAR');
            fputcsv($out, ['Compra', 'Fecha', 'Proveedor', 'Total', 'Pagado', 'Saldo', 'Estado']);
            Compra::with('proveedor')
                ->where('state', 'a')
                ->where('estado_aprobacion', 'aprobada')
                ->where('saldo', '>', 0)
                ->orderByDesc('saldo')
                ->cursor()
                ->each(function (Compra $compra) use ($out) {
                    fputcsv($out, [
                        $compra->id,
                        optional($compra->fecha_compra)->format('Y-m-d'),
                        $compra->proveedor?->nombre ?? 'Sin proveedor',
                        number_format((float) $compra->total, 2, '.', ''),
                        number_format((float) $compra->monto_pagado, 2, '.', ''),
                        number_format((float) $compra->saldo, 2, '.', ''),
                        $compra->estado,
                    ]);
                });

            $writeTitle('STOCK BAJO');
            fputcsv($out, ['Código', 'Producto', 'Stock', 'Stock mínimo', 'Precio compra', 'Precio venta']);
            Producto::where('state', 'a')
                ->whereColumn('stock', '<=', 'stock_minimo')
                ->orderBy('stock')
                ->cursor()
                ->each(function (Producto $producto) use ($out) {
                    fputcsv($out, [
                        $producto->codigo,
                        $producto->nombre,
                        $producto->stock,
                        $producto->stock_minimo,
                        number_format((float) ($producto->precio_compra ?? 0), 2, '.', ''),
                        number_format((float) ($producto->precio_venta ?? $producto->precio ?? 0), 2, '.', ''),
                    ]);
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function buscador(Request $request)
    {
        $validated = $request->validate([
            'buscar' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[\pL\pN\s@._\-]+$/u'],
        ], [
            'buscar.required' => 'Debe ingresar un texto para realizar la búsqueda.',
            'buscar.min' => 'La búsqueda debe tener al menos 2 caracteres.',
            'buscar.max' => 'La búsqueda no debe superar 60 caracteres.',
            'buscar.regex' => 'La búsqueda contiene caracteres no permitidos.',
        ]);

        //$rutaTecnos = 'http://127.0.0.1:8000/';
        $rutaTecnos = '/';
        $search = mb_strtolower(trim($validated['buscar']));
        $tablas = [
            ['users', 'email', 'usuario'],
            ['users', 'nombre', 'usuario'],
            ['users', 'telefono', 'usuario'],
            ['roles', 'nombre', 'rol'],
            ['privilegios', 'funcionalidad', 'privilegio'],
            ['clientes', 'nombre', 'cliente'],
            ['clientes', 'direccion', 'cliente'],
            ['clientes', 'telefono', 'cliente'],
            ['pagos', 'tipo_pago', 'pago'],
            ['pagos', 'estado_pago', 'pago'],
            ['pagos', 'fecha_pago', 'pago'],
            ['ventas', 'estado', 'venta'],
            ['ventas', 'fecha_venta', 'venta'],
            ['ventas', 'total', 'venta'],
            ['inventario', 'descripcion', 'inventario'],
            ['inventario', 'tipo', 'inventario'],
            ['productos', 'nombre', 'producto'],
            ['productos', 'descripcion', 'producto'],
            ['categorias', 'nombre', 'categoria'],
        ];

        $data = [];

        foreach ($tablas as $tabla) {
            // Convertimos la columna a text para evitar errores con números o fechas
            $columnText = "$tabla[1]::text";

            $resultados = DB::table($tabla[0])
                ->select(DB::raw("'$rutaTecnos$tabla[2]' as ruta, $tabla[1] as nombre, '$tabla[0]' as modelo"))
                ->whereRaw("lower($columnText) like ?", ["%$search%"])
                ->get();

            $data = array_merge($data, $resultados->toArray());
        }

        // return view('Estadisticas.index', compact('data'));
        return Inertia::render('Estadisticas/Index', [
            'data' => $data,
        ]);
    }

    public function estadisticas()
    {
        $datos = Contador::select('nombre', 'visitas')
            ->orderByDesc('visitas')
            ->orderBy('nombre')
            ->get();

        $recursosMasAccedidos = AuditoriaAccion::select('entidad_tipo as recurso', DB::raw('COUNT(*) as total'))
            ->where('state', 'a')
            ->where('modulo', 'Acceso')
            ->where('accion', 'recurso_visitado')
            ->whereNotNull('entidad_tipo')
            ->groupBy('entidad_tipo')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return Inertia::render('Estadisticas/Resultado', [
            'datos' => $datos,
            'recursosMasAccedidos' => $recursosMasAccedidos,
        ]);
    }

    public function cargarEstilo(Request $request, $id)
    {
        $validated = validator(
            ['estilo' => $id],
            ['estilo' => 'required|integer|in:1,2,3,4,5,6'],
            [
                'estilo.required' => 'Debe seleccionar un tema visual.',
                'estilo.integer' => 'El tema seleccionado no es válido.',
                'estilo.in' => 'El tema seleccionado no está disponible.',
            ]
        )->validate();

        $usuario = $request->user();
        $usuario->estilo = (int) $validated['estilo'];
        $usuario->save();

        return redirect()->back()->with('success', 'Tema visual actualizado correctamente.');
    }
}

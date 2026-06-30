<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Cuota;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\Pago;
use App\Models\PagoFacilTransaccion;
use App\Models\PagoProveedor;
use App\Models\PlanPago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmpacarOperacionesDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedComprasConAprobacion();
            $this->seedVentasContadoYElectronicas();
            $this->seedVentasConCuotas();
            $this->seedMovimientosManualInventario();
        });
    }

    private function usuario(string $email): Usuario
    {
        return Usuario::where('email', $email)->firstOrFail();
    }

    private function proveedor(string $nit): Proveedor
    {
        return Proveedor::where('nit', $nit)->firstOrFail();
    }

    private function cliente(string $documento): Cliente
    {
        return Cliente::where('documento', $documento)->firstOrFail();
    }

    private function producto(string $codigo): Producto
    {
        return Producto::where('codigo', $codigo)->firstOrFail();
    }

    private function seedComprasConAprobacion(): void
    {
        $inventario = $this->usuario('inventario@empacar.local');
        $admin = $this->usuario('admin@empacar.local');

        $comprasAprobadas = [
            [
                'referencia' => 'SOL-COMP-REC-001',
                'proveedor_nit' => '4567890012',
                'producto_codigo' => 'REC-HOJ-PET',
                'cantidad' => 1000,
                'precio' => 4.50,
                'descuento' => 0,
                'monto_pagado' => 0,
                'metodo' => 'otro',
                'observaciones' => 'Solicitud de compra de materia prima reciclada PET para abastecimiento de producción.',
                'observacion_aprobacion' => 'Aprobada para mantener stock de materia prima reciclada.',
            ],
            [
                'referencia' => 'TRF-RES-PET-002',
                'proveedor_nit' => '3456789011',
                'producto_codigo' => 'REC-RES-PET',
                'cantidad' => 600,
                'precio' => 6.80,
                'descuento' => 0,
                'monto_pagado' => 2000,
                'metodo' => 'transferencia',
                'observaciones' => 'Solicitud de compra de resina PET reciclada para procesos de transformación plástica.',
                'observacion_aprobacion' => 'Aprobada con pago inicial por transferencia bancaria.',
            ],
            [
                'referencia' => 'TRF-COR-003',
                'proveedor_nit' => '5678901234',
                'producto_codigo' => 'COR-CAJ-MED',
                'cantidad' => 1000,
                'precio' => 3.80,
                'descuento' => 0,
                'monto_pagado' => 3800,
                'metodo' => 'transferencia',
                'observaciones' => 'Solicitud de compra de cajas corrugadas para abastecimiento comercial.',
                'observacion_aprobacion' => 'Aprobada por rotación comercial de cajas corrugadas.',
            ],
            [
                'referencia' => 'REC-KRAFT-004',
                'proveedor_nit' => '2345678901',
                'producto_codigo' => 'KRF-BOL-MAR',
                'cantidad' => 1500,
                'precio' => 0.95,
                'descuento' => 0,
                'monto_pagado' => 1000,
                'metodo' => 'efectivo',
                'observaciones' => 'Solicitud de compra de bolsas Kraft para clientes gastronómicos y comerciales.',
                'observacion_aprobacion' => 'Aprobada por demanda de clientes gastronómicos.',
            ],
        ];

        foreach ($comprasAprobadas as $data) {
            if (Compra::where('referencia_pago_propuesto', $data['referencia'])->exists()) {
                continue;
            }

            $proveedor = $this->proveedor($data['proveedor_nit']);
            $producto = $this->producto($data['producto_codigo']);
            $subtotal = round($data['cantidad'] * $data['precio'], 2);
            $total = round($subtotal - $data['descuento'], 2);
            $saldo = round($total - $data['monto_pagado'], 2);

            $compra = Compra::create([
                'id_proveedor' => $proveedor->id,
                'id_usuario' => $inventario->id,
                'solicitado_por' => $inventario->id,
                'aprobado_por' => $admin->id,
                'fecha_compra' => '2026-06-28',
                'fecha_solicitud' => '2026-06-28 08:30:00',
                'fecha_aprobacion' => '2026-06-28 10:00:00',
                'subtotal' => $subtotal,
                'descuento' => $data['descuento'],
                'total' => $total,
                'monto_pagado' => $data['monto_pagado'],
                'saldo' => $saldo,
                'estado' => $saldo <= 0 ? 'pagado' : ($data['monto_pagado'] > 0 ? 'parcial' : 'pendiente'),
                'estado_aprobacion' => 'aprobada',
                'observaciones' => $data['observaciones'],
                'metodo_pago_propuesto' => $data['metodo'],
                'referencia_pago_propuesto' => $data['referencia'],
                'observacion_aprobacion' => $data['observacion_aprobacion'],
                'stock_aplicado' => true,
                'state' => 'a',
            ]);

            DetalleCompra::create([
                'id_compra' => $compra->id,
                'id_producto' => $producto->id,
                'cantidad' => $data['cantidad'],
                'precio_unitario' => $data['precio'],
                'subtotal' => $subtotal,
                'state' => 'a',
            ]);

            $producto->increment('stock', $data['cantidad']);
            $producto->update([
                'precio_compra' => $data['precio'],
                'fecha_ingreso' => '2026-06-28',
            ]);

            Inventario::create([
                'id_producto' => $producto->id,
                'cantidad' => $data['cantidad'],
                'fecha' => '2026-06-28',
                'tipo' => 'entrada',
                'descripcion' => 'Entrada automática por aprobación de solicitud de compra demo ' . $data['referencia'],
                'state' => 'a',
            ]);

            if ($data['monto_pagado'] > 0) {
                PagoProveedor::create([
                    'id_proveedor' => $proveedor->id,
                    'id_compra' => $compra->id,
                    'id_usuario' => $admin->id,
                    'monto' => $data['monto_pagado'],
                    'fecha_pago' => '2026-06-28',
                    'metodo_pago' => $data['metodo'],
                    'referencia' => $data['referencia'],
                    'estado' => 'confirmado',
                    'observaciones' => 'Pago inicial aplicado al aprobar compra demo.',
                    'state' => 'a',
                ]);
            }
        }

        $this->crearCompraRechazada($inventario, $admin);
    }

    private function crearCompraRechazada(Usuario $inventario, Usuario $admin): void
    {
        $referencia = 'SOL-REV-PP-005';

        if (Compra::where('referencia_pago_propuesto', $referencia)->exists()) {
            return;
        }

        $proveedor = $this->proveedor('6789012345');
        $producto = $this->producto('INY-ENV-PP');
        $cantidad = 1000;
        $precio = 1.40;
        $subtotal = round($cantidad * $precio, 2);

        $compra = Compra::create([
            'id_proveedor' => $proveedor->id,
            'id_usuario' => $inventario->id,
            'solicitado_por' => $inventario->id,
            'aprobado_por' => $admin->id,
            'fecha_compra' => '2026-06-28',
            'fecha_solicitud' => '2026-06-28 09:00:00',
            'fecha_aprobacion' => '2026-06-28 11:00:00',
            'subtotal' => $subtotal,
            'descuento' => 0,
            'total' => $subtotal,
            'monto_pagado' => 0,
            'saldo' => $subtotal,
            'estado' => 'rechazado',
            'estado_aprobacion' => 'rechazada',
            'observaciones' => 'Solicitud de compra de envases PP con precio sujeto a revisión administrativa.',
            'metodo_pago_propuesto' => 'otro',
            'referencia_pago_propuesto' => $referencia,
            'motivo_rechazo' => 'Precio de compra elevado. Solicitar nueva cotización al proveedor.',
            'stock_aplicado' => false,
            'state' => 'a',
        ]);

        DetalleCompra::create([
            'id_compra' => $compra->id,
            'id_producto' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $subtotal,
            'state' => 'a',
        ]);
    }

    private function seedVentasContadoYElectronicas(): void
    {
        $vendedor = $this->usuario('vendedor@empacar.local');

        $ventas = [
            [
                'codigo' => 'REC-VENTA-001',
                'cliente_doc' => '1020304050',
                'producto_codigo' => 'KRF-BOL-MAR',
                'cantidad' => 300,
                'precio' => 1.80,
                'descuento' => 0,
                'monto_pagado' => 540,
                'tipo_pago' => 'contado',
                'metodo' => 'efectivo',
                'observaciones' => 'REC-VENTA-001 - Venta al contado de bolsas Kraft para atención comercial gastronómica.',
            ],
            [
                'codigo' => 'TRF-VENTA-002',
                'cliente_doc' => '5060708090',
                'producto_codigo' => 'COR-VINO-01',
                'cantidad' => 200,
                'precio' => 12.00,
                'descuento' => 0,
                'monto_pagado' => 2400,
                'tipo_pago' => 'contado',
                'metodo' => 'transferencia',
                'observaciones' => 'TRF-VENTA-002 - Venta de cajas corrugadas para presentación y transporte de botellas de vino.',
            ],
            [
                'codigo' => 'QR-VENTA-003',
                'cliente_doc' => '2030405060',
                'producto_codigo' => 'KRF-BOL-BLA-PER',
                'cantidad' => 500,
                'precio' => 2.50,
                'descuento' => 0,
                'monto_pagado' => 1250,
                'tipo_pago' => 'contado',
                'metodo' => 'pagofacil',
                'observaciones' => 'QR-VENTA-003 - Venta con pago electrónico de bolsas Kraft personalizadas para comercio retail.',
                'pagofacil' => true,
            ],
            [
                'codigo' => 'REC-DESC-005',
                'cliente_doc' => '6070809010',
                'producto_codigo' => 'KRF-BOL-MAR',
                'cantidad' => 500,
                'precio' => 1.80,
                'descuento' => 50,
                'monto_pagado' => 850,
                'tipo_pago' => 'contado',
                'metodo' => 'efectivo',
                'observaciones' => 'REC-DESC-005 - Venta de bolsas Kraft con descuento comercial por volumen.',
            ],
        ];

        foreach ($ventas as $data) {
            if (Venta::where('observaciones', $data['observaciones'])->exists()) {
                continue;
            }

            $cliente = $this->cliente($data['cliente_doc']);
            $producto = $this->producto($data['producto_codigo']);
            $subtotal = round($data['cantidad'] * $data['precio'], 2);
            $total = round($subtotal - $data['descuento'], 2);
            $saldo = round($total - $data['monto_pagado'], 2);

            $venta = Venta::create([
                'estado' => $saldo <= 0 ? 'pagado' : 'parcial',
                'fecha_venta' => '2026-06-28',
                'id_cliente' => $cliente->id,
                'id_usuario' => $vendedor->id,
                'subtotal' => $subtotal,
                'descuento' => $data['descuento'],
                'total' => $total,
                'monto_pagado' => $data['monto_pagado'],
                'saldo' => $saldo,
                'tipo_pago' => $data['tipo_pago'],
                'observaciones' => $data['observaciones'],
                'state' => 'a',
            ]);

            DetalleVenta::create([
                'id_venta' => $venta->id,
                'id_producto' => $producto->id,
                'cantidad' => $data['cantidad'],
                'precio' => $data['precio'],
                'subtotal' => $subtotal,
                'state' => 'a',
            ]);

            $producto->decrement('stock', $data['cantidad']);

            $pago = Pago::create([
                'id_plan' => null,
                'id_venta' => $venta->id,
                'id_cliente' => $cliente->id,
                'id_cuota' => null,
                'estado_pago' => 'pagado',
                'fecha_pago' => '2026-06-28',
                'monto' => $data['monto_pagado'],
                'tipo_pago' => $data['metodo'],
                'referencia' => $data['codigo'],
                'transaction_id' => $data['pagofacil'] ?? false ? 'PF-DEMO-' . $venta->id : null,
                'observaciones' => 'Pago inicial/contado demo registrado por seeder.',
                'state' => 'a',
            ]);

            if ($data['pagofacil'] ?? false) {
                $venta->update(['pagofacil_transaction_id' => 'PF-DEMO-' . $venta->id]);

                PagoFacilTransaccion::create([
                    'id_venta' => $venta->id,
                    'id_cuota' => null,
                    'id_pago' => $pago->id,
                    'transaction_id' => 'PF-DEMO-' . $venta->id,
                    'payment_number' => 'V' . $venta->id,
                    'monto' => $data['monto_pagado'],
                    'estado' => 'confirmado',
                    'qr_url' => null,
                    'qr_base64' => null,
                    'request_json' => [
                        'paymentNumber' => 'V' . $venta->id,
                        'amount' => $data['monto_pagado'],
                        'clientName' => $cliente->nombre,
                    ],
                    'response_json' => ['modo' => 'demo', 'mensaje' => 'Transacción PagoFácil simulada por seeder.'],
                    'webhook_json' => ['estado' => 'confirmado', 'origen' => 'seeder_demo'],
                    'fecha_creacion' => now(),
                    'fecha_actualizacion' => now(),
                ]);
            }
        }
    }

    private function seedVentasConCuotas(): void
    {
        $vendedor = $this->usuario('vendedor@empacar.local');

        $this->crearVentaCreditoBebidas($vendedor);
        $this->crearVentaCreditoAvicola($vendedor);
    }

    private function crearVentaCreditoBebidas(Usuario $vendedor): void
    {
        $codigo = 'INI-CRED-004';
        $observaciones = $codigo . ' - Venta a crédito de botellas PET para cliente industrial de bebidas.';

        if (Venta::where('observaciones', $observaciones)->exists()) {
            return;
        }

        $cliente = $this->cliente('3040506070');
        $producto = $this->producto('PET-BOT-500-CR');
        $cantidad = 2000;
        $precio = 1.20;
        $subtotal = 2400.00;
        $total = 2400.00;
        $inicial = 800.00;
        $saldoInicial = 1600.00;

        $venta = Venta::create([
            'estado' => 'parcial',
            'fecha_venta' => '2026-06-28',
            'id_cliente' => $cliente->id,
            'id_usuario' => $vendedor->id,
            'subtotal' => $subtotal,
            'descuento' => 0,
            'total' => $total,
            'monto_pagado' => 1866.66,
            'saldo' => 533.34,
            'tipo_pago' => 'credito',
            'observaciones' => $observaciones,
            'state' => 'a',
        ]);

        DetalleVenta::create([
            'id_venta' => $venta->id,
            'id_producto' => $producto->id,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
            'state' => 'a',
        ]);

        $producto->decrement('stock', $cantidad);

        $plan = PlanPago::create([
            'cantidad_cuotas' => 3,
            'monto_cuota' => 533.33,
            'total_deuda' => $total,
            'monto_inicial' => $inicial,
            'saldo_financiado' => $saldoInicial,
            'saldo_restante' => 533.34,
            'fecha_inicio' => '2026-06-28',
            'frecuencia' => 'mensual',
            'observaciones' => 'Plan demo de 3 cuotas para venta de botellas PET.',
            'estado' => 'en_curso',
            'id_venta' => $venta->id,
            'state' => 'a',
        ]);

        Pago::create([
            'id_plan' => $plan->id,
            'id_venta' => $venta->id,
            'id_cliente' => $cliente->id,
            'estado_pago' => 'pagado',
            'fecha_pago' => '2026-06-28',
            'monto' => $inicial,
            'tipo_pago' => 'efectivo',
            'referencia' => $codigo,
            'observaciones' => 'Pago inicial de venta a crédito demo.',
            'state' => 'a',
        ]);

        $cuotas = [
            ['n' => 1, 'monto' => 533.33, 'fecha' => '2026-07-28', 'pagada' => true, 'referencia' => 'REC-CUOTA-001', 'metodo' => 'efectivo'],
            ['n' => 2, 'monto' => 533.33, 'fecha' => '2026-08-28', 'pagada' => true, 'referencia' => 'TRF-CUOTA-002', 'metodo' => 'transferencia'],
            ['n' => 3, 'monto' => 533.34, 'fecha' => '2026-09-28', 'pagada' => false, 'referencia' => null, 'metodo' => null],
        ];

        foreach ($cuotas as $item) {
            $cuota = Cuota::create([
                'id_venta' => $venta->id,
                'id_plan_pago' => $plan->id,
                'monto' => $item['monto'],
                'fecha_vencimiento' => $item['fecha'],
                'estado' => $item['pagada'] ? 'pagado' : 'pendiente',
                'monto_pagado' => $item['pagada'] ? $item['monto'] : 0,
                'fecha_pago' => $item['pagada'] ? '2026-06-28' : null,
                'state' => 'a',
            ]);

            if ($item['pagada']) {
                $pago = Pago::create([
                    'id_plan' => $plan->id,
                    'id_venta' => $venta->id,
                    'id_cliente' => $cliente->id,
                    'id_cuota' => $cuota->id,
                    'estado_pago' => 'pagado',
                    'fecha_pago' => '2026-06-28',
                    'monto' => $item['monto'],
                    'tipo_pago' => $item['metodo'],
                    'referencia' => $item['referencia'],
                    'observaciones' => 'Pago de cuota demo de venta PET.',
                    'state' => 'a',
                ]);

                $cuota->update(['id_pago' => $pago->id]);
            }
        }
    }

    private function crearVentaCreditoAvicola(Usuario $vendedor): void
    {
        $codigo = 'INI-CRED-006';
        $observaciones = $codigo . ' - Venta a crédito de cajas corrugadas para despacho de productos avícolas.';

        if (Venta::where('observaciones', $observaciones)->exists()) {
            return;
        }

        $cliente = $this->cliente('4050607080');
        $producto = $this->producto('COR-CAJ-MED');
        $cantidad = 400;
        $precio = 6.50;
        $subtotal = 2600.00;
        $descuento = 100.00;
        $total = 2500.00;
        $inicial = 500.00;
        $saldo = 2000.00;

        $venta = Venta::create([
            'estado' => 'parcial',
            'fecha_venta' => '2026-06-28',
            'id_cliente' => $cliente->id,
            'id_usuario' => $vendedor->id,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
            'monto_pagado' => $inicial,
            'saldo' => $saldo,
            'tipo_pago' => 'credito',
            'observaciones' => $observaciones,
            'state' => 'a',
        ]);

        DetalleVenta::create([
            'id_venta' => $venta->id,
            'id_producto' => $producto->id,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
            'state' => 'a',
        ]);

        $producto->decrement('stock', $cantidad);

        $plan = PlanPago::create([
            'cantidad_cuotas' => 4,
            'monto_cuota' => 500.00,
            'total_deuda' => $total,
            'monto_inicial' => $inicial,
            'saldo_financiado' => $saldo,
            'saldo_restante' => $saldo,
            'fecha_inicio' => '2026-06-28',
            'frecuencia' => 'mensual',
            'observaciones' => 'Plan demo de 4 cuotas para venta de cajas corrugadas.',
            'estado' => 'en_curso',
            'id_venta' => $venta->id,
            'state' => 'a',
        ]);

        Pago::create([
            'id_plan' => $plan->id,
            'id_venta' => $venta->id,
            'id_cliente' => $cliente->id,
            'estado_pago' => 'pagado',
            'fecha_pago' => '2026-06-28',
            'monto' => $inicial,
            'tipo_pago' => 'transferencia',
            'referencia' => $codigo,
            'observaciones' => 'Pago inicial de venta a crédito demo.',
            'state' => 'a',
        ]);

        for ($i = 1; $i <= 4; $i++) {
            Cuota::create([
                'id_venta' => $venta->id,
                'id_plan_pago' => $plan->id,
                'monto' => 500.00,
                'fecha_vencimiento' => Carbon::parse('2026-06-28')->addMonths($i)->toDateString(),
                'estado' => 'pendiente',
                'monto_pagado' => 0,
                'state' => 'a',
            ]);
        }
    }

    private function seedMovimientosManualInventario(): void
    {
        $movimientos = [
            [
                'codigo' => 'PET-PRE-28MM',
                'cantidad' => 100,
                'tipo' => 'salida',
                'descripcion' => 'Salida por muestra técnica para control de calidad.',
            ],
            [
                'codigo' => 'TER-BAN-TRA',
                'cantidad' => 250,
                'tipo' => 'entrada',
                'descripcion' => 'Ingreso por regularización de inventario físico.',
            ],
        ];

        foreach ($movimientos as $data) {
            if (Inventario::where('descripcion', $data['descripcion'])->exists()) {
                continue;
            }

            $producto = $this->producto($data['codigo']);

            if ($data['tipo'] === 'entrada') {
                $producto->increment('stock', $data['cantidad']);
            } else {
                $producto->decrement('stock', $data['cantidad']);
            }

            Inventario::create([
                'id_producto' => $producto->id,
                'cantidad' => $data['cantidad'],
                'fecha' => '2026-06-28',
                'tipo' => $data['tipo'],
                'descripcion' => $data['descripcion'],
                'state' => 'a',
            ]);
        }
    }
}

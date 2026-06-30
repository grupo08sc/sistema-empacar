<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

class MetodoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $metodos = [
            ['codigo' => 'efectivo', 'nombre' => 'Efectivo', 'es_electronico' => false],
            ['codigo' => 'transferencia', 'nombre' => 'Transferencia bancaria', 'es_electronico' => true],
            ['codigo' => 'qr', 'nombre' => 'Pago QR', 'es_electronico' => true],
            ['codigo' => 'pagofacil', 'nombre' => 'PagoFácil', 'es_electronico' => true],
            ['codigo' => 'tarjeta', 'nombre' => 'Tarjeta débito/crédito', 'es_electronico' => true],
            ['codigo' => 'cheque', 'nombre' => 'Cheque', 'es_electronico' => false],
        ];

        foreach ($metodos as $metodo) {
            MetodoPago::firstOrCreate(
                ['codigo' => $metodo['codigo']],
                [
                    'nombre' => $metodo['nombre'],
                    'es_electronico' => $metodo['es_electronico'],
                    'permite_pago_unico' => true,
                    'permite_plan_pagos' => true,
                    'descripcion' => 'Método de pago inicial del sistema.',
                    'state' => 'a',
                ]
            );
        }
    }
}

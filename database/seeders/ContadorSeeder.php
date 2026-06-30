<?php

namespace Database\Seeders;

use App\Models\Contador;
use Illuminate\Database\Seeder;

class ContadorSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            1 => 'Landing',
            2 => 'Login',
            3 => 'Dashboard',
            4 => 'Empresa',
            5 => 'Cliente',
            6 => 'Pago',
            7 => 'Privilegio',
            8 => 'Producto',
            9 => 'Categoria',
            10 => 'Rol',
            11 => 'Usuario',
            12 => 'Venta',
            13 => 'PlanPago',
            14 => 'Reportes',
            15 => 'Auditoria',
            16 => 'Inventario',
            17 => 'Proveedor',
            18 => 'Compra',
            19 => 'Solicitud',
            20 => 'PagoProveedor',
            21 => 'MetodoPago',
            22 => 'Perfil',
            23 => 'Preferencias visuales',
            24 => 'Acceso no autorizado',
        ];

        foreach ($items as $tipo => $nombre) {
            Contador::firstOrCreate(
                ['nombre' => $nombre],
                ['visitas' => 0, 'tipo' => $tipo]
            );
        }
    }
}

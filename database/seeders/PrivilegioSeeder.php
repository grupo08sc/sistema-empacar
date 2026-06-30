<?php

namespace Database\Seeders;

use App\Models\Privilegio;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrivilegioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('privilegios')->delete();

        $modulos = [
            'Administracion', 'Empresa', 'Usuario', 'Rol', 'Privilegio',
            'Cliente', 'Venta', 'Pago', 'PlanPago', 'MetodoPago',
            'Producto', 'Categoria', 'Inventario',
            'Proveedor', 'Compra', 'PagoProveedor', 'Solicitud', 'Departamento',
            'Reportes', 'Auditoria',
        ];

        $roles = Role::whereIn('nombre', [
            'Administrador',
            'Vendedor',
            'Cajero',
            'Encargado de Inventario',
            'Cliente',
        ])->get()->keyBy('nombre');

        if ($roles->has('Administrador')) {
            foreach ($modulos as $modulo) {
                $this->crear($roles['Administrador'], $modulo, true, true, true, true);
            }
        }

        if ($roles->has('Vendedor')) {
            $this->crear($roles['Vendedor'], 'Cliente', true, true, true, false);
            $this->crear($roles['Vendedor'], 'Venta', true, true, true, false);
            $this->crear($roles['Vendedor'], 'Pago', true, true, false, false);
            $this->crear($roles['Vendedor'], 'PlanPago', true, true, true, false);
            $this->crear($roles['Vendedor'], 'MetodoPago', true, false, false, false);
            $this->crear($roles['Vendedor'], 'Producto', true, false, false, false);
            $this->crear($roles['Vendedor'], 'Categoria', true, false, false, false);
            $this->crear($roles['Vendedor'], 'Reportes', true, false, false, false);
        }

        if ($roles->has('Cajero')) {
            $this->crear($roles['Cajero'], 'Cliente', true, false, false, false);
            $this->crear($roles['Cajero'], 'Venta', true, false, false, false);
            $this->crear($roles['Cajero'], 'Pago', true, true, true, false);
            $this->crear($roles['Cajero'], 'PlanPago', true, false, false, false);
            $this->crear($roles['Cajero'], 'MetodoPago', true, false, false, false);
            $this->crear($roles['Cajero'], 'Reportes', true, false, false, false);
        }

        if ($roles->has('Encargado de Inventario')) {
            $this->crear($roles['Encargado de Inventario'], 'Producto', true, true, true, false);
            $this->crear($roles['Encargado de Inventario'], 'Categoria', true, true, true, false);
            $this->crear($roles['Encargado de Inventario'], 'Inventario', true, true, true, false);
            $this->crear($roles['Encargado de Inventario'], 'Proveedor', true, true, true, false);
            $this->crear($roles['Encargado de Inventario'], 'Compra', true, true, false, false);
            $this->crear($roles['Encargado de Inventario'], 'PagoProveedor', true, true, false, false);
            $this->crear($roles['Encargado de Inventario'], 'Solicitud', true, true, true, false);
            $this->crear($roles['Encargado de Inventario'], 'Reportes', true, false, false, false);
        }

        if ($roles->has('Cliente')) {
            $this->crear($roles['Cliente'], 'Venta', true, false, false, false);
            $this->crear($roles['Cliente'], 'Pago', true, true, false, false);
            $this->crear($roles['Cliente'], 'PlanPago', true, false, false, false);
            $this->crear($roles['Cliente'], 'MetodoPago', true, false, false, false);
        }
    }

    private function crear(Role $rol, string $funcionalidad, bool $leer, bool $agregar, bool $modificar, bool $borrar): void
    {
        Privilegio::create([
            'funcionalidad' => $funcionalidad,
            'id_rol' => $rol->id,
            'leer' => $leer,
            'agregar' => $agregar,
            'modificar' => $modificar,
            'borrar' => $borrar,
            'state' => 'a',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Role;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $empresaDefault = Empresa::first();

        if (! $empresaDefault) {
            return;
        }

        $usuarios = [
            [
                'rol' => 'Administrador',
                'nombre' => 'Administrador EMPACAR',
                'email' => 'admin@empacar.local',
                'telefono' => 70000001,
                'password' => 'secret',
                'estilo' => 6,
            ],
            [
                'rol' => 'Vendedor',
                'nombre' => 'Vendedor EMPACAR',
                'email' => 'vendedor@empacar.local',
                'telefono' => 70000002,
                'password' => 'secret',
                'estilo' => 2,
            ],
            [
                'rol' => 'Cajero',
                'nombre' => 'Cajero EMPACAR',
                'email' => 'cajero@empacar.local',
                'telefono' => 70000003,
                'password' => 'secret',
                'estilo' => 3,
            ],
            [
                'rol' => 'Encargado de Inventario',
                'nombre' => 'Inventario EMPACAR',
                'email' => 'inventario@empacar.local',
                'telefono' => 70000004,
                'password' => 'secret',
                'estilo' => 3,
            ],
            [
                'rol' => 'Cliente',
                'nombre' => 'Cliente EMPACAR',
                'email' => 'cliente@empacar.local',
                'telefono' => 70000005,
                'password' => 'secret',
                'estilo' => 4,
            ],
        ];

        foreach ($usuarios as $data) {
            $rol = Role::where('nombre', $data['rol'])->first();

            if (! $rol) {
                continue;
            }

            $usuario = Usuario::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nombre' => $data['nombre'],
                    'password' => bcrypt($data['password']),
                    'telefono' => $data['telefono'],
                    'estilo' => $data['estilo'],
                    'id_empresa' => $empresaDefault->id,
                    'id_rol' => $rol->id,
                    'state' => 'a',
                ]
            );

            if ($data['rol'] === 'Cliente') {
                Cliente::firstOrCreate(
                    ['id_user' => $usuario->id],
                    [
                        'nombre' => 'Cliente EMPACAR',
                        'telefono' => 70000005,
                        'direccion' => 'Sin dirección registrada',
                        'state' => 'a',
                    ]
                );
            }
        }
    }
}

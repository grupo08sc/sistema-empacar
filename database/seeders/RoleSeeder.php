<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Administrador',
            'Vendedor',
            'Cajero',
            'Encargado de Inventario',
            'Cliente',
        ];

        foreach ($roles as $nombre) {
            Role::firstOrCreate(
                ['nombre' => $nombre],
                ['state' => 'a']
            );
        }
    }
}

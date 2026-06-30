<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('empresas')->insert([
            [
                'nombre' => 'EMPACAR S.A.',
                'direccion' => 'Parque Industrial PI - 45B, Santa Cruz de la Sierra',
                'telefono' => 62037777,
                'correo' => 'contacto@empacar.local',
                'logo_path' => null,
                'state' => 'a',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

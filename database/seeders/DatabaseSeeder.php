<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EmpresaSeeder::class,
            ContadorSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            MetodoPagoSeeder::class,
            PrivilegioSeeder::class,

            // Datos demo EMPACAR para poblar el sistema de forma directa.
            EmpacarCatalogoDemoSeeder::class,
            EmpacarOperacionesDemoSeeder::class,
        ]);
    }
}

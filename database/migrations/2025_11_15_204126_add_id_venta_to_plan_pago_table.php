<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // La columna id_venta ya forma parte de la migración base de plan_pago.
    }

    public function down(): void
    {
        // No se realiza rollback para evitar eliminar una columna requerida por el modelo comercial.
    }
};

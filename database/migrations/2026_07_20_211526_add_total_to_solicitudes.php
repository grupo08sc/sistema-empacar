<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            if (! Schema::hasColumn('solicitudes', 'total')) {
                $table->decimal('total', 12, 2)->default(0);
            }
            $table->foreignId('id_proveedor')->nullable()
                ->default(null)->constrained('proveedores');
            $table->enum('metodo_pago_propuesto', [
                'cheque',
                'efectivo',
                'pagofacil',
                'qr',
                'tarjeta',
                'transferencia',
            ])->default('efectivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn('total');
            $table->dropForeign('solicitudes_id_proveedor_foreign');
            $table->dropColumn('id_proveedor');
            $table->dropColumn('metodo_pago_propuesto');
        });
    }
};

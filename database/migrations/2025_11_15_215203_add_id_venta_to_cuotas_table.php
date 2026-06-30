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
        Schema::table('cuotas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_venta')->after('id')->nullable();

            // Clave foránea
            $table->foreign('id_venta')
                  ->references('id')->on('ventas')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuotas', function (Blueprint $table) {
            // Primero eliminar la foreign key
            $table->dropForeign(['id_venta']);
            // Luego eliminar la columna
            $table->dropColumn('id_venta');
        });
    }
};

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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_plan')->nullable();
            $table->unsignedBigInteger('id_venta')->nullable();
            $table->enum('estado_pago', ['pendiente', 'parcial', 'pagado', 'excedente'])->default('pendiente');
            $table->date('fecha_pago');
            $table->double('monto');
            $table->string('tipo_pago');
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_plan')->references('id')->on('plan_pago');
            $table->foreign('id_venta')->references('id')->on('ventas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};

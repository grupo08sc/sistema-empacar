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
        Schema::create('plan_pago', function (Blueprint $table) {
            $table->id();
            $table->integer('cantidad_cuotas');
            $table->double('monto_cuota');
            $table->double('total_deuda');
            $table->double('saldo_restante')->default(0);
            $table->date('fecha_inicio');
            $table->enum('estado', ['pendiente', 'en_curso', 'finalizado'])->default('pendiente');
            $table->string('state')->default('a');
            $table->unsignedBigInteger('id_venta')->nullable();
            $table->timestamps();

            $table->foreign('id_venta')->references('id')->on('ventas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_pago');
    }
};

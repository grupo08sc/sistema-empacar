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
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_plan_pago');
            $table->double('monto');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['pendiente', 'pagado', 'atrasado'])->default('pendiente');
            $table->double('monto_pagado')->default(0);
            $table->unsignedBigInteger('id_pago')->nullable();
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_plan_pago')->references('id')->on('plan_pago');
            $table->foreign('id_pago')->references('id')->on('pagos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuotas');
    }
};

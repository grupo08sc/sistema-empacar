<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->boolean('es_electronico')->default(false);
            $table->boolean('permite_pago_unico')->default(true);
            $table->boolean('permite_plan_pagos')->default(true);
            $table->string('descripcion')->nullable();
            $table->string('state')->default('a');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};

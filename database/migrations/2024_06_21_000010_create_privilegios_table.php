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
        Schema::create('privilegios', function (Blueprint $table) {
            $table->id();
            $table->string('funcionalidad');
            $table->boolean('agregar');
            $table->boolean('borrar');
            $table->boolean('modificar');
            $table->boolean('leer');
            $table->unsignedBigInteger('id_rol');
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_rol')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privilegios');
    }
};

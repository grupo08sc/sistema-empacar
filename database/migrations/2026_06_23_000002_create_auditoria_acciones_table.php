<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_acciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('modulo')->index();
            $table->string('accion')->index();
            $table->string('entidad_tipo')->nullable()->index();
            $table->unsignedBigInteger('entidad_id')->nullable()->index();
            $table->string('nivel')->default('info')->index();
            $table->text('descripcion')->nullable();
            $table->json('estado_anterior')->nullable();
            $table->json('estado_nuevo')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('fecha')->nullable()->index();
            $table->string('state')->default('a');
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_acciones');
    }
};

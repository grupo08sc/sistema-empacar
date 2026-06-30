<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contador extends Model
{
    protected $table = 'contadors';

    protected $fillable = [
        'nombre',
        'visitas',
        'tipo',
    ];

    /**
     * Compatibilidad con controladores antiguos.
     * El conteo real ahora se realiza automáticamente mediante middleware.
     */
    public function contarModel($id): int
    {
        return (int) (session('contador_pagina') ?? optional(self::find($id))->visitas ?? 0);
    }
}

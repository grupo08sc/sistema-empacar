<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = [
        'codigo',
        'nombre',
        'es_electronico',
        'permite_pago_unico',
        'permite_plan_pagos',
        'descripcion',
        'state',
    ];

    protected $casts = [
        'es_electronico' => 'boolean',
        'permite_pago_unico' => 'boolean',
        'permite_plan_pagos' => 'boolean',
    ];
}

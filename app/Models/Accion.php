<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accion extends Model
{
    protected $table = 'accion';

    protected $fillable = [
        'id_tipo_accion',
        'id_usuario',
        'fecha',
        'observaciones',
        'estado_actual',
        'estado_siguiente',
        'state',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function tipoAccion()
    {
        return $this->belongsTo(TipoAccion::class, 'id_tipo_accion');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}

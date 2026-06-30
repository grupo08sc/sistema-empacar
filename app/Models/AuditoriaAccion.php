<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaAccion extends Model
{
    protected $table = 'auditoria_acciones';

    protected $fillable = [
        'id_usuario',
        'modulo',
        'accion',
        'entidad_tipo',
        'entidad_id',
        'nivel',
        'descripcion',
        'estado_anterior',
        'estado_nuevo',
        'ip',
        'user_agent',
        'fecha',
        'state',
    ];

    protected $casts = [
        'estado_anterior' => 'array',
        'estado_nuevo' => 'array',
        'fecha' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'id_usuario',
        'id_departamento',
        'descripcion',
        'justificacion',
        'fecha_solicitud',
        'fecha_requerida',
        'estado',
        'moneda',
        'observaciones',
        'state',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_requerida' => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_departamento');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleSolicitud::class, 'id_solicitud');
    }
}

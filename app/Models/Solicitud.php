<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'descripcion',
        'estado',
        'fecha_requerida',
        'fecha_solicitud',
        'id_departamento',
        'id_proveedor',
        'id_usuario',
        'justificacion',
        'metodo_pago_propuesto',
        'moneda',
        'observaciones',
        'state',
        'total',
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

    public function compras()
    {
        return $this->hasMany(Compra::class, 'solicitud_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }
}

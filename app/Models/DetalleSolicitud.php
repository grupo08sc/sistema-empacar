<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleSolicitud extends Model
{
    protected $table = 'detalle_solicitud';

    protected $fillable = [
        'cantidad',
        'id_articulo',
        'id_producto',
        'id_solicitud',
        'importe',
        'nombre_articulo',
        'precio_estimado',
        'state',
    ];

    protected $casts = [
        'precio_estimado' => 'decimal:2',
        'importe' => 'decimal:2',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}

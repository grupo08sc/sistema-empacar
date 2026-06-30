<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleSolicitud extends Model
{
    protected $table = 'detalle_solicitud';

    protected $fillable = [
        'id_solicitud',
        'id_producto',
        'id_articulo',
        'nombre_articulo',
        'cantidad',
        'precio_estimado',
        'importe',
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

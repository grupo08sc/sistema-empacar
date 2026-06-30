<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;
    protected $table = 'inventario';

    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['cantidad', 'descripcion', 'fecha', 'id_producto', 'tipo', 'state'];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}

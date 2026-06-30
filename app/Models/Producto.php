<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_categoria',
        'codigo',
        'nombre',
        'descripcion',
        'precio',
        'precio_compra',
        'precio_venta',
        'stock_minimo',
        'state',
        'fecha_ingreso',
        'stock',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class, 'id_producto');
    }

    public function detallesCompra()
    {
        return $this->hasMany(DetalleCompra::class, 'id_producto');
    }

    public function precioBaseVenta(): float
    {
        return (float) ($this->precio_venta ?? $this->precio ?? 0);
    }

    public function precioConDescuento(): float
    {
        return round($this->precioBaseVenta(), 2);
    }
}

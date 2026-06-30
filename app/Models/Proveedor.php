<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'nit',
        'telefono',
        'email',
        'direccion',
        'contacto',
        'estado',
        'state',
    ];

    public function compras()
    {
        return $this->hasMany(Compra::class, 'id_proveedor');
    }

    public function pagos()
    {
        return $this->hasMany(PagoProveedor::class, 'id_proveedor');
    }
}

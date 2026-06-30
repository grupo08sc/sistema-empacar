<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'apellido',
        'documento',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'id_user',
        'state',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'id_cliente');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_cliente');
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombre . ' ' . ($this->apellido ?? ''));
    }
}

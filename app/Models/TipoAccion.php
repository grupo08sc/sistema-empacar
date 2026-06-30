<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoAccion extends Model
{
    protected $table = 'tipo_accion';

    protected $fillable = ['nombre', 'descripcion', 'state'];

    public function acciones()
    {
        return $this->hasMany(Accion::class, 'id_tipo_accion');
    }
}

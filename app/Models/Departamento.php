<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';

    protected $fillable = ['nombre', 'descripcion', 'state'];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_departamento');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_departamento');
    }
}

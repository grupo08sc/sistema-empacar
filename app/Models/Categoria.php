<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = ['nombre','state'];

    public function scopeActivas($query)
    {
        return $query->where('state', 'a');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nombre',
        'name',
        'email',
        'fecha',
        'password',
        'telefono',
        'estilo',
        'id_empresa',
        'id_rol',
        'id_departamento',
        'state',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Compatibilidad con Breeze/Laravel, que normalmente usa el atributo "name".
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function rol()
    {
        return $this->belongsTo(Role::class, 'id_rol');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_departamento');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id_user');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'id_usuario');
    }
}

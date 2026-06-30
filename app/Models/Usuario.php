<?php

namespace App\Models;

/**
 * Alias temporal para conservar compatibilidad con controladores antiguos.
 * El modelo autenticable real es User. Nuevos módulos deben usar App\Models\User.
 */
class Usuario extends User
{
    protected $table = 'users';
}

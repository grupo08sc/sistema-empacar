<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPrivilegio
{
    public function handle(Request $request, Closure $next, string $funcionalidad, string $accion = 'leer'): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->rol) {
            abort(403, 'No autorizado.');
        }

        if ($usuario->rol->nombre === 'Administrador') {
            return $next($request);
        }

        $privilegio = $usuario->rol->privilegios()
            ->where('state', 'a')
            ->where('funcionalidad', $funcionalidad)
            ->first();

        if (! $privilegio || ! (bool) ($privilegio->{$accion} ?? false)) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\AuditoriaAccion;
use App\Models\Contador;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RegistrarAccesoPagina
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->debeRegistrar($request)) {
            $modulo = $this->resolverModulo($request);

            if ($modulo) {
                $contador = Contador::firstOrCreate(
                    ['nombre' => $modulo],
                    ['visitas' => 0, 'tipo' => $this->resolverTipo($modulo)]
                );

                $contador->increment('visitas');
                $contador->refresh();

                $request->session()->put('contador_pagina', $contador->visitas);
                $request->session()->put('contador_modulo', $modulo);

                $this->registrarAuditoria($request, $modulo);
            }
        }

        return $next($request);
    }

    private function debeRegistrar(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $esInertia = $request->headers->has('X-Inertia');

        if (($request->expectsJson() || $request->ajax()) && ! $esInertia) {
            return false;
        }

        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return false;
        }

        return ! Str::startsWith($routeName, [
            'ignition.',
            'sanctum.',
            'verification.',
            'password.',
        ]);
    }

    private function resolverModulo(Request $request): ?string
    {
        $name = $request->route()?->getName();

        $mapaExacto = [
            'landing' => 'Landing',
            'dashboard' => 'Dashboard',
            'dashboardvue' => 'Dashboard',
            'login' => 'Login',
            'intruso' => 'Acceso no autorizado',
            'profile.edit' => 'Perfil',
        ];

        if (isset($mapaExacto[$name])) {
            return $mapaExacto[$name];
        }

        $prefijo = Str::before($name, '.');

        return match ($prefijo) {
            'empresa' => 'Empresa',
            'cliente' => 'Cliente',
            'pago', 'pagos' => 'Pago',
            'metodos-pago' => 'MetodoPago',
            'privilegio', 'privilegios' => 'Privilegio',
            'producto' => 'Producto',
            'categoria' => 'Categoria',
            'rol' => 'Rol',
            'usuario' => 'Usuario',
            'venta', 'ventas' => 'Venta',
            'planes' => 'PlanPago',
            'reportes', 'estadisticas' => 'Reportes',
            'auditoria' => 'Auditoria',
            'inventario' => 'Inventario',
            'proveedores' => 'Proveedor',
            'compras' => 'Compra',
            'solicitudes' => 'Solicitud',
            'pagos-proveedor' => 'PagoProveedor',
            'cargarEstilo' => 'Preferencias visuales',
            default => Str::title(str_replace(['-', '_'], ' ', $prefijo)),
        };
    }

    private function resolverTipo(string $modulo): int
    {
        $tipos = [
            'Landing' => 1,
            'Login' => 2,
            'Dashboard' => 3,
            'Empresa' => 4,
            'Cliente' => 5,
            'Pago' => 6,
            'Privilegio' => 7,
            'Producto' => 8,
            'Categoria' => 9,
            'Rol' => 10,
            'Usuario' => 11,
            'Venta' => 12,
            'PlanPago' => 13,
            'Reportes' => 14,
            'Auditoria' => 15,
            'Inventario' => 16,
            'Proveedor' => 17,
            'Compra' => 18,
            'Solicitud' => 19,
            'PagoProveedor' => 20,
            'MetodoPago' => 21,
            'Perfil' => 22,
            'Preferencias visuales' => 23,
            'Acceso no autorizado' => 24,
        ];

        return $tipos[$modulo] ?? 99;
    }

    private function registrarAuditoria(Request $request, string $modulo): void
    {
        try {
            AuditoriaAccion::create([
                'id_usuario' => $request->user()?->id,
                'modulo' => 'Acceso',
                'accion' => 'recurso_visitado',
                'entidad_tipo' => $modulo,
                'entidad_id' => null,
                'nivel' => 'info',
                'descripcion' => 'Visita al recurso: ' . $modulo . ' | Ruta: ' . $request->route()?->getName(),
                'estado_anterior' => null,
                'estado_nuevo' => [
                    'ruta' => $request->path(),
                    'nombre_ruta' => $request->route()?->getName(),
                    'metodo' => $request->method(),
                ],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'fecha' => now(),
                'state' => 'a',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

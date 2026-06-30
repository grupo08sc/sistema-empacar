<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $request->authenticate();

        if ($request->user()->state !== 'a') {
            $auditoria->registrar(
                'Acceso',
                'login_rechazado',
                null,
                'Intento de ingreso con cuenta inactiva: ' . $request->user()->email,
                null,
                ['email' => $request->user()->email, 'estado' => $request->user()->state],
                'warning',
                $request
            );

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta se encuentra inactiva. Contacta al administrador.',
            ]);
        }

        $request->session()->regenerate();

        $auditoria->registrar(
            'Acceso',
            'login_aceptado',
            null,
            'Inicio de sesión aceptado para: ' . $request->user()->email,
            null,
            ['email' => $request->user()->email, 'rol' => $request->user()->rol?->nombre],
            'info',
            $request
        );

        return redirect()->intended($this->rutaInicioParaUsuario($request->user()));
    }

    private function rutaInicioParaUsuario($usuario): string
    {
        $rol = $usuario?->rol?->nombre;

        if ($rol === 'Administrador') {
            return route('dashboard', absolute: false);
        }

        $rutasPorModulo = [
            'Reportes' => 'dashboard',
            'Venta' => 'venta.index',
            'PlanPago' => 'planes.index',
            'Pago' => 'pago.index',
            'Cliente' => 'cliente.index',
            'Producto' => 'producto.index',
            'Inventario' => 'inventario.index',
            'Compra' => 'compras.index',
            'Proveedor' => 'proveedores.index',
            'Solicitud' => 'solicitudes.index',
            'Empresa' => 'empresa.index',
        ];

        $privilegios = $usuario?->rol?->privilegios()
            ->where('state', 'a')
            ->where('leer', true)
            ->pluck('funcionalidad')
            ->all() ?? [];

        foreach ($rutasPorModulo as $modulo => $ruta) {
            if (in_array($modulo, $privilegios, true)) {
                return route($ruta, absolute: false);
            }
        }

        return route('profile.edit', absolute: false);
    }

    public function destroy(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $usuario = $request->user();

        $auditoria->registrar(
            'Acceso',
            'logout',
            null,
            'Cierre de sesión: ' . ($usuario?->email ?? 'usuario no identificado'),
            null,
            ['email' => $usuario?->email],
            'info',
            $request
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}

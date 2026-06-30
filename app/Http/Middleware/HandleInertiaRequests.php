<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['rol.privilegios', 'cliente']);
        }

        $privilegios = [];
        if ($user && $user->rol) {
            $privilegios = $user->rol->privilegios
                ->where('state', 'a')
                ->mapWithKeys(function ($privilegio) {
                    return [$privilegio->funcionalidad => [
                        'leer' => (bool) $privilegio->leer,
                        'agregar' => (bool) $privilegio->agregar,
                        'modificar' => (bool) $privilegio->modificar,
                        'borrar' => (bool) $privilegio->borrar,
                    ]];
                })
                ->toArray();
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'privilegios' => $privilegios,
            ],
            'menu' => $this->menuDinamico($privilegios, $user),
            'homeRoute' => $this->rutaInicio($privilegios, $user),
            'empresa' => fn () => Empresa::query()->select('id', 'nombre', 'logo_path')->first(),
            'num' => $request->session()->get('contador_pagina', 0),
            'contadorModulo' => $request->session()->get('contador_modulo', ''),
            'visitas' => $request->session()->get('contador_pagina', 0),
            'assetUrl' => asset('/'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }

    private function rutaInicio(array $privilegios, $user): string
    {
        if (! $user) {
            return 'landing';
        }

        if ($user->rol?->nombre === 'Administrador') {
            return 'dashboard';
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

        foreach ($rutasPorModulo as $modulo => $ruta) {
            if ($this->puede($privilegios, $modulo)) {
                return $ruta;
            }
        }

        return 'profile.edit';
    }

    private function puede(array $privilegios, string $modulo): bool
    {
        return (bool) ($privilegios[$modulo]['leer'] ?? false);
    }

    private function menuDinamico(array $privilegios, $user): array
    {
        if (! $user) {
            return [];
        }

        $grupos = [];

        $this->agregarGrupo($grupos, 'Administración', 'fas fa-copy', [
            $this->item($privilegios, 'Usuario', 'Usuario', 'usuario.index', 'far fa-circle'),
            $this->item($privilegios, 'Rol', 'Rol', 'rol.index', 'far fa-circle'),
            $this->item($privilegios, 'Privilegio', 'Matriz de acceso', 'privilegio.index', 'far fa-circle'),
            $this->item($privilegios, 'Empresa', 'Configuración', 'empresa.index', 'far fa-circle'),
        ]);

        $this->agregarGrupo($grupos, 'Gestión Comercial', 'fas fa-chart-pie', [
            $this->item($privilegios, 'Cliente', 'Clientes', 'cliente.index', 'far fa-circle'),
            $this->item($privilegios, 'Pago', 'Pagos', 'pago.index', 'far fa-circle'),
            $this->item($privilegios, 'MetodoPago', 'Métodos de pago', 'metodos-pago.index', 'far fa-circle'),
            $this->item($privilegios, 'PlanPago', 'Planes de pago', 'planes.index', 'far fa-circle'),
            $this->item($privilegios, 'Venta', 'Ventas', 'venta.index', 'far fa-circle'),
        ]);

        $this->agregarGrupo($grupos, 'Compras y Proveedores', 'fas fa-dolly', [
            $this->item($privilegios, 'Proveedor', 'Proveedores', 'proveedores.index', 'far fa-circle'),
            $this->item($privilegios, 'Compra', 'Solicitudes y compras', 'compras.index', 'far fa-circle'),
            $this->item($privilegios, 'PagoProveedor', 'Pagos a proveedores', 'pagos-proveedor.index', 'far fa-circle'),
            $this->item($privilegios, 'Solicitud', 'Solicitudes internas', 'solicitudes.index', 'far fa-circle'),
        ]);

        $this->agregarGrupo($grupos, 'Inventario y Recursos', 'fas fa-boxes', [
            $this->item($privilegios, 'Inventario', 'Inventario', 'inventario.index', 'far fa-circle'),
            $this->item($privilegios, 'Producto', 'Productos', 'producto.index', 'far fa-circle'),
            $this->item($privilegios, 'Categoria', 'Categorías', 'categoria.index', 'far fa-circle'),
        ]);

        $reportes = [];
        if ($this->puede($privilegios, 'Reportes')) {
            $reportes[] = ['label' => 'Estadísticas de acceso', 'route' => 'estadisticas.index', 'icon' => 'far fa-circle'];
            $reportes[] = ['label' => 'Reporte de ventas', 'route' => 'dashboard', 'icon' => 'far fa-circle'];
            $reportes[] = ['label' => 'Control financiero', 'route' => 'reportes.financiero', 'icon' => 'far fa-circle'];
        }
        if ($this->puede($privilegios, 'Auditoria')) {
            $reportes[] = ['label' => 'Bitácora y auditoría', 'route' => 'auditoria.index', 'icon' => 'far fa-circle'];
        }
        $this->agregarGrupo($grupos, 'Reportes y Estadísticas', 'fas fa-chart-line', $reportes);

        return $grupos;
    }

    private function item(array $privilegios, string $modulo, string $label, string $route, string $icon): ?array
    {
        if (! $this->puede($privilegios, $modulo)) {
            return null;
        }

        return [
            'label' => $label,
            'route' => $route,
            'icon' => $icon,
            'modulo' => $modulo,
        ];
    }

    private function agregarGrupo(array &$grupos, string $label, string $icon, array $items): void
    {
        $items = array_values(array_filter($items));

        if (count($items) === 0) {
            return;
        }

        $grupos[] = [
            'label' => $label,
            'icon' => $icon,
            'items' => $items,
        ];
    }
}

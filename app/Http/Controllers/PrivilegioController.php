<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Privilegio;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PrivilegioController extends Controller
{
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(6);

        $privilegios = Privilegio::with('rol')->where('state', 'a')->get();
        $funcionalidades = Privilegio::where('state', 'a')->select('funcionalidad')->distinct()->orderBy('funcionalidad')->pluck('funcionalidad');

        $roles = Role::with(['privilegios' => fn ($query) => $query->where('state', 'a')->orderBy('funcionalidad')])
            ->where('state', 'a')
            ->orderBy('nombre')
            ->get();

        foreach ($roles as $rol) {
            $rol->privilegiosAgrupados = $rol->privilegios
                ->groupBy('funcionalidad')
                ->map(function ($items) {
                    $primer = $items->first();

                    return [
                        'id' => $primer->id,
                        'funcionalidad' => $primer->funcionalidad,
                        'agregar' => $items->pluck('agregar')->contains(true),
                        'borrar' => $items->pluck('borrar')->contains(true),
                        'modificar' => $items->pluck('modificar')->contains(true),
                        'leer' => $items->pluck('leer')->contains(true),
                    ];
                })
                ->values();
        }

        return Inertia::render('Privilegio/Index', [
            'privilegios' => $privilegios,
            'roles' => $roles,
            'num' => $num,
            'funcionalidades' => $funcionalidades,
        ]);
    }

    public function asignar(Request $request, Role $rol)
    {
        $validated = $request->validate([
            'privilegios' => 'required|array',
            'privilegios.*.agregar' => 'sometimes|boolean',
            'privilegios.*.borrar' => 'sometimes|boolean',
            'privilegios.*.modificar' => 'sometimes|boolean',
            'privilegios.*.leer' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($rol, $validated) {
            Privilegio::where('id_rol', $rol->id)->update(['state' => 'i']);

            foreach ($validated['privilegios'] as $funcionalidad => $permisos) {
                Privilegio::updateOrCreate(
                    [
                        'id_rol' => $rol->id,
                        'funcionalidad' => $funcionalidad,
                    ],
                    [
                        'agregar' => (bool) ($permisos['agregar'] ?? false),
                        'borrar' => (bool) ($permisos['borrar'] ?? false),
                        'modificar' => (bool) ($permisos['modificar'] ?? false),
                        'leer' => (bool) ($permisos['leer'] ?? false),
                        'state' => 'a',
                    ]
                );
            }
        });

        return to_route('privilegio.index')->with('success', 'Privilegios actualizados correctamente.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rol_id' => [
                'required',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('state', 'a')),
            ],
            'funcion' => 'required|string|max:100',
            'agregar' => 'sometimes|boolean',
            'borrar' => 'sometimes|boolean',
            'modificar' => 'sometimes|boolean',
            'leer' => 'sometimes|boolean',
        ]);

        $exists = Privilegio::where('id_rol', $validated['rol_id'])
            ->where('funcionalidad', $validated['funcion'])
            ->where('state', 'a')
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'funcion' => 'Este privilegio ya está asignado a este rol.',
            ]);
        }

        Privilegio::create([
            'id_rol' => $validated['rol_id'],
            'funcionalidad' => $validated['funcion'],
            'agregar' => (bool) ($validated['agregar'] ?? false),
            'borrar' => (bool) ($validated['borrar'] ?? false),
            'modificar' => (bool) ($validated['modificar'] ?? false),
            'leer' => (bool) ($validated['leer'] ?? false),
            'state' => 'a',
        ]);

        return back()->with('success', 'Privilegio creado correctamente.');
    }

    public function updateOne(Request $request, Privilegio $privilegio)
    {
        $validated = $request->validate([
            'agregar' => 'sometimes|boolean',
            'borrar' => 'sometimes|boolean',
            'modificar' => 'sometimes|boolean',
            'leer' => 'sometimes|boolean',
        ]);

        $privilegio->update([
            'agregar' => (bool) ($validated['agregar'] ?? $privilegio->agregar),
            'borrar' => (bool) ($validated['borrar'] ?? $privilegio->borrar),
            'modificar' => (bool) ($validated['modificar'] ?? $privilegio->modificar),
            'leer' => (bool) ($validated['leer'] ?? $privilegio->leer),
        ]);

        return back()->with('success', 'Privilegio actualizado correctamente.');
    }

    public function updateByRol(Request $request, Role $rol)
    {
        $validated = $request->validate([
            'privilegios' => 'required|array',
            'privilegios.*.id' => 'required|exists:privilegios,id',
            'privilegios.*.funcionalidad' => 'required|string|max:100',
            'privilegios.*.agregar' => 'sometimes|boolean',
            'privilegios.*.borrar' => 'sometimes|boolean',
            'privilegios.*.modificar' => 'sometimes|boolean',
            'privilegios.*.leer' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($rol, $validated) {
            foreach ($validated['privilegios'] as $data) {
                $privilegio = Privilegio::where('id_rol', $rol->id)
                    ->where('id', $data['id'])
                    ->where('state', 'a')
                    ->firstOrFail();

                $privilegio->update([
                    'agregar' => (bool) ($data['agregar'] ?? false),
                    'borrar' => (bool) ($data['borrar'] ?? false),
                    'modificar' => (bool) ($data['modificar'] ?? false),
                    'leer' => (bool) ($data['leer'] ?? false),
                ]);
            }
        });

        return back()->with('success', 'Privilegios actualizados correctamente.');
    }

    public function destroy(Privilegio $privilegio)
    {
        $privilegio->update(['state' => 'i']);

        return to_route('privilegio.index')->with('success', 'Privilegio desactivado exitosamente.');
    }

    public function destroyByRol(Role $rol)
    {
        Privilegio::where('id_rol', $rol->id)->update(['state' => 'i']);

        return to_route('privilegio.index')->with('success', 'Privilegios del rol desactivados exitosamente.');
    }
}

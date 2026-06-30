<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(9);

        $roles = Role::where('state', 'a')->orderBy('id', 'asc')->get();

        return Inertia::render('Rol/Index', [
            'roles' => $roles,
            'num' => $num,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:roles,nombre',
        ]);

        Role::create($validated + ['state' => 'a']);

        return to_route('rol.index')->with('success', 'Rol agregado exitosamente.');
    }

    public function update(Request $request, Role $rol)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:roles,nombre,' . $rol->id,
        ]);

        $rol->update($validated);

        return to_route('rol.index')->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy(Role $rol)
    {
        if ($rol->usuarios()->where('state', 'a')->exists()) {
            return back()->withErrors([
                'rol' => 'No se puede eliminar el rol porque tiene usuarios activos asignados.',
            ]);
        }

        $rol->update(['state' => 'i']);

        return to_route('rol.index')->with('success', 'Rol eliminado exitosamente.');
    }
}

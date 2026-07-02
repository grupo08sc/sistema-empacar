<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Role;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UsuarioController extends Controller
{
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(11);

        $roles = Role::where('state', 'a')->orderBy('nombre')->get();

        $usuarios = Usuario::with(['rol'])
            ->where('state', 'a')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Usuario/Index', [
            'usuarios' => $usuarios,
            'num' => $num,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|digits_between:6,15',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255',
            'id_rol' => [
                'required',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('state', 'a')),
            ],
        ]);

        Usuario::create([
            'nombre' => $validated['nombre'],
            'telefono' => $validated['telefono'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'id_rol' => $validated['id_rol'],
            'id_empresa' => 1,
            'state' => 'a',
        ]);

        return to_route('usuario.index')->with('success', 'Usuario agregado exitosamente.');
    }

    public function update(Request $request, Usuario $usuario)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|digits_between:6,15',
            'email' => 'required|email|max:255|unique:users,email,' . $usuario->id,
            'id_rol' => [
                'required',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('state', 'a')),
            ],
            'password' => 'nullable|string|min:8|max:255',
        ]);

        $data = [
            'nombre' => $validated['nombre'],
            'telefono' => $validated['telefono'],
            'email' => $validated['email'],
            'id_rol' => $validated['id_rol'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $usuario->update($data);

        return to_route('usuario.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(Usuario $usuario)
    {
        if (auth()->id() === $usuario->id) {
            return back()->withErrors([
                'usuario' => 'No puedes eliminar tu propio usuario activo.',
            ]);
        }

        $usuario->update(['state' => 'i']);

        return to_route('usuario.index')->with('success', 'Usuario eliminado exitosamente.');
    }
}

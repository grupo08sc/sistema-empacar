<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Departamento;
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
        $departamentos = Departamento::where('state', 'a')->orderBy('nombre')->get();

        $usuarios = Usuario::with(['rol', 'departamento'])
            ->where('state', 'a')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Usuario/Index', [
            'usuarios' => $usuarios,
            'num' => $num,
            'roles' => $roles,
            'departamentos' => $departamentos,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'telefono' => 'required|digits_between:6,15',
                'email' => 'required|email|max:255|unique:users,email',
                'id_departamento' => 'nullable|exists:departamentos,id',
                'password' => 'required|string|min:8|max:255',
                'id_rol' => [
                    'required',
                    Rule::exists('roles', 'id')->where(fn($query) => $query->where('state', 'a')),
                ],
            ]);

            $usuario = Usuario::create([
                'nombre' => $validated['nombre'],
                'telefono' => $validated['telefono'],
                'email' => $validated['email'],
                'id_departamento' => $validated['id_departamento'],
                'password' => Hash::make($validated['password']),
                'id_rol' => $validated['id_rol'],
                'id_empresa' => 1,
                'state' => 'a',
            ]);
            $usuario->fresh(['rol', 'departamento']);
            if ($usuario && $usuario->rol) {
                $rol = $usuario->rol;
                if ($rol->nombre === 'Cliente') {
                    $cliente = Cliente::create($validated + [
                        'id_user' => $usuario->id,
                        'state' => 'a',
                        'nombre' => $usuario->nombre,
                        'telefono' => $usuario->telefono,
                        'email' => $usuario->email,
                        'direccion' =>  "",
                        'documento' => null,
                        'ciudad' => null,
                    ]);
                }
            } else {
                return response()->json(['error' => 'Error al crear el usuario.'], 500);
            }

            return to_route('usuario.index')->with('success', 'Usuario agregado exitosamente.');
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function update(Request $request, Usuario $usuario)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|digits_between:6,15',
            'email' => 'required|email|max:255|unique:users,email,' . $usuario->id,
            'id_rol' => [
                'required',
                Rule::exists('roles', 'id')->where(fn($query) => $query->where('state', 'a')),
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

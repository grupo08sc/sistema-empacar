<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contador;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteController extends Controller
{
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(2);

        $clientes = Cliente::with(['user'])->where('state', 'a')->get();

        return Inertia::render('Cliente/Index', [
            'clientes' => $clientes,
            'num' => $num,
        ]);
    }

    public function create()
    {
        // La creación se realiza desde modal Vue/Inertia.
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|digits:8',
            'email' => 'nullable|email|max:255',
            'documento' => 'nullable|string|max:50',
            'ciudad' => 'nullable|string|max:100',
        ]);

        Cliente::create($validated + [
            'id_user' => null,
            'state' => 'a',
        ]);

        return to_route('cliente.index')->with('success', 'Cliente creado exitosamente');
    }

    public function show(Cliente $cliente)
    {
        // Pendiente para vista de detalle.
    }

    public function edit(Cliente $cliente)
    {
        // La edición se realiza desde modal Vue/Inertia.
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|digits:8',
            'email' => 'nullable|email|max:255',
            'documento' => 'nullable|string|max:50',
            'ciudad' => 'nullable|string|max:100',
        ]);

        $cliente->update($validated);

        if ($cliente->user) {
            $cliente->user->update([
                'nombre' => $validated['nombre'],
                'telefono' => $validated['telefono'],
                'email' => $validated['email'] ?? $cliente->user->email,
            ]);
        }

        return to_route('cliente.index')->with('success', 'Cliente actualizado exitosamente');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->update(['state' => 'i']);

        if ($cliente->user) {
            $cliente->user->update(['state' => 'i']);
        }

        return to_route('cliente.index')->with('success', 'Cliente desactivado exitosamente');
    }
}

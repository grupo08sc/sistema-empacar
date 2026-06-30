<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $proveedores = Proveedor::withCount(['compras', 'pagos'])
            ->where('state', 'a')
            ->orderBy('nombre')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['proveedores' => $proveedores]);
        }

        return Inertia::render('Proveedor/Index', [
            'proveedores' => $proveedores,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'nit' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'estado' => 'nullable|in:activo,inactivo,bloqueado',
        ]);

        $proveedor = Proveedor::create($validated + [
            'estado' => $validated['estado'] ?? 'activo',
            'state' => 'a',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Proveedor creado correctamente.',
                'proveedor' => $proveedor,
            ], 201);
        }

        return to_route('proveedores.index')->with('success', 'Proveedor registrado correctamente.');
    }

    public function show(Request $request, Proveedor $proveedor)
    {
        $proveedor->load(['compras.detalles.producto', 'pagos.usuario']);

        if ($request->wantsJson()) {
            return response()->json(['proveedor' => $proveedor]);
        }

        return Inertia::render('Proveedor/Show', [
            'proveedor' => $proveedor,
        ]);
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'nit' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'estado' => 'nullable|in:activo,inactivo,bloqueado',
        ]);

        $proveedor->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Proveedor actualizado correctamente.',
                'proveedor' => $proveedor->fresh(),
            ]);
        }

        return to_route('proveedores.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Request $request, Proveedor $proveedor)
    {
        $proveedor->update(['state' => 'i', 'estado' => 'inactivo']);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Proveedor desactivado correctamente.']);
        }

        return to_route('proveedores.index')->with('success', 'Proveedor desactivado correctamente.');
    }
}

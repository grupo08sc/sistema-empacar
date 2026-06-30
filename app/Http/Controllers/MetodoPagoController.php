<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MetodoPagoController extends Controller
{
    public function index()
    {
        return Inertia::render('MetodoPago/Index', [
            'metodos' => MetodoPago::where('state', 'a')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|alpha_dash|unique:metodos_pago,codigo',
            'nombre' => 'required|string|max:120',
            'es_electronico' => 'boolean',
            'permite_pago_unico' => 'boolean',
            'permite_plan_pagos' => 'boolean',
            'descripcion' => 'nullable|string|max:500',
        ], [
            'codigo.required' => 'Debe ingresar el código del método de pago.',
            'codigo.alpha_dash' => 'El código solo puede contener letras, números, guiones y guion bajo.',
            'codigo.unique' => 'Ya existe un método de pago con ese código.',
            'nombre.required' => 'Debe ingresar el nombre del método de pago.',
        ]);

        MetodoPago::create([
            'codigo' => mb_strtolower($validated['codigo']),
            'nombre' => $validated['nombre'],
            'es_electronico' => (bool) ($validated['es_electronico'] ?? false),
            'permite_pago_unico' => (bool) ($validated['permite_pago_unico'] ?? true),
            'permite_plan_pagos' => (bool) ($validated['permite_plan_pagos'] ?? true),
            'descripcion' => $validated['descripcion'] ?? null,
            'state' => 'a',
        ]);

        return to_route('metodos-pago.index')->with('success', 'Método de pago registrado correctamente.');
    }

    public function update(Request $request, MetodoPago $metodo_pago)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|alpha_dash|unique:metodos_pago,codigo,' . $metodo_pago->id,
            'nombre' => 'required|string|max:120',
            'es_electronico' => 'boolean',
            'permite_pago_unico' => 'boolean',
            'permite_plan_pagos' => 'boolean',
            'descripcion' => 'nullable|string|max:500',
        ], [
            'codigo.required' => 'Debe ingresar el código del método de pago.',
            'codigo.alpha_dash' => 'El código solo puede contener letras, números, guiones y guion bajo.',
            'codigo.unique' => 'Ya existe un método de pago con ese código.',
            'nombre.required' => 'Debe ingresar el nombre del método de pago.',
        ]);

        $metodo_pago->update([
            'codigo' => mb_strtolower($validated['codigo']),
            'nombre' => $validated['nombre'],
            'es_electronico' => (bool) ($validated['es_electronico'] ?? false),
            'permite_pago_unico' => (bool) ($validated['permite_pago_unico'] ?? true),
            'permite_plan_pagos' => (bool) ($validated['permite_plan_pagos'] ?? true),
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return to_route('metodos-pago.index')->with('success', 'Método de pago actualizado correctamente.');
    }

    public function destroy(MetodoPago $metodo_pago)
    {
        $metodo_pago->update(['state' => 'i']);

        return to_route('metodos-pago.index')->with('success', 'Método de pago desactivado correctamente.');
    }
}

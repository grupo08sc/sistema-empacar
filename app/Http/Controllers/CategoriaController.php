<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Contador;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoriaController extends Controller
{
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(1);
        $categorias = Categoria::activas()->orderBy('id', 'asc')->get();

        return Inertia::render('Categoria/Index', [
            'categorias' => $categorias,
            'num' => $num,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
        ]);

        Categoria::create($validated + ['state' => 'a']);

        return to_route('categoria.index')->with('success', 'Categoría agregada exitosamente.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre,' . $categoria->id,
        ]);

        $categoria->update($validated);

        return to_route('categoria.index')->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->update(['state' => 'i']);

        return to_route('categoria.index')->with('success', 'Categoría eliminada exitosamente.');
    }
}

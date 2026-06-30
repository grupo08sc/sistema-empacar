<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Contador;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProductoController extends Controller
{
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(7);

        $productos = Producto::with('categoria')
            ->where('state', 'a')
            ->orderBy('id', 'asc')
            ->get();
        $categorias = Categoria::where('state', 'a')->get();

        return Inertia::render('Producto/Index', [
            'productos' => $productos,
            'num' => $num,
            'categorias' => $categorias,
        ]);
    }

    public function create()
    {
        // No se usa con Inertia.
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'codigo' => 'nullable|string|max:100|unique:productos,codigo',
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'fecha_ingreso' => 'nullable|date',
                'precio' => 'required|numeric|min:0',
                'precio_compra' => 'nullable|numeric|min:0',
                'precio_venta' => 'nullable|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'stock_minimo' => 'nullable|integer|min:0',
                'id_categoria' => 'nullable|exists:categorias,id',
            ]);

            Producto::create($validated + ['state' => 'a']);

            return to_route('producto.index')->with('success', 'Producto registrado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al registrar producto: ' . $e->getMessage());
            return to_route('producto.index')
                ->with('error', 'Ocurrió un error al registrar el producto. Intente nuevamente.')
                ->withInput();
        }
    }

    public function show(Producto $producto)
    {
        // No se usa con Inertia.
    }

    public function edit(Producto $producto)
    {
        // No se usa con Inertia.
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $validated = $request->validate([
            'codigo' => 'nullable|string|max:100|unique:productos,codigo,' . $producto->id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_ingreso' => 'nullable|date',
            'precio' => 'required|numeric|min:0',
            'precio_compra' => 'nullable|numeric|min:0',
            'precio_venta' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'id_categoria' => 'nullable|exists:categorias,id',
        ]);

        $producto->update($validated);

        return to_route('producto.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto)
    {
        $producto->state = 'i';
        $producto->save();

        return to_route('producto.index')->with('success', 'Producto desactivado exitosamente.');
    }
}

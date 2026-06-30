<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InventarioController extends Controller
{
    public function index()
    {
        $contar = new Contador();
        $num = $contar->contarModel(4);

        $productos = Producto::where('state', 'a')->orderBy('id', 'asc')->get();
        $inventarios = Inventario::with('producto')->where('state', 'a')->latest('id')->get();

        return Inertia::render('Inventario/Index', [
            'inventarios' => $inventarios,
            'num' => $num,
            'productos' => $productos,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        try {
            DB::transaction(function () use ($validated) {
                $producto = Producto::lockForUpdate()->findOrFail($validated['id_producto']);
                $this->aplicarMovimientoStock($producto, $validated['tipo'], (int) $validated['cantidad']);

                Inventario::create($validated + ['state' => 'a']);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return to_route('inventario.index')->with('error', 'Error al registrar inventario: ' . $e->getMessage());
        }

        return to_route('inventario.index')->with('success', 'Movimiento de inventario registrado correctamente.');
    }

    public function update(Request $request, Inventario $inventario)
    {
        $validated = $this->validatedData($request);

        try {
            DB::transaction(function () use ($inventario, $validated) {
                $inventario = Inventario::lockForUpdate()->findOrFail($inventario->id);

                if ($inventario->state !== 'a') {
                    throw ValidationException::withMessages([
                        'inventario' => 'No se puede modificar un movimiento inactivo.',
                    ]);
                }

                $productoAnterior = Producto::lockForUpdate()->findOrFail($inventario->id_producto);
                $this->revertirMovimientoStock($productoAnterior, $inventario->tipo, (int) $inventario->cantidad);

                $productoNuevo = Producto::lockForUpdate()->findOrFail($validated['id_producto']);
                $this->aplicarMovimientoStock($productoNuevo, $validated['tipo'], (int) $validated['cantidad']);

                $inventario->update($validated);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return to_route('inventario.index')->with('error', 'Error al actualizar inventario: ' . $e->getMessage());
        }

        return to_route('inventario.index')->with('success', 'Inventario actualizado exitosamente.');
    }

    public function destroy(Inventario $inventario)
    {
        try {
            DB::transaction(function () use ($inventario) {
                $inventario = Inventario::lockForUpdate()->findOrFail($inventario->id);

                if ($inventario->state !== 'a') {
                    return;
                }

                $producto = Producto::lockForUpdate()->findOrFail($inventario->id_producto);
                $this->revertirMovimientoStock($producto, $inventario->tipo, (int) $inventario->cantidad);

                $inventario->update(['state' => 'i']);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return to_route('inventario.index')->with('error', 'Error al eliminar inventario: ' . $e->getMessage());
        }

        return to_route('inventario.index')->with('success', 'Inventario eliminado exitosamente.');
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'cantidad' => 'required|integer|min:1',
            'descripcion' => 'required|string|max:255',
            'fecha' => 'required|date',
            'id_producto' => 'required|exists:productos,id',
            'tipo' => 'required|in:entrada,salida,producto terminado',
        ]);
    }

    protected function aplicarMovimientoStock(Producto $producto, string $tipo, int $cantidad): void
    {
        if ($this->esEntrada($tipo)) {
            $producto->increment('stock', $cantidad);
            return;
        }

        if ((int) $producto->stock < $cantidad) {
            throw ValidationException::withMessages([
                'cantidad' => 'No existe stock suficiente para registrar la salida.',
            ]);
        }

        $producto->decrement('stock', $cantidad);
    }

    protected function revertirMovimientoStock(Producto $producto, string $tipo, int $cantidad): void
    {
        if ($this->esEntrada($tipo)) {
            if ((int) $producto->stock < $cantidad) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No se puede revertir el movimiento porque el stock ya fue utilizado.',
                ]);
            }

            $producto->decrement('stock', $cantidad);
            return;
        }

        $producto->increment('stock', $cantidad);
    }

    protected function esEntrada(string $tipo): bool
    {
        return in_array($tipo, ['entrada', 'producto terminado'], true);
    }
}

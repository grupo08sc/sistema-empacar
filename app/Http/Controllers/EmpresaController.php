<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    public function index()
    {
        $datos = Empresa::find(1);

        return Inertia::render('Configuracion/Index', [
            'datos' => $datos,
        ]);
    }

    public function intruso()
    {
        return view('intruso');
    }

    public function nombre(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ], [
            'nombre.required' => 'El nombre de la empresa es obligatorio.',
            'nombre.max' => 'El nombre de la empresa no debe superar los 255 caracteres.',
        ]);

        $empresa->update($validated);

        return to_route('empresa.index')->with('success', 'Nombre actualizado exitosamente.');
    }

    public function direccion(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'direccion' => 'required|string|max:255',
        ], [
            'direccion.required' => 'La dirección de la empresa es obligatoria.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
        ]);

        $empresa->update($validated);

        return to_route('empresa.index')->with('success', 'Dirección actualizada exitosamente.');
    }

    public function correo(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'correo' => 'required|email|max:255',
        ], [
            'correo.required' => 'El correo electrónico de la empresa es obligatorio.',
            'correo.email' => 'Debe ingresar un correo electrónico válido.',
            'correo.max' => 'El correo electrónico no debe superar los 255 caracteres.',
        ]);

        $empresa->update($validated);

        return to_route('empresa.index')->with('success', 'Correo electrónico actualizado exitosamente.');
    }

    public function telefono(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'telefono' => 'required|string|max:30',
        ], [
            'telefono.required' => 'El teléfono de la empresa es obligatorio.',
            'telefono.max' => 'El teléfono no debe superar los 30 caracteres.',
        ]);

        $empresa->update($validated);

        return to_route('empresa.index')->with('success', 'Teléfono actualizado exitosamente.');
    }

    public function logo(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'logo.required' => 'Debe seleccionar un logo para cargar.',
            'logo.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'logo.mimes' => 'El logo debe estar en formato JPG, JPEG, PNG o WEBP.',
            'logo.max' => 'El logo no debe superar los 2 MB.',
        ]);

        if ($empresa->logo_path && Storage::disk('public')->exists($empresa->logo_path)) {
            Storage::disk('public')->delete($empresa->logo_path);
        }

        $rutaLogo = $validated['logo']->store('logos', 'public');

        $empresa->update([
            'logo_path' => $rutaLogo,
        ]);

        return to_route('empresa.index')->with('success', 'Logo actualizado exitosamente.');
    }

    public function eliminarLogo(Empresa $empresa)
    {
        if ($empresa->logo_path && Storage::disk('public')->exists($empresa->logo_path)) {
            Storage::disk('public')->delete($empresa->logo_path);
        }

        $empresa->update([
            'logo_path' => null,
        ]);

        return to_route('empresa.index')->with('success', 'Logo eliminado exitosamente.');
    }
}

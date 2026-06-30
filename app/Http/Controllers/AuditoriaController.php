<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaAccion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'modulo' => 'nullable|string|max:80',
            'accion' => 'nullable|string|max:120',
            'nivel' => 'nullable|string|max:30',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $query = AuditoriaAccion::with('usuario')
            ->where('state', 'a')
            ->latest('fecha')
            ->latest('id');

        if (! empty($validated['modulo'])) {
            $query->where('modulo', $validated['modulo']);
        }

        if (! empty($validated['accion'])) {
            $query->where('accion', 'like', '%' . $validated['accion'] . '%');
        }

        if (! empty($validated['nivel'])) {
            $query->where('nivel', $validated['nivel']);
        }

        if (! empty($validated['fecha_inicio'])) {
            $query->whereDate('fecha', '>=', $validated['fecha_inicio']);
        }

        if (! empty($validated['fecha_fin'])) {
            $query->whereDate('fecha', '<=', $validated['fecha_fin']);
        }

        $auditorias = $query->limit(300)->get()->map(fn (AuditoriaAccion $log) => [
            'id' => $log->id,
            'fecha' => optional($log->fecha)->format('Y-m-d H:i:s'),
            'usuario' => $log->usuario?->nombre ?? $log->usuario?->email ?? 'Sistema',
            'modulo' => $log->modulo,
            'accion' => $log->accion,
            'nivel' => $log->nivel,
            'entidad_tipo' => $log->entidad_tipo,
            'entidad_id' => $log->entidad_id,
            'descripcion' => $log->descripcion,
            'ip' => $log->ip,
            'user_agent' => $log->user_agent,
            'estado_anterior' => $log->estado_anterior,
            'estado_nuevo' => $log->estado_nuevo,
        ]);

        $modulos = AuditoriaAccion::where('state', 'a')->distinct()->orderBy('modulo')->pluck('modulo');
        $niveles = AuditoriaAccion::where('state', 'a')->distinct()->orderBy('nivel')->pluck('nivel');

        return Inertia::render('Auditoria/Index', [
            'auditorias' => $auditorias,
            'modulos' => $modulos,
            'niveles' => $niveles,
            'filtros' => [
                'modulo' => $validated['modulo'] ?? '',
                'accion' => $validated['accion'] ?? '',
                'nivel' => $validated['nivel'] ?? '',
                'fecha_inicio' => $validated['fecha_inicio'] ?? '',
                'fecha_fin' => $validated['fecha_fin'] ?? '',
            ],
        ]);
    }
}

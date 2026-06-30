<?php

namespace App\Services;

use App\Models\AuditoriaAccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditoriaService
{
    public function registrar(
        string $modulo,
        string $accion,
        ?Model $entidad = null,
        ?string $descripcion = null,
        ?array $estadoAnterior = null,
        ?array $estadoNuevo = null,
        string $nivel = 'info',
        ?Request $request = null
    ): void {
        try {
            $request ??= request();

            AuditoriaAccion::create([
                'id_usuario' => Auth::id(),
                'modulo' => $modulo,
                'accion' => $accion,
                'entidad_tipo' => $entidad ? class_basename($entidad) : null,
                'entidad_id' => $entidad?->getKey(),
                'nivel' => $nivel,
                'descripcion' => $descripcion,
                'estado_anterior' => $this->normalizarEstado($estadoAnterior),
                'estado_nuevo' => $this->normalizarEstado($estadoNuevo),
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'fecha' => now(),
                'state' => 'a',
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar auditoría.', [
                'modulo' => $modulo,
                'accion' => $accion,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function capturarEstado(?Model $modelo): ?array
    {
        if (! $modelo) {
            return null;
        }

        return $this->normalizarEstado($modelo->fresh()?->toArray() ?? $modelo->toArray());
    }

    private function normalizarEstado(?array $estado): ?array
    {
        if ($estado === null) {
            return null;
        }

        return json_decode(json_encode($estado, JSON_PARTIAL_OUTPUT_ON_ERROR), true);
    }
}

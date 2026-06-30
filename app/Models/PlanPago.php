<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPago extends Model
{
    protected $table = 'plan_pago';

    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'cantidad_cuotas',
        'estado',
        'fecha_inicio',
        'total_deuda',
        'monto_inicial',
        'saldo_financiado',
        'saldo_restante',
        'frecuencia',
        'observaciones',
        'state',
        'id_venta',
        'monto_cuota',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'total_deuda' => 'decimal:2',
        'monto_inicial' => 'decimal:2',
        'saldo_financiado' => 'decimal:2',
        'saldo_restante' => 'decimal:2',
        'monto_cuota' => 'decimal:2',
    ];

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_plan');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'id_plan_pago')->orderBy('fecha_vencimiento', 'asc');
    }
}

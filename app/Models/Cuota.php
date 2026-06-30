<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    use HasFactory;

    protected $table = 'cuotas';

    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_venta',
        'id_plan_pago',
        'monto',
        'monto_pagado',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'id_pago',
        'state',
        'pagofacil_transaction_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
    ];

    public function planPago()
    {
        return $this->belongsTo(PlanPago::class, 'id_plan_pago');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_cuota');
    }

    public function pagoPrincipal()
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }

    public function saldo(): float
    {
        $pagado = array_key_exists('monto_pagado', $this->attributes)
            ? (float) $this->attributes['monto_pagado']
            : (float) $this->pagos()->where('estado_pago', 'pagado')->sum('monto');

        return max(0, round((float) $this->monto - $pagado, 2));
    }
}

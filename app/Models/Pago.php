<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'estado_pago',
        'fecha_pago',
        'id_plan',
        'id_venta',
        'id_cliente',
        'id_cuota',
        'monto',
        'tipo_pago',
        'referencia',
        'transaction_id',
        'observaciones',
        'state',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function plan()
    {
        return $this->belongsTo(PlanPago::class, 'id_plan');
    }

    public function cuota()
    {
        return $this->belongsTo(Cuota::class, 'id_cuota');
    }
}

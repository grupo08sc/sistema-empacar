<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'estado',
        'id_cliente',
        'fecha_venta',
        'state',
        'subtotal',
        'descuento',
        'total',
        'monto_pagado',
        'saldo',
        'tipo_pago',
        'observaciones',
        'id_usuario',
        'pagofacil_transaction_id',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'id_venta');
    }

    public function detalleVentas()
    {
        return $this->hasMany(DetalleVenta::class, 'id_venta', 'id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_venta');
    }

    public function plan()
    {
        return $this->hasOne(PlanPago::class, 'id_venta');
    }

    public function planes()
    {
        return $this->hasMany(PlanPago::class, 'id_venta');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'id_venta');
    }

    public function transaccionesPagoFacil()
    {
        return $this->hasMany(PagoFacilTransaccion::class, 'id_venta');
    }

    public function saldoActual(): float
    {
        if (array_key_exists('saldo', $this->attributes) && $this->attributes['saldo'] !== null) {
            return (float) $this->attributes['saldo'];
        }

        return max(0, (float) $this->total - (float) $this->pagos()->where('estado_pago', 'pagado')->sum('monto'));
    }
}

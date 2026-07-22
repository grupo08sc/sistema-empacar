<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'aprobado_por',
        'descuento',
        'estado_aprobacion',
        'estado',
        'fecha_aprobacion',
        'fecha_compra',
        'fecha_solicitud',
        'id_proveedor',
        'id_usuario',
        'metodo_pago_propuesto',
        'monto_pagado',
        'motivo_rechazo',
        'observacion_aprobacion',
        'observaciones',
        'referencia_pago_propuesto',
        'saldo',
        'solicitado_por',
        'solicitud_id',
        'state',
        'stock_aplicado',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_solicitud' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo' => 'decimal:2',
        'stock_aplicado' => 'boolean',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'id_compra');
    }

    public function pagos()
    {
        return $this->hasMany(PagoProveedor::class, 'id_compra');
    }

    public function estaPendienteAprobacion(): bool
    {
        return $this->estado_aprobacion === 'pendiente';
    }

    public function estaAprobada(): bool
    {
        return $this->estado_aprobacion === 'aprobada';
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }
}

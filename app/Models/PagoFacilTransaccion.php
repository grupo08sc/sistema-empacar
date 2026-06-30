<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoFacilTransaccion extends Model
{
    protected $table = 'pagofacil_transacciones';

    protected $fillable = [
        'id_venta',
        'id_cuota',
        'id_pago',
        'transaction_id',
        'payment_number',
        'monto',
        'estado',
        'qr_url',
        'qr_base64',
        'request_json',
        'response_json',
        'webhook_json',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'request_json' => 'array',
        'response_json' => 'array',
        'webhook_json' => 'array',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function cuota()
    {
        return $this->belongsTo(Cuota::class, 'id_cuota');
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }
}

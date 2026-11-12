<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'reserva_id',
        'proveedor',
        'estado',
        'monto_centimos',
        'moneda',
        'proveedor_pago_id',
        'codigo_respuesta',
        'mensaje',
        'intentos',
        'procesado_en',
    ];

    protected $casts = [
        'monto_centimos' => 'integer',
        'intentos' => 'integer',
        'procesado_en' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleReserva extends Model
{
    protected $table = 'detalle_reserva';

    protected $fillable = [
        'reserva_id',
        'tarifa_id',
        'cantidad',
        'precio_unitario',
        'cargo_servicio_unitario'
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class);
    }
}

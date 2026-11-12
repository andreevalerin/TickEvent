<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'usuario_id',
        'evento_id',
        'estado',
        'expira_en'
    ];

    protected $casts = [
        'expira_en' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleReserva::class);
    }

    public function pago()
    {
        return $this->hasOne(Pago::class);
    }

    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }
}

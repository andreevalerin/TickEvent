<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    protected $table = 'entradas';

    protected $fillable = [
        'reserva_id',
        'tarifa_id',
        'dni',
        'codigo',
        'estado',
        'usada_en',
        'validada_por',
    ];

    protected $casts = [
        'usada_en' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class);
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'validada_por');
    }

    public function validaciones()
    {
        return $this->hasMany(ValidacionEntrada::class);
    }
}

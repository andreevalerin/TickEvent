<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zona extends Model
{
    protected $table = 'zonas';

    protected $fillable = [
        'evento_id',
        'nombre',
        'capacidad',
        'activo'
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class);
    }
}

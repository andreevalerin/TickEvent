<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evento extends Model
{
    protected $table = 'eventos';

    protected $fillable = [
        'organizador_id',
        'titulo',
        'slug',
        'categoria',
        'descripcion',
        'fecha_evento',
        'lugar',
        'direccion',
        'ciudad',
        'aforo_total',
        'imagen_principal',
        'imagen_plano',
        'estado'
    ];

    protected $casts = [
        'fecha_evento' => 'datetime'
    ];

    public function organizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizador_id');
    }

    public function zonas(): HasMany
    {
        return $this->hasMany(Zona::class);
    }

    public function personalAcceso()
    {
        return $this->belongsToMany(User::class, 'evento_personal_acceso', 'evento_id', 'usuario_id')
            ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function cargoServicio(): int
    {
        return $this->aforo_total >= 10000 ? 10 : 5;
    }
}

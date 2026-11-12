<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'zona_id',
        'nombre',
        'precio',
        'activo'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean'
    ];

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }
}

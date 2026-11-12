<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValidacionEntrada extends Model
{
    protected $table = 'validaciones_entrada';

    protected $fillable = [
        'entrada_id',
        'operador_id',
        'codigo_hash',
        'resultado',
        'motivo',
        'ip',
    ];

    public function entrada()
    {
        return $this->belongsTo(Entrada::class);
    }

    public function operador()
    {
        return $this->belongsTo(User::class, 'operador_id');
    }
}

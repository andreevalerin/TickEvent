<?php

namespace App\Services;

use App\Models\Entrada;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmisorEntradas
{
    public function emitir(Reserva $reserva): void
    {
        if ($reserva->estado !== 'confirmada') {
            throw new RuntimeException('La reserva debe estar confirmada antes de emitir entradas.');
        }

        $reserva->loadMissing('detalles');
        $dnis = DB::table('reserva_asistentes')
            ->where('reserva_id', $reserva->id)
            ->orderBy('id')
            ->pluck('dni')
            ->all();
        $cantidad = $reserva->detalles->sum('cantidad');

        if (count($dnis) !== $cantidad) {
            throw new RuntimeException('La cantidad de asistentes no coincide con la reserva.');
        }

        $indice = 0;

        foreach ($reserva->detalles as $detalle) {
            for ($numero = 0; $numero < $detalle->cantidad; $numero++) {
                Entrada::firstOrCreate(
                    [
                        'reserva_id' => $reserva->id,
                        'dni' => $dnis[$indice],
                    ],
                    [
                        'tarifa_id' => $detalle->tarifa_id,
                        'codigo' => $this->generarCodigo(),
                        'estado' => 'emitida',
                    ]
                );

                $indice++;
            }
        }
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'TV-'.Str::upper(Str::random(24));
        } while (Entrada::where('codigo', $codigo)->exists());

        return $codigo;
    }
}

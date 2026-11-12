<?php

namespace Database\Seeders;

use App\Models\Evento;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TickEventSeeder extends Seeder
{
    public function run(): void
    {
        $organizador = User::firstOrCreate(
            ['email' => 'organizador.demo@tickevent.test'],
            [
                'name' => 'Organizador de demostración',
                'password' => Hash::make(Str::random(40))
            ]
        );

        $sectores = [
            ['nombre' => 'Platinum', 'capacidad' => 680, 'precio' => 545],
            ['nombre' => 'VIP', 'capacidad' => 1158, 'precio' => 371],
            ['nombre' => 'General', 'capacidad' => 3090, 'precio' => 231],
            ['nombre' => 'Occ. Central', 'capacidad' => 540, 'precio' => 585],
            ['nombre' => 'Occidente 1', 'capacidad' => 811, 'precio' => 545],
            ['nombre' => 'Occidente 2', 'capacidad' => 917, 'precio' => 371],
            ['nombre' => 'Oriente', 'capacidad' => 2028, 'precio' => 371],
            ['nombre' => 'Norte', 'capacidad' => 4056, 'precio' => 185]
        ];

        $evento = Evento::updateOrCreate(
            ['slug' => 'robbie-williams'],
            [
                'organizador_id' => $organizador->id,
                'titulo' => 'Robbie Williams',
                'categoria' => 'concierto',
                'descripcion' => 'Britpop World Tour',
                'fecha_evento' => '2026-12-12 21:00:00',
                'lugar' => 'Estadio San Marcos',
                'ciudad' => 'Lima',
                'aforo_total' => array_sum(array_column($sectores, 'capacidad')),
                'estado' => 'publicado'
            ]
        );

        foreach ($sectores as $sector) {
            $zona = Zona::updateOrCreate(
                [
                    'evento_id' => $evento->id,
                    'nombre' => $sector['nombre']
                ],
                [
                    'capacidad' => $sector['capacidad'],
                    'activo' => true
                ]
            );

            $zona->tarifas()->updateOrCreate(
                ['nombre' => 'Regular'],
                [
                    'precio' => $sector['precio'],
                    'activo' => true
                ]
            );
        }
    }
}

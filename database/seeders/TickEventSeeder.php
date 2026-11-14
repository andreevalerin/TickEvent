<?php

namespace Database\Seeders;

use App\Models\Evento;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class TickEventSeeder extends Seeder
{
    public function run(): void
    {
        $sectores = [
            ['nombre' => 'Platinum', 'capacidad' => 680, 'precio' => 545],
            ['nombre' => 'VIP', 'capacidad' => 1158, 'precio' => 371],
            ['nombre' => 'General', 'capacidad' => 3090, 'precio' => 231],
            ['nombre' => 'Occ. Central', 'capacidad' => 540, 'precio' => 585],
            ['nombre' => 'Occidente 1', 'capacidad' => 811, 'precio' => 545],
            ['nombre' => 'Occidente 2', 'capacidad' => 917, 'precio' => 371],
            ['nombre' => 'Oriente', 'capacidad' => 2028, 'precio' => 371],
            ['nombre' => 'Norte', 'capacidad' => 4056, 'precio' => 185],
        ];

        DB::transaction(function () use ($sectores) {
            $organizador = User::where('email', 'organizador.demo@tickevent.test')->first();

            if (!$organizador) {
                $organizador = User::create([
                    'name' => 'Organizador de demostración',
                    'email' => 'organizador.demo@tickevent.test',
                    'password' => Hash::make(Str::random(64)),
                ]);
                $organizador->forceFill(['rol' => 'organizador'])->save();
            }

            if ($organizador->rol !== 'organizador') {
                throw new RuntimeException('La cuenta de demostración existente no tiene el rol organizador. Corrige el rol desde el panel administrativo y vuelve a ejecutar el seeder.');
            }

            $evento = Evento::where('slug', 'robbie-williams')->first();

            if (!$evento) {
                $evento = Evento::create([
                    'organizador_id' => $organizador->id,
                    'titulo' => 'Robbie Williams',
                    'slug' => 'robbie-williams',
                    'categoria' => 'concierto',
                    'descripcion' => 'Britpop World Tour',
                    'fecha_evento' => '2026-12-12 21:00:00',
                    'lugar' => 'Estadio San Marcos',
                    'ciudad' => 'Lima',
                    'aforo_total' => array_sum(array_column($sectores, 'capacidad')),
                    'imagen_principal' => 'imagenes/eventos/robbie-williams/portada.png',
                    'imagen_plano' => 'imagenes/eventos/robbie-williams/tarifario.png',
                    'estado' => 'publicado',
                ]);
            }

            if ((int) $evento->organizador_id !== (int) $organizador->id) {
                throw new RuntimeException('El evento de demostración existente pertenece a otro organizador y no será modificado.');
            }

            foreach ($sectores as $sector) {
                $zona = Zona::firstOrCreate(
                    [
                        'evento_id' => $evento->id,
                        'nombre' => $sector['nombre'],
                    ],
                    [
                        'capacidad' => $sector['capacidad'],
                        'activo' => true,
                    ]
                );

                $zona->tarifas()->firstOrCreate(
                    ['nombre' => 'Regular'],
                    [
                        'precio' => $sector['precio'],
                        'activo' => true,
                    ]
                );
            }
        }, 3);
    }
}

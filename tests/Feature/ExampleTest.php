<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\User;
use Database\Seeders\TickEventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_el_home_muestra_todos_los_eventos_publicados(): void
    {
        $organizador = User::factory()->create();

        foreach (range(1, 5) as $numero) {
            Evento::create([
                'organizador_id' => $organizador->id,
                'titulo' => 'Evento publicado '.$numero,
                'slug' => 'evento-publicado-'.$numero.'-'.Str::lower(Str::random(5)),
                'categoria' => $numero % 2 === 0 ? 'partido' : 'concierto',
                'fecha_evento' => now()->addDays($numero),
                'lugar' => 'Lugar '.$numero,
                'ciudad' => 'Lima',
                'aforo_total' => 100,
                'estado' => 'publicado',
            ]);
        }

        $respuesta = $this->get(route('inicio'));

        $respuesta->assertOk();
        foreach (range(1, 5) as $numero) {
            $respuesta->assertSee('Evento publicado '.$numero);
        }
    }

    public function test_la_busqueda_publica_filtra_eventos_reales(): void
    {
        $organizador = User::factory()->create();
        foreach (['Festival Aurora', 'Partido Central'] as $titulo) {
            Evento::create([
                'organizador_id' => $organizador->id,
                'titulo' => $titulo,
                'slug' => Str::slug($titulo).'-'.Str::lower(Str::random(5)),
                'categoria' => str_contains($titulo, 'Partido') ? 'partido' : 'concierto',
                'fecha_evento' => now()->addMonth(),
                'lugar' => 'Recinto de prueba',
                'ciudad' => 'Lima',
                'aforo_total' => 100,
                'estado' => 'publicado',
            ]);
        }

        $this->get(route('eventos.index', ['q' => 'Aurora']))
            ->assertOk()
            ->assertSee('Festival Aurora')
            ->assertDontSee('Partido Central');
    }

    public function test_el_seeder_de_demostracion_es_idempotente_y_no_sobrescribe_datos(): void
    {
        $this->seed(TickEventSeeder::class);

        $organizador = User::where('email', 'organizador.demo@tickevent.test')->firstOrFail();
        $evento = Evento::where('slug', 'robbie-williams')->firstOrFail();
        $tarifa = Tarifa::whereHas('zona', fn ($consulta) => $consulta->where('evento_id', $evento->id))
            ->where('nombre', 'Regular')
            ->firstOrFail();

        $evento->update(['descripcion' => 'Descripción local conservada']);
        $tarifa->update(['precio' => 999]);

        $this->seed(TickEventSeeder::class);

        $this->assertSame('organizador', $organizador->fresh()->rol);
        $this->assertSame('Descripción local conservada', $evento->fresh()->descripcion);
        $this->assertSame('999.00', $tarifa->fresh()->precio);
        $this->assertSame(8, $evento->zonas()->count());
    }

    public function test_contacto_y_politicas_utilizan_paginas_blade_completas(): void
    {
        $paginas = [
            'contacto' => 'Contáctanos',
            'politicas.cancelaciones' => 'Cancelaciones',
            'politicas.reembolsos' => 'Reembolsos',
            'politicas.cambios-fecha' => 'Cambios de fecha',
        ];

        foreach ($paginas as $ruta => $titulo) {
            $this->get(route($ruta))
                ->assertOk()
                ->assertSee($titulo)
                ->assertSee('Paga a tu manera');
        }
    }
}

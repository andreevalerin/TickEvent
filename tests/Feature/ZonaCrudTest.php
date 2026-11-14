<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ZonaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_organizador_lista_registra_y_edita_zonas_de_sus_eventos(): void
    {
        $organizador = $this->crearOrganizador();
        $evento = $this->crearEvento($organizador, 'Concierto principal');

        $this->actingAs($organizador)
            ->get(route('organizador.zonas.create'))
            ->assertOk()
            ->assertSee('Concierto principal');

        $this->actingAs($organizador)
            ->post(route('organizador.zonas.store'), [
                'evento_id' => $evento->id,
                'nombre' => 'Platinum',
                'capacidad' => 250,
                'activo' => 1,
                'tarifas' => [[
                    'nombre' => 'Regular',
                    'precio' => 120,
                    'activo' => 1,
                ]],
            ])
            ->assertRedirect(route('organizador.zonas.index'));

        $zona = Zona::where('evento_id', $evento->id)
            ->where('nombre', 'Platinum')
            ->firstOrFail();
        $tarifa = $zona->tarifas()->where('nombre', 'Regular')->firstOrFail();

        $this->actingAs($organizador)
            ->get(route('organizador.zonas.index'))
            ->assertOk()
            ->assertSee('Concierto principal')
            ->assertSee('Platinum');

        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'evento_id' => $evento->id,
            'nombre' => 'Platinum',
            'capacidad' => 250,
            'activo' => true,
        ]);
        $this->assertSame(250, $evento->fresh()->aforo_total);

        $this->actingAs($organizador)
            ->get(route('organizador.zonas.edit', $zona))
            ->assertOk()
            ->assertSee('Concierto principal')
            ->assertDontSee('name="id"', false);

        $this->actingAs($organizador)
            ->put(route('organizador.zonas.update', $zona), [
                'nombre' => 'Platinum renovada',
                'capacidad' => 300,
                'activo' => 1,
                'tarifas' => [
                    [
                        'id' => $tarifa->id,
                        'nombre' => 'Regular renovada',
                        'precio' => 130,
                        'activo' => 1,
                    ],
                    [
                        'nombre' => 'Preventa',
                        'precio' => 100,
                        'activo' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('organizador.zonas.index'));

        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'nombre' => 'Platinum renovada',
            'capacidad' => 300,
            'activo' => true,
        ]);
        $this->assertSame(300, $evento->fresh()->aforo_total);
        $this->assertDatabaseHas('tarifas', [
            'id' => $tarifa->id,
            'nombre' => 'Regular renovada',
            'precio' => 130,
            'activo' => true,
        ]);
        $this->assertDatabaseHas('tarifas', [
            'zona_id' => $zona->id,
            'nombre' => 'Preventa',
            'precio' => 100,
            'activo' => true,
        ]);
    }

    public function test_desactivar_una_zona_conserva_el_registro_y_desactiva_sus_tarifas(): void
    {
        $organizador = $this->crearOrganizador();
        $evento = $this->crearEvento($organizador, 'Festival local');
        $zona = Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'General',
            'capacidad' => 500,
            'activo' => true,
        ]);
        $tarifa = Tarifa::create([
            'zona_id' => $zona->id,
            'nombre' => 'Regular',
            'precio' => 90,
            'activo' => true,
        ]);

        $this->actingAs($organizador)
            ->delete(route('organizador.zonas.destroy', $zona))
            ->assertRedirect(route('organizador.zonas.index'));

        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('tarifas', [
            'id' => $tarifa->id,
            'activo' => false,
        ]);
        $this->assertSame(0, $evento->fresh()->aforo_total);
    }

    public function test_un_organizador_no_puede_gestionar_zonas_de_eventos_ajenos(): void
    {
        $propietario = $this->crearOrganizador();
        $otroOrganizador = $this->crearOrganizador();
        $evento = $this->crearEvento($propietario, 'Evento privado');
        $zona = Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'VIP',
            'capacidad' => 100,
            'activo' => true,
        ]);

        $this->actingAs($otroOrganizador)
            ->post(route('organizador.zonas.store'), [
                'evento_id' => $evento->id,
                'nombre' => 'Intento ajeno',
                'capacidad' => 50,
                'activo' => 1,
                'tarifas' => [[
                    'nombre' => 'Regular',
                    'precio' => 50,
                    'activo' => 1,
                ]],
            ])
            ->assertForbidden();

        $this->actingAs($otroOrganizador)
            ->get(route('organizador.zonas.edit', $zona))
            ->assertForbidden();

        $this->actingAs($otroOrganizador)
            ->put(route('organizador.zonas.update', $zona), [
                'nombre' => 'Cambio ajeno',
                'capacidad' => 50,
                'activo' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($otroOrganizador)
            ->delete(route('organizador.zonas.destroy', $zona))
            ->assertForbidden();

        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'nombre' => 'VIP',
            'capacidad' => 100,
            'activo' => true,
        ]);
    }

    public function test_un_cliente_no_puede_acceder_al_crud_de_zonas(): void
    {
        $cliente = User::factory()->create();

        $this->actingAs($cliente)
            ->get(route('organizador.zonas.index'))
            ->assertForbidden();
    }

    public function test_una_zona_activa_requiere_tarifas_activas_y_sin_nombres_duplicados(): void
    {
        $organizador = $this->crearOrganizador();
        $evento = $this->crearEvento($organizador, 'Evento con tarifas');

        $this->actingAs($organizador)
            ->post(route('organizador.zonas.store'), [
                'evento_id' => $evento->id,
                'nombre' => 'General',
                'capacidad' => 100,
                'activo' => 1,
                'tarifas' => [
                    ['nombre' => 'Regular', 'precio' => 80, 'activo' => 0],
                    ['nombre' => 'regular', 'precio' => 90, 'activo' => 0],
                ],
            ])
            ->assertSessionHasErrors('tarifas');

        $this->assertDatabaseMissing('zonas', [
            'evento_id' => $evento->id,
            'nombre' => 'General',
        ]);
    }

    private function crearOrganizador(): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['rol' => 'organizador'])->save();

        return $usuario;
    }

    private function crearEvento(User $organizador, string $titulo): Evento
    {
        return Evento::create([
            'organizador_id' => $organizador->id,
            'titulo' => $titulo,
            'slug' => Str::slug($titulo).'-'.Str::lower(Str::random(8)),
            'categoria' => 'concierto',
            'fecha_evento' => now()->addMonth(),
            'lugar' => 'Estadio de prueba',
            'ciudad' => 'Lima',
            'aforo_total' => 1,
            'estado' => 'publicado',
        ]);
    }
}

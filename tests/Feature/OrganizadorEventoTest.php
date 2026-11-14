<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizadorEventoTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_organizador_crea_un_evento_con_zonas_tarifas_e_imagenes(): void
    {
        Storage::fake('public');
        $organizador = $this->crearOrganizador();

        $respuesta = $this->actingAs($organizador)
            ->post(route('organizador.eventos.store'), [
                'titulo' => 'Festival de prueba',
                'categoria' => 'concierto',
                'descripcion' => 'Evento creado desde el panel',
                'fecha_evento' => now()->addMonth()->format('Y-m-d H:i:s'),
                'lugar' => 'Estadio Nacional',
                'direccion' => 'Calle de prueba 123',
                'ciudad' => 'Lima',
                'estado' => 'publicado',
                'imagen_principal' => UploadedFile::fake()->image('portada.jpg', 1200, 500),
                'imagen_plano' => UploadedFile::fake()->image('plano.png', 1000, 700),
                'zonas' => [
                    [
                        'nombre' => 'Platinum',
                        'capacidad' => 100,
                        'activo' => 1,
                        'tarifas' => [
                            [
                                'nombre' => 'Regular',
                                'precio' => 250,
                                'activo' => 1,
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'VIP',
                        'capacidad' => 50,
                        'activo' => 1,
                        'tarifas' => [
                            [
                                'nombre' => 'Preventa',
                                'precio' => 180,
                                'activo' => 1,
                            ],
                        ],
                    ],
                ],
            ]);

        $respuesta->assertRedirect(route('organizador.eventos.index'));
        $evento = Evento::where('titulo', 'Festival de prueba')->firstOrFail();

        $this->assertSame($organizador->id, $evento->organizador_id);
        $this->assertSame(150, $evento->aforo_total);
        $this->assertSame('publicado', $evento->estado);
        $this->assertDatabaseCount('zonas', 2);
        $this->assertDatabaseCount('tarifas', 2);
        Storage::disk('public')->assertExists(Str::after($evento->imagen_principal, 'storage/'));
        Storage::disk('public')->assertExists(Str::after($evento->imagen_plano, 'storage/'));
    }

    public function test_un_cliente_no_puede_acceder_al_panel_del_organizador(): void
    {
        $cliente = User::factory()->create();

        $this->actingAs($cliente)
            ->get(route('organizador.eventos.index'))
            ->assertForbidden();
    }

    public function test_un_organizador_no_puede_publicar_sin_una_zona_y_tarifa_activas(): void
    {
        $organizador = $this->crearOrganizador();

        $this->actingAs($organizador)
            ->post(route('organizador.eventos.store'), [
                'titulo' => 'Evento sin venta activa',
                'categoria' => 'concierto',
                'fecha_evento' => now()->addMonth()->format('Y-m-d H:i:s'),
                'lugar' => 'Arena Lima',
                'ciudad' => 'Lima',
                'estado' => 'publicado',
                'zonas' => [
                    [
                        'nombre' => 'General',
                        'capacidad' => 100,
                        'tarifas' => [
                            [
                                'nombre' => 'Regular',
                                'precio' => 80,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors('zonas');

        $this->assertDatabaseMissing('eventos', [
            'titulo' => 'Evento sin venta activa',
        ]);
    }

    public function test_un_organizador_no_puede_editar_eventos_ajenos(): void
    {
        $propietario = $this->crearOrganizador();
        $otroOrganizador = $this->crearOrganizador();
        $evento = $this->crearEvento($propietario);

        $this->actingAs($otroOrganizador)
            ->get(route('organizador.eventos.edit', $evento))
            ->assertForbidden();
    }

    public function test_un_organizador_actualiza_su_evento_zona_y_tarifa(): void
    {
        $organizador = $this->crearOrganizador();
        $evento = $this->crearEvento($organizador);
        $zona = Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'General',
            'capacidad' => 100,
            'activo' => true,
        ]);
        $tarifa = Tarifa::create([
            'zona_id' => $zona->id,
            'nombre' => 'Regular',
            'precio' => 80,
            'activo' => true,
        ]);

        $this->actingAs($organizador)
            ->put(route('organizador.eventos.update', $evento), [
                'titulo' => 'Evento actualizado',
                'categoria' => 'concierto',
                'descripcion' => 'Nueva descripción',
                'fecha_evento' => now()->addMonths(2)->format('Y-m-d H:i:s'),
                'lugar' => 'Arena Lima',
                'direccion' => 'Avenida de prueba 123',
                'ciudad' => 'Lima',
                'estado' => 'publicado',
                'zonas' => [
                    [
                        'id' => $zona->id,
                        'nombre' => 'General renovada',
                        'capacidad' => 180,
                        'activo' => 1,
                        'tarifas' => [
                            [
                                'id' => $tarifa->id,
                                'nombre' => 'Precio final',
                                'precio' => 95,
                                'activo' => 1,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('organizador.eventos.index'));

        $this->assertDatabaseHas('eventos', [
            'id' => $evento->id,
            'titulo' => 'Evento actualizado',
            'aforo_total' => 180,
            'estado' => 'publicado',
        ]);
        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'nombre' => 'General renovada',
            'capacidad' => 180,
            'activo' => true,
        ]);
        $this->assertDatabaseHas('tarifas', [
            'id' => $tarifa->id,
            'nombre' => 'Precio final',
            'precio' => 95,
            'activo' => true,
        ]);
    }

    public function test_retirar_un_evento_no_elimina_sus_datos(): void
    {
        $organizador = $this->crearOrganizador();
        $evento = $this->crearEvento($organizador);
        $zona = Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'General',
            'capacidad' => 100,
            'activo' => true,
        ]);
        $tarifa = Tarifa::create([
            'zona_id' => $zona->id,
            'nombre' => 'Regular',
            'precio' => 80,
            'activo' => true,
        ]);

        $this->actingAs($organizador)
            ->delete(route('organizador.eventos.destroy', $evento))
            ->assertRedirect(route('organizador.eventos.index'));

        $this->assertDatabaseHas('eventos', [
            'id' => $evento->id,
            'estado' => 'retirado',
        ]);
        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('tarifas', [
            'id' => $tarifa->id,
            'activo' => false,
        ]);
    }

    public function test_un_organizador_no_puede_republicar_un_evento_retirado(): void
    {
        $organizador = $this->crearOrganizador();
        $evento = $this->crearEvento($organizador);
        $evento->update(['estado' => 'retirado']);

        $this->actingAs($organizador)
            ->get(route('organizador.eventos.edit', $evento))
            ->assertForbidden();

        $this->actingAs($organizador)
            ->put(route('organizador.eventos.update', $evento), [
                'titulo' => 'Intento de republicación',
                'categoria' => 'concierto',
                'fecha_evento' => now()->addMonth()->format('Y-m-d H:i:s'),
                'lugar' => 'Estadio de prueba',
                'ciudad' => 'Lima',
                'estado' => 'publicado',
                'zonas' => [],
            ])
            ->assertForbidden();

        $this->assertSame('retirado', $evento->fresh()->estado);
    }

    public function test_ventas_y_perfil_solo_son_accesibles_para_el_organizador(): void
    {
        $organizador = $this->crearOrganizador();
        $cliente = User::factory()->create();

        foreach (['organizador.ventas', 'organizador.perfil'] as $ruta) {
            $this->actingAs($organizador)
                ->get(route($ruta))
                ->assertOk()
                ->assertSee('Organizador');

            $this->actingAs($cliente)
                ->get(route($ruta))
                ->assertForbidden();
        }
    }

    private function crearOrganizador(): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['rol' => 'organizador'])->save();

        return $usuario;
    }

    private function crearEvento(User $organizador): Evento
    {
        return Evento::create([
            'organizador_id' => $organizador->id,
            'titulo' => 'Evento del organizador',
            'slug' => 'evento-organizador-'.Str::lower(Str::random(10)),
            'categoria' => 'concierto',
            'fecha_evento' => now()->addMonth(),
            'lugar' => 'Estadio de prueba',
            'ciudad' => 'Lima',
            'aforo_total' => 100,
            'estado' => 'publicado',
        ]);
    }
}

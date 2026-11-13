<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizadorFormularioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_requiere_iniciar_sesion(): void
    {
        $this->get(route('organizador.eventos.create'))->assertRedirect(route('login'));
    }

    public function test_el_organizador_abre_el_formulario_nuevo_y_sus_datos_al_editar(): void
    {
        $organizador = $this->organizador();
        $evento = $this->evento($organizador, ['imagen_principal' => 'imagenes/prototipo/event-singer.jpg']);
        [$zona, $tarifa] = $this->zona($evento);
        $this->actingAs($organizador)->get(route('organizador.eventos.create'))
            ->assertOk()->assertSee('Crear nuevo evento')->assertSee('data-editor-evento', false);
        $this->get(route('organizador.eventos.edit', $evento))
            ->assertOk()->assertSee('Editar evento')->assertSee(asset('imagenes/prototipo/event-singer.jpg'))
            ->assertViewHas('zonasFormulario', fn (array $zonas): bool => $zonas[0]['id'] === $zona->id && $zonas[0]['tarifas'][0]['id'] === $tarifa->id);
    }

    #[DataProvider('vistas')]
    public function test_los_filtros_y_metricas_incluyen_solo_eventos_del_organizador(string $vista, array $esperados): void
    {
        $this->freezeTime();
        $organizador = $this->organizador();
        $this->evento($organizador, ['titulo' => 'Festival próximo']);
        $this->evento($organizador, ['titulo' => 'Festival pasado', 'fecha_evento' => now()->subDay()]);
        $this->evento($organizador, ['titulo' => 'Festival borrador', 'estado' => 'borrador']);
        $this->evento($organizador, ['titulo' => 'Festival retirado', 'estado' => 'retirado']);
        $this->evento($this->organizador(), ['titulo' => 'Festival ajeno']);

        $this->actingAs($organizador)->get(route('organizador.eventos.index', ['buscar' => 'Festival', 'vista' => $vista, 'orden' => 'titulo']))
            ->assertOk()
            ->assertViewHas('eventos', fn (LengthAwarePaginator $eventos): bool => $eventos->pluck('titulo')->all() === $esperados)
            ->assertViewHas('metricas', ['total' => 4, 'publicados' => 2, 'borradores' => 1, 'aforo' => 200])
            ->assertDontSee('Festival ajeno');
    }

    public function test_el_listado_pagina_y_conserva_la_busqueda(): void
    {
        $organizador = $this->organizador();
        foreach (range(1, 13) as $numero) {
            $this->evento($organizador, ['titulo' => 'Festival '.$numero]);
        }

        $this->actingAs($organizador)->get(route('organizador.eventos.index', ['buscar' => 'Festival', 'vista' => 'proximos']))
            ->assertOk()
            ->assertViewHas('eventos', fn (LengthAwarePaginator $eventos): bool => $eventos->count() === 12 && $eventos->total() === 13)
            ->assertSee('buscar=Festival')
            ->assertSee('page=2');
    }

    public function test_un_filtro_invalido_vuelve_al_panel_con_error(): void
    {
        $this->actingAs($this->organizador())->from(route('organizador.eventos.index'))
            ->get(route('organizador.eventos.index', ['vista' => 'ajenos']))
            ->assertRedirect(route('organizador.eventos.index'))
            ->assertSessionHasErrors(['vista' => 'Selecciona un filtro válido.']);
    }

    public function test_dos_zonas_pueden_usar_el_mismo_nombre_de_tarifa(): void
    {
        $organizador = $this->organizador();
        $datos = $this->datos();
        $datos['zonas'][] = array_replace($datos['zonas'][0], ['nombre' => 'VIP', 'capacidad' => 50]);

        $this->actingAs($organizador)->post(route('organizador.eventos.store'), $datos)
            ->assertRedirect(route('organizador.eventos.index'));
        $this->assertDatabaseHas('eventos', ['titulo' => 'Nuevo festival', 'aforo_total' => 150]);
        $this->assertDatabaseCount('tarifas', 2);
    }

    public function test_las_zonas_repetidas_no_generan_errores_de_base_de_datos_ni_guardan_imagenes(): void
    {
        Storage::fake('public');
        $datos = $this->datos();
        $datos['imagen_principal'] = UploadedFile::fake()->image('portada.png');
        $datos['zonas'][] = array_replace($datos['zonas'][0], ['nombre' => 'GENERAL']);

        $this->actingAs($this->organizador())->from(route('organizador.eventos.create'))
            ->post(route('organizador.eventos.store'), $datos)
            ->assertRedirect(route('organizador.eventos.create'))
            ->assertSessionHasErrors(['zonas.1.nombre' => 'Los nombres de las zonas no pueden repetirse.']);
        $this->assertDatabaseCount('eventos', 0);
        Storage::disk('public')->assertEmpty();
    }

    public function test_las_tarifas_repetidas_en_una_zona_se_rechazan(): void
    {
        $datos = $this->datos();
        $datos['zonas'][0]['tarifas'][] = ['nombre' => 'REGULAR', 'precio' => 80, 'activo' => 1];

        $this->actingAs($this->organizador())->post(route('organizador.eventos.store'), $datos)
            ->assertSessionHasErrors(['zonas.0.tarifas' => 'Los nombres de las tarifas no pueden repetirse dentro de una zona.']);
        $this->assertDatabaseCount('eventos', 0);
    }

    public function test_agregar_una_zona_con_nombre_existente_muestra_un_error_util(): void
    {
        $organizador = $this->organizador();
        $evento = $this->evento($organizador);
        $this->zona($evento);

        $this->actingAs($organizador)->put(route('organizador.eventos.update', $evento), $this->datos())
            ->assertSessionHasErrors(['zonas.0.nombre' => 'Ya existe una zona con ese nombre. Edita la zona existente o elige otro nombre.']);
        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'titulo' => 'Evento propio']);
        $this->assertDatabaseCount('zonas', 1);
    }

    #[DataProvider('estadosOcupados')]
    public function test_no_se_reduce_la_capacidad_por_debajo_de_las_entradas_ocupadas(string $estado, int $minutos): void
    {
        $this->freezeTime();
        $organizador = $this->organizador();
        $evento = $this->evento($organizador);
        [$zona, $tarifa] = $this->zona($evento);
        $this->reservar($evento, $tarifa, $estado, $minutos);
        $datos = $this->datosActualizacion($zona, $tarifa);
        $datos['zonas'][0]['capacidad'] = 2;

        $this->actingAs($organizador)->put(route('organizador.eventos.update', $evento), $datos)
            ->assertSessionHasErrors(['zonas.0.capacidad' => 'La capacidad no puede ser menor que las entradas vendidas o reservadas.']);
        $this->assertDatabaseHas('zonas', ['id' => $zona->id, 'capacidad' => 100]);
        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'titulo' => 'Evento propio']);
    }

    public function test_una_reserva_vencida_no_impide_reducir_el_aforo(): void
    {
        $this->freezeTime();
        $organizador = $this->organizador();
        $evento = $this->evento($organizador);
        [$zona, $tarifa] = $this->zona($evento);
        $this->reservar($evento, $tarifa, 'activa', 0);
        $datos = $this->datosActualizacion($zona, $tarifa);
        $datos['zonas'][0]['capacidad'] = 2;

        $this->actingAs($organizador)->put(route('organizador.eventos.update', $evento), $datos)
            ->assertRedirect(route('organizador.eventos.index'));
        $this->assertDatabaseHas('zonas', ['id' => $zona->id, 'capacidad' => 2]);
        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'aforo_total' => 2]);
    }

    public function test_no_se_quita_una_zona_con_reservas_pero_se_puede_desactivar(): void
    {
        $this->freezeTime();
        $organizador = $this->organizador();
        $evento = $this->evento($organizador);
        [$zona, $tarifa] = $this->zona($evento);
        $this->reservar($evento, $tarifa, 'confirmada', -1);
        $datos = $this->datos();
        $datos['zonas'][0]['nombre'] = 'Otra zona';

        $this->actingAs($organizador)->put(route('organizador.eventos.update', $evento), $datos)
            ->assertSessionHasErrors(['zonas' => 'No puedes quitar una zona con entradas vendidas o reservadas. Puedes desactivarla para detener nuevas ventas.']);
        $this->assertDatabaseCount('zonas', 1);

        $datos = $this->datosActualizacion($zona, $tarifa);
        $datos['zonas'][0]['activo'] = 0;
        $this->put(route('organizador.eventos.update', $evento), $datos)->assertRedirect(route('organizador.eventos.index'));
        $this->assertDatabaseHas('zonas', ['id' => $zona->id, 'activo' => false]);
        $this->assertDatabaseCount('detalle_reserva', 1);
    }

    public function test_un_id_de_zona_ajena_no_modifica_el_evento_ni_sube_imagenes(): void
    {
        Storage::fake('public');
        $organizador = $this->organizador();
        $evento = $this->evento($organizador);
        [$zonaAjena, $tarifaAjena] = $this->zona($this->evento($this->organizador()));
        $datos = $this->datosActualizacion($zonaAjena, $tarifaAjena);
        $datos['imagen_principal'] = UploadedFile::fake()->image('portada.png');

        $this->actingAs($organizador)->put(route('organizador.eventos.update', $evento), $datos)->assertNotFound();
        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'titulo' => 'Evento propio']);
        Storage::disk('public')->assertEmpty();
    }

    public function test_un_id_de_tarifa_ajena_no_se_puede_mover_a_otra_zona(): void
    {
        $organizador = $this->organizador();
        $evento = $this->evento($organizador);
        [$zona, $tarifa] = $this->zona($evento);
        [$zonaAjena, $tarifaAjena] = $this->zona($this->evento($this->organizador()));
        $datos = $this->datosActualizacion($zona, $tarifa);
        $datos['zonas'][0]['tarifas'][0]['id'] = $tarifaAjena->id;

        $this->actingAs($organizador)->put(route('organizador.eventos.update', $evento), $datos)->assertNotFound();
        $this->assertDatabaseHas('tarifas', ['id' => $tarifaAjena->id, 'zona_id' => $zonaAjena->id]);
        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'titulo' => 'Evento propio']);
    }

    public function test_los_indices_discontinuos_se_conservan_tras_un_error(): void
    {
        $datos = $this->datos();
        $datos['zonas'] = [0 => $datos['zonas'][0], 2 => array_replace($datos['zonas'][0], ['nombre' => 'VIP', 'capacidad' => 0])];
        $this->actingAs($this->organizador())->from(route('organizador.eventos.create'))
            ->post(route('organizador.eventos.store'), $datos)->assertSessionHasErrors('zonas.2.capacidad');
        $this->get(route('organizador.eventos.create'))->assertOk()->assertSee('data-zona="2"', false)->assertSee('Nuevo festival');
    }

    public function test_guardar_un_evento_retirado_no_lo_publica_automaticamente(): void
    {
        $organizador = $this->organizador();
        $evento = $this->evento($organizador, ['estado' => 'retirado']);
        [$zona, $tarifa] = $this->zona($evento);
        $datos = $this->datosActualizacion($zona, $tarifa);
        $datos['estado'] = 'retirado';

        $this->actingAs($organizador)->get(route('organizador.eventos.edit', $evento))
            ->assertOk()->assertSee('value="retirado" selected', false);
        $this->put(route('organizador.eventos.update', $evento), $datos)->assertRedirect(route('organizador.eventos.index'));
        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'estado' => 'retirado']);
    }

    public static function vistas(): array
    {
        return [
            'todos' => ['todos', ['Festival borrador', 'Festival pasado', 'Festival próximo', 'Festival retirado']],
            'próximos' => ['proximos', ['Festival próximo']],
            'pasados' => ['pasados', ['Festival pasado']],
            'borradores' => ['borradores', ['Festival borrador']],
            'retirados' => ['retirados', ['Festival retirado']],
        ];
    }

    public static function estadosOcupados(): array
    {
        return ['confirmada' => ['confirmada', -1], 'en proceso' => ['procesando_pago', -1], 'activa' => ['activa', 15]];
    }

    private function organizador(): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['rol' => 'organizador'])->save();

        return $usuario;
    }

    private function evento(User $organizador, array $atributos = []): Evento
    {
        return Evento::create(array_merge([
            'organizador_id' => $organizador->id, 'titulo' => 'Evento propio',
            'slug' => 'evento-'.(Evento::count() + 1), 'categoria' => 'concierto',
            'fecha_evento' => now()->addMonth(), 'lugar' => 'Estadio de prueba', 'ciudad' => 'Lima',
            'aforo_total' => 100, 'estado' => 'publicado',
        ], $atributos));
    }

    private function zona(Evento $evento): array
    {
        $zona = $evento->zonas()->create(['nombre' => 'General', 'capacidad' => 100, 'activo' => true]);
        $tarifa = $zona->tarifas()->create(['nombre' => 'Regular', 'precio' => 100, 'activo' => true]);

        return [$zona, $tarifa];
    }

    private function datos(): array
    {
        return [
            'titulo' => 'Nuevo festival', 'categoria' => 'concierto', 'fecha_evento' => now()->addMonth()->format('Y-m-d H:i:s'),
            'lugar' => 'Arena Lima', 'ciudad' => 'Lima', 'estado' => 'borrador',
            'zonas' => [['nombre' => 'General', 'capacidad' => 100, 'activo' => 1, 'tarifas' => [['nombre' => 'Regular', 'precio' => 100, 'activo' => 1]]]],
        ];
    }

    private function datosActualizacion(Zona $zona, Tarifa $tarifa): array
    {
        $datos = $this->datos();
        $datos['zonas'][0]['id'] = $zona->id;
        $datos['zonas'][0]['tarifas'][0]['id'] = $tarifa->id;

        return $datos;
    }

    private function reservar(Evento $evento, Tarifa $tarifa, string $estado, int $minutos): void
    {
        $reserva = Reserva::create(['usuario_id' => User::factory()->create()->id, 'evento_id' => $evento->id, 'estado' => $estado, 'expira_en' => now()->addMinutes($minutos)]);
        $reserva->detalles()->create(['tarifa_id' => $tarifa->id, 'cantidad' => 3, 'precio_unitario' => 100, 'cargo_servicio_unitario' => 5]);
    }
}

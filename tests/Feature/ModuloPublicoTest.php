<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\TickEventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModuloPublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_inicio_muestra_seis_eventos_publicados_en_orden_de_fecha(): void
    {
        foreach (range(7, 1) as $dia) {
            $this->crearEvento('evento-'.$dia, ['fecha_evento' => '2026-12-0'.$dia.' 21:00:00']);
        }
        $this->crearEvento('evento-borrador', ['estado' => 'borrador']);
        $this->crearEvento('evento-retirado', ['estado' => 'retirado']);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSeeInOrder(['evento-1', 'evento-2', 'evento-3', 'evento-4', 'evento-5', 'evento-6'])
            ->assertDontSee('evento-7')
            ->assertDontSee('evento-borrador')
            ->assertDontSee('evento-retirado');
    }

    #[DataProvider('categorias')]
    public function test_cada_categoria_muestra_solo_sus_eventos_publicados(string $ruta, string $categoria, string $otraCategoria): void
    {
        $incluido = $this->crearEvento('evento-incluido', ['categoria' => $categoria]);
        $this->crearEvento('evento-otra-categoria', ['categoria' => $otraCategoria]);
        $this->crearEvento('evento-borrador', ['categoria' => $categoria, 'estado' => 'borrador']);
        $this->crearEvento('evento-retirado', ['categoria' => $categoria, 'estado' => 'retirado']);

        $this->get(route($ruta))
            ->assertOk()
            ->assertSee(route('eventos.show', $incluido))
            ->assertSee('evento-incluido')
            ->assertDontSee('evento-otra-categoria')
            ->assertDontSee('evento-borrador')
            ->assertDontSee('evento-retirado');
    }

    public function test_el_catalogo_muestra_ambas_categorias_sin_eventos_ocultos(): void
    {
        $this->crearEvento('concierto-publicado');
        $this->crearEvento('partido-publicado', ['categoria' => 'partido']);
        $this->crearEvento('evento-borrador', ['estado' => 'borrador']);
        $this->crearEvento('evento-retirado', ['estado' => 'retirado']);

        $this->get(route('eventos.index'))
            ->assertOk()
            ->assertSee('concierto-publicado')
            ->assertSee('partido-publicado')
            ->assertDontSee('evento-borrador')
            ->assertDontSee('evento-retirado');
    }

    #[DataProvider('listados')]
    public function test_los_listados_vacios_permiten_navegar_a_otras_categorias(string $ruta): void
    {
        $this->get(route($ruta))
            ->assertOk()
            ->assertSee('Por el momento no hay eventos disponibles en esta sección.')
            ->assertSee(route('conciertos.index'))
            ->assertSee(route('partidos.index'));
    }

    public function test_contacto_usa_el_layout_y_no_ofrece_un_envio_ficticio(): void
    {
        $this->get(route('contacto'))
            ->assertOk()
            ->assertViewIs('publico.contacto')
            ->assertSee('Contáctanos')
            ->assertSee('Los canales de atención de TickEvent todavía no están publicados.')
            ->assertSee(asset('css/tickevent.css'))
            ->assertSee(route('entradas.index'));
    }

    public function test_robbie_muestra_las_imagenes_y_el_enlace_de_compra(): void
    {
        $this->seed(TickEventSeeder::class);

        $this->get(route('eventos.show', 'robbie-williams'))
            ->assertOk()
            ->assertSee('Robbie Williams')
            ->assertSee(asset('imagenes/prototipo/event-singer.jpg'))
            ->assertSee(asset('imagenes/prototipo/robbie-williams-stadium.png'))
            ->assertSee(route('eventos.comprar', 'robbie-williams'));
    }

    #[DataProvider('eventosOcultos')]
    public function test_un_evento_oculto_no_permite_abrir_su_ficha_ni_su_compra(string $ruta, string $estado): void
    {
        $evento = $this->crearEvento('evento-oculto', ['estado' => $estado]);

        $this->get(route($ruta, $evento))->assertNotFound();
    }

    public function test_el_listado_escapa_el_titulo_del_evento(): void
    {
        $this->crearEvento('evento-seguro', ['titulo' => '<script>alert("evento")</script>']);

        $this->get(route('eventos.index'))
            ->assertOk()
            ->assertSee('<script>alert("evento")</script>')
            ->assertDontSee('<script>alert("evento")</script>', false);
    }

    public function test_la_busqueda_combina_texto_y_categoria_sin_revelar_eventos_ocultos(): void
    {
        $porTitulo = $this->crearEvento('coincide-titulo', ['titulo' => 'Festival Lima']);
        $porLugar = $this->crearEvento('coincide-lugar', ['lugar' => 'Arena Lima', 'ciudad' => 'Callao']);
        $porCiudad = $this->crearEvento('coincide-ciudad');
        $this->crearEvento('partido-excluido', ['categoria' => 'partido']);
        $this->crearEvento('oculto-excluido', ['estado' => 'borrador']);
        $this->crearEvento('sin-coincidencia', ['ciudad' => 'Cusco']);

        $this->get(route('eventos.index', ['buscar' => '  Lima  ', 'categoria' => 'concierto']))
            ->assertOk()
            ->assertViewHas('eventos', fn (Collection $eventos): bool => $eventos->modelKeys() === [$porTitulo->id, $porLugar->id, $porCiudad->id])
            ->assertViewHas('busqueda', 'Lima');
    }

    public function test_la_ruta_de_categoria_prevalece_sobre_el_filtro_del_buscador(): void
    {
        $concierto = $this->crearEvento('concierto-lima');
        $this->crearEvento('partido-lima', ['categoria' => 'partido']);

        $this->get(route('conciertos.index', ['buscar' => 'Lima', 'categoria' => 'partido']))
            ->assertOk()
            ->assertViewHas('eventos', fn (Collection $eventos): bool => $eventos->modelKeys() === [$concierto->id]);
    }

    #[DataProvider('filtrosInvalidos')]
    public function test_el_buscador_rechaza_filtros_invalidos(array $filtros, string $campo, string $mensaje): void
    {
        $this->from(route('eventos.index'))
            ->get(route('eventos.index', $filtros))
            ->assertRedirect(route('eventos.index'))
            ->assertSessionHasErrors([$campo => $mensaje]);
    }

    public function test_el_precio_desde_excluye_tarifas_y_zonas_inactivas(): void
    {
        $evento = $this->crearEvento('evento-con-precios');
        $zona = $this->crearZona($evento, 'General');
        $this->crearTarifa($zona, 'Regular', 125);
        $this->crearTarifa($zona, 'Preventa', 75);
        $this->crearTarifa($zona, 'Suspendida', 10, false);
        $this->crearTarifa($this->crearZona($evento, 'Cerrada', 10, false), 'Regular', 5);

        $this->get(route('eventos.index'))
            ->assertOk()
            ->assertSee('S/75')
            ->assertDontSee('S/10')
            ->assertDontSee('S/5');
    }

    public function test_un_evento_sin_tarifas_publicadas_no_muestra_un_precio_inventado(): void
    {
        $this->crearEvento('sin-tarifas');

        $this->get(route('eventos.index'))
            ->assertOk()
            ->assertSee('Tarifas por confirmar')
            ->assertDontSee('S/0');
    }

    public function test_la_ficha_comparte_el_aforo_entre_tarifas_y_descontabiliza_reservas_vencidas(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 13)->startOfDay());
        $evento = $this->crearEvento('aforo-compartido');
        $zona = $this->crearZona($evento, 'General', 10);
        $regular = $this->crearTarifa($zona, 'Regular');
        $preventa = $this->crearTarifa($zona, 'Preventa', 80);
        $this->crearTarifa($zona, 'Inactiva', 50, false);
        $this->crearTarifa($this->crearZona($evento, 'Cerrada', 10, false), 'Regular');
        foreach ([['confirmada', -1], ['procesando_pago', -1], ['activa', 10], ['activa', -1], ['cancelada', 10]] as [$estado, $minutos]) {
            $this->crearReserva($evento, $regular, 2, $estado, $minutos);
        }

        $this->get(route('eventos.show', $evento))
            ->assertOk()
            ->assertViewHas('sectores', fn (Collection $sectores): bool => $sectores->pluck('disponibles', 'id')->sortKeys()->all() === [$regular->id => 4, $preventa->id => 4]);
    }

    public function test_la_ficha_limita_la_disponibilidad_por_el_aforo_total_del_evento(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 13)->startOfDay());
        $evento = $this->crearEvento('aforo-total', ['aforo_total' => 3]);
        $tarifa = $this->crearTarifa($this->crearZona($evento, 'General', 10), 'Regular');
        $otraTarifa = $this->crearTarifa($this->crearZona($evento, 'VIP', 10), 'Regular');
        $this->crearReserva($evento, $otraTarifa, 2);

        $this->get(route('eventos.show', $evento))
            ->assertOk()
            ->assertViewHas('sectores', fn (Collection $sectores): bool => $sectores->pluck('disponibles', 'id')->sortKeys()->all() === [$tarifa->id => 1, $otraTarifa->id => 1]);
    }

    public function test_los_eventos_pasados_no_ofrecen_sectores_disponibles(): void
    {
        $evento = $this->crearEvento('evento-pasado', ['fecha_evento' => now()->subDay()]);
        $tarifa = $this->crearTarifa($this->crearZona($evento, 'General'), 'Regular');

        $this->get(route('eventos.show', $evento))
            ->assertOk()
            ->assertSee('Sin disponibilidad')
            ->assertViewHas('sectores', fn (Collection $sectores): bool => $sectores->pluck('disponibles', 'id')->all() === [$tarifa->id => 0]);
    }

    public function test_la_compra_recibe_la_tarifa_elegida_solo_si_esta_disponible_en_el_evento(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 13)->startOfDay());
        $evento = $this->crearEvento('evento-elegido');
        $tarifa = $this->crearTarifa($this->crearZona($evento, 'General', 2), 'Regular');
        $ajena = $this->crearTarifa($this->crearZona($this->crearEvento('otro-evento'), 'General'), 'Regular');

        $this->get(route('eventos.comprar', ['evento' => $evento, 'tarifa_id' => $tarifa->id]))
            ->assertOk()
            ->assertViewHas('tarifaSeleccionada', $tarifa->id);
        $this->get(route('eventos.comprar', ['evento' => $evento, 'tarifa_id' => $ajena->id]))
            ->assertOk()
            ->assertViewHas('tarifaSeleccionada', fn (?int $seleccion): bool => $seleccion === null);

        $this->crearReserva($evento, $tarifa, 2);
        $this->get(route('eventos.comprar', ['evento' => $evento, 'tarifa_id' => $tarifa->id]))
            ->assertOk()
            ->assertViewHas('tarifaSeleccionada', fn (?int $seleccion): bool => $seleccion === null);
    }

    public static function filtrosInvalidos(): array
    {
        return [
            'búsqueda como arreglo' => [['buscar' => ['Lima']], 'buscar', 'Escribe el nombre de un artista, equipo o evento.'],
            'búsqueda extensa' => [['buscar' => str_repeat('a', 121)], 'buscar', 'La búsqueda admite hasta 120 caracteres.'],
            'categoría desconocida' => [['categoria' => 'teatro'], 'categoria', 'Elige una categoría válida.'],
            'categoría como arreglo' => [['categoria' => ['concierto']], 'categoria', 'Elige una categoría válida.'],
        ];
    }

    public static function categorias(): array
    {
        return [
            'conciertos' => ['conciertos.index', 'concierto', 'partido'],
            'partidos' => ['partidos.index', 'partido', 'concierto'],
        ];
    }

    public static function listados(): array
    {
        return [
            'inicio' => ['inicio'],
            'catalogo' => ['eventos.index'],
            'conciertos' => ['conciertos.index'],
            'partidos' => ['partidos.index'],
        ];
    }

    public static function eventosOcultos(): array
    {
        return [
            'ficha borrador' => ['eventos.show', 'borrador'],
            'ficha retirada' => ['eventos.show', 'retirado'],
            'compra borrador' => ['eventos.comprar', 'borrador'],
            'compra retirada' => ['eventos.comprar', 'retirado'],
        ];
    }

    private function crearEvento(string $slug, array $atributos = []): Evento
    {
        return Evento::create(array_merge([
            'organizador_id' => User::factory()->create()->id,
            'titulo' => $slug,
            'slug' => $slug,
            'categoria' => 'concierto',
            'fecha_evento' => '2026-12-12 21:00:00',
            'lugar' => 'Estadio de prueba',
            'ciudad' => 'Lima',
            'aforo_total' => 100,
            'estado' => 'publicado',
        ], $atributos));
    }

    private function crearZona(Evento $evento, string $nombre, int $capacidad = 10, bool $activo = true): Zona
    {
        return $evento->zonas()->create(compact('nombre', 'capacidad', 'activo'));
    }

    private function crearTarifa(Zona $zona, string $nombre, int $precio = 100, bool $activo = true): Tarifa
    {
        return $zona->tarifas()->create(compact('nombre', 'precio', 'activo'));
    }

    private function crearReserva(Evento $evento, Tarifa $tarifa, int $cantidad, string $estado = 'confirmada', int $minutos = -1): void
    {
        $reserva = Reserva::create([
            'usuario_id' => User::factory()->create()->id,
            'evento_id' => $evento->id,
            'estado' => $estado,
            'expira_en' => now()->addMinutes($minutos),
        ]);
        $reserva->detalles()->create([
            'tarifa_id' => $tarifa->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $tarifa->precio,
            'cargo_servicio_unitario' => $evento->cargoServicio(),
        ]);
    }
}

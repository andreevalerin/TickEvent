<?php

namespace Tests\Feature;

use App\Models\Entrada;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ValidacionEntradaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_personal_asignado_puede_abrir_el_control_y_validar_una_sola_vez(): void
    {
        [, $entrada] = $this->crearEntrada();
        $personal = $this->crearPersonal($entrada->reserva->evento_id);

        $this->actingAs($personal)
            ->get(route('entradas.validar'))
            ->assertOk()
            ->assertSee('Personal de acceso')
            ->assertSee('Activar cámara')
            ->assertDontSee('Mis entradas')
            ->assertDontSee('Conciertos')
            ->assertSee('navigator.mediaDevices.getUserMedia', false);

        $this->actingAs($personal)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => strtolower($entrada->codigo),
                'evento_id' => $entrada->reserva->evento_id,
            ])
            ->assertOk()
            ->assertViewHas('permitido', true);

        $this->assertDatabaseHas('entradas', [
            'id' => $entrada->id,
            'estado' => 'usada',
            'validada_por' => $personal->id,
        ]);

        $this->actingAs($personal)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => $entrada->codigo,
                'evento_id' => $entrada->reserva->evento_id,
            ])
            ->assertOk()
            ->assertViewHas('permitido', false);
    }

    public function test_el_personal_de_acceso_no_valida_eventos_no_asignados(): void
    {
        [, $entradaAsignada] = $this->crearEntrada();
        [, $entradaAjena] = $this->crearEntrada();
        $personal = $this->crearPersonal($entradaAsignada->reserva->evento_id);

        $this->actingAs($personal)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => $entradaAjena->codigo,
                'evento_id' => $entradaAsignada->reserva->evento_id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('entradas', [
            'id' => $entradaAjena->id,
            'estado' => 'emitida',
            'validada_por' => null,
        ]);
    }

    public function test_administradores_organizadores_y_clientes_no_acceden_a_la_validacion_operativa(): void
    {
        [$organizador, $entrada] = $this->crearEntrada();
        $administrador = User::factory()->create();
        $administrador->forceFill(['rol' => 'administrador'])->save();
        $cliente = User::factory()->create();

        foreach ([$administrador, $organizador, $cliente] as $usuario) {
            $this->actingAs($usuario)
                ->get(route('entradas.validar'))
                ->assertForbidden();
            $this->actingAs($usuario)
                ->post(route('entradas.validar.procesar'), [
                    'codigo' => $entrada->codigo,
                    'evento_id' => $entrada->reserva->evento_id,
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseHas('entradas', [
            'id' => $entrada->id,
            'estado' => 'emitida',
            'validada_por' => null,
        ]);
    }

    public function test_el_personal_sin_eventos_asignados_ve_el_panel_vacio_y_no_puede_validar(): void
    {
        $personal = $this->crearPersonal();

        $this->actingAs($personal)
            ->get(route('entradas.validar'))
            ->assertOk()
            ->assertSee('No tienes eventos asignados')
            ->assertDontSee('Activar cámara');

        $this->actingAs($personal)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => 'TV-INEXISTENTE',
                'evento_id' => 1,
            ])
            ->assertForbidden();
    }

    public function test_el_personal_solo_puede_navegar_por_su_panel_y_cerrar_sesion(): void
    {
        [, $entrada] = $this->crearEntrada();
        $personal = $this->crearPersonal($entrada->reserva->evento_id);

        foreach ([route('inicio'), route('eventos.index'), route('entradas.index'), route('contacto')] as $ruta) {
            $this->actingAs($personal)
                ->get($ruta)
                ->assertRedirect(route('entradas.validar'));
        }

        $this->actingAs($personal)
            ->post(route('reservas.guardar', $entrada->reserva->evento), [])
            ->assertForbidden();

        $this->actingAs($personal)
            ->post(route('logout'))
            ->assertRedirect(route('inicio'));
        $this->assertGuest();
    }

    public function test_el_personal_rechaza_un_codigo_inexistente_sin_inventar_una_entrada(): void
    {
        [, $entrada] = $this->crearEntrada();
        $personal = $this->crearPersonal($entrada->reserva->evento_id);

        $this->actingAs($personal)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => 'TV-CODIGO-INEXISTENTE',
                'evento_id' => $entrada->reserva->evento_id,
            ])
            ->assertOk()
            ->assertViewHas('permitido', false)
            ->assertSee('no corresponde a una entrada emitida');

        $this->assertDatabaseCount('entradas', 1);
    }

    public function test_el_login_del_personal_siempre_redirige_al_panel_aunque_no_tenga_asignaciones(): void
    {
        $personal = $this->crearPersonal();
        $personal->forceFill(['password' => Hash::make('clave-segura-123')])->save();

        $this->withSession(['url.intended' => route('eventos.index')])
            ->post(route('login.ingresar'), [
                'email' => $personal->email,
                'password' => 'clave-segura-123',
            ])
            ->assertRedirect(route('entradas.validar'));
    }

    public function test_el_selector_solo_acepta_eventos_asignados_y_limita_la_validacion_al_seleccionado(): void
    {
        [, $primeraEntrada] = $this->crearEntrada();
        [, $segundaEntrada] = $this->crearEntrada();
        $personal = $this->crearPersonal();
        $personal->eventosAsignadosAcceso()->attach([
            $primeraEntrada->reserva->evento_id,
            $segundaEntrada->reserva->evento_id,
        ]);

        $this->actingAs($personal)
            ->get(route('entradas.validar', ['evento_id' => $segundaEntrada->reserva->evento_id]))
            ->assertOk()
            ->assertViewHas('eventoSeleccionado', fn ($evento) => $evento->id === $segundaEntrada->reserva->evento_id)
            ->assertSee($segundaEntrada->reserva->evento->titulo);

        $this->actingAs($personal)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => $primeraEntrada->codigo,
                'evento_id' => $segundaEntrada->reserva->evento_id,
            ])
            ->assertForbidden();

        $this->actingAs($personal)
            ->get(route('entradas.validar', ['evento_id' => 999999]))
            ->assertForbidden();

        $this->assertSame('emitida', $primeraEntrada->fresh()->estado);
    }

    private function crearPersonal(?int $eventoId = null): User
    {
        $personal = User::factory()->create();
        $personal->forceFill(['rol' => 'personal_acceso'])->save();

        if ($eventoId) {
            $personal->eventosAsignadosAcceso()->attach($eventoId);
        }

        return $personal;
    }

    private function crearEntrada(): array
    {
        $organizador = User::factory()->create();
        $organizador->forceFill(['rol' => 'organizador'])->save();
        $cliente = User::factory()->create();
        $evento = Evento::create([
            'organizador_id' => $organizador->id,
            'titulo' => 'Evento de acceso',
            'slug' => 'evento-acceso-'.Str::lower(Str::random(10)),
            'categoria' => 'concierto',
            'fecha_evento' => now()->addMonth(),
            'lugar' => 'Estadio de prueba',
            'ciudad' => 'Lima',
            'aforo_total' => 100,
            'estado' => 'publicado',
        ]);
        $zona = Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'Platinum',
            'capacidad' => 100,
            'activo' => true,
        ]);
        $tarifa = Tarifa::create([
            'zona_id' => $zona->id,
            'nombre' => 'Regular',
            'precio' => 100,
            'activo' => true,
        ]);
        $reserva = Reserva::create([
            'usuario_id' => $cliente->id,
            'evento_id' => $evento->id,
            'estado' => 'confirmada',
            'expira_en' => now()->subMinute(),
        ]);
        $entrada = Entrada::create([
            'reserva_id' => $reserva->id,
            'tarifa_id' => $tarifa->id,
            'dni' => '71234567',
            'codigo' => 'TV-'.Str::upper(Str::random(24)),
            'estado' => 'emitida',
        ]);

        return [$organizador, $entrada];
    }
}

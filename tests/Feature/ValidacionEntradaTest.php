<?php

namespace Tests\Feature;

use App\Models\Entrada;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ValidacionEntradaTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_entrada_confirmada_solo_puede_validarse_una_vez(): void
    {
        [$organizador, $entrada] = $this->crearEntrada();

        $this->actingAs($organizador)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => strtolower($entrada->codigo),
            ])
            ->assertOk()
            ->assertViewHas('permitido', true);

        $this->assertDatabaseHas('entradas', [
            'id' => $entrada->id,
            'estado' => 'usada',
            'validada_por' => $organizador->id,
        ]);
        $this->assertDatabaseHas('validaciones_entrada', [
            'entrada_id' => $entrada->id,
            'resultado' => 'aceptada',
            'motivo' => 'Acceso permitido',
        ]);

        $this->actingAs($organizador)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => $entrada->codigo,
            ])
            ->assertOk()
            ->assertViewHas('permitido', false)
            ->assertSee('Esta entrada ya fue utilizada.');

        $this->assertDatabaseCount('validaciones_entrada', 2);
        $this->assertDatabaseHas('validaciones_entrada', [
            'entrada_id' => $entrada->id,
            'resultado' => 'rechazada',
            'motivo' => 'Entrada ya utilizada',
        ]);
    }

    public function test_un_organizador_no_puede_validar_entradas_de_otro_evento(): void
    {
        [$organizador, $entrada] = $this->crearEntrada();
        $otroOrganizador = User::factory()->create();
        $otroOrganizador->forceFill(['rol' => 'organizador'])->save();

        Evento::create([
            'organizador_id' => $otroOrganizador->id,
            'titulo' => 'Otro evento',
            'slug' => 'otro-evento-'.Str::lower(Str::random(10)),
            'categoria' => 'concierto',
            'fecha_evento' => now()->addMonth(),
            'lugar' => 'Otro estadio',
            'ciudad' => 'Lima',
            'aforo_total' => 100,
            'estado' => 'publicado',
        ]);

        $this->actingAs($otroOrganizador)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => $entrada->codigo,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('entradas', [
            'id' => $entrada->id,
            'estado' => 'emitida',
            'validada_por' => null,
        ]);
        $this->assertDatabaseHas('validaciones_entrada', [
            'entrada_id' => $entrada->id,
            'operador_id' => $otroOrganizador->id,
            'resultado' => 'rechazada',
            'motivo' => 'Evento no autorizado',
        ]);
    }

    public function test_un_cliente_no_puede_abrir_el_control_de_acceso(): void
    {
        $cliente = User::factory()->create();

        $this->actingAs($cliente)
            ->get(route('entradas.validar'))
            ->assertForbidden();
    }

    public function test_el_personal_de_acceso_solo_valida_eventos_asignados(): void
    {
        [, $entradaAsignada] = $this->crearEntrada();
        [, $entradaAjena] = $this->crearEntrada();
        $personal = User::factory()->create();
        $personal->forceFill(['rol' => 'personal_acceso'])->save();
        $personal->eventosAsignadosAcceso()->attach($entradaAsignada->reserva->evento_id);

        $this->actingAs($personal)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => $entradaAjena->codigo,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('entradas', [
            'id' => $entradaAjena->id,
            'estado' => 'emitida',
        ]);

        $this->actingAs($personal)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => $entradaAsignada->codigo,
            ])
            ->assertOk()
            ->assertViewHas('permitido', true);

        $this->assertDatabaseHas('entradas', [
            'id' => $entradaAsignada->id,
            'estado' => 'usada',
            'validada_por' => $personal->id,
        ]);
    }

    public function test_el_administrador_puede_supervisar_la_validacion_de_cualquier_evento(): void
    {
        [, $entrada] = $this->crearEntrada();
        $administrador = User::factory()->create();
        $administrador->forceFill(['rol' => 'administrador'])->save();

        $this->actingAs($administrador)
            ->post(route('entradas.validar.procesar'), [
                'codigo' => $entrada->codigo,
            ])
            ->assertOk()
            ->assertViewHas('permitido', true);
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

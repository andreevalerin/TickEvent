<?php

namespace Tests\Feature;

use App\Models\DetalleReserva;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservaEntradasTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_crea_una_reserva_activa_por_quince_minutos(): void
    {
        Carbon::setTestNow('2026-11-12 12:00:00');

        [$evento, $zona, $tarifa] = $this->crearEvento(5000, 20);
        $cliente = User::factory()->create();

        $respuesta = $this->actingAs($cliente)->post(
            route('reservas.guardar', $evento),
            [
                'tarifa_id' => $tarifa->id,
                'cantidad' => 2,
                'dni' => ['71234567', '72345678'],
            ]
        );

        $reserva = Reserva::query()->sole();

        $respuesta->assertRedirect(route('reservas.mostrar', $reserva));
        $this->assertSame('activa', $reserva->estado);
        $this->assertTrue($reserva->expira_en->equalTo(now()->addMinutes(15)));
        $this->assertDatabaseHas('detalle_reserva', [
            'reserva_id' => $reserva->id,
            'tarifa_id' => $tarifa->id,
            'cantidad' => 2,
            'precio_unitario' => 100,
            'cargo_servicio_unitario' => 5,
        ]);
        $this->assertDatabaseCount('reserva_asistentes', 2);
    }

    public function test_todas_las_tarifas_comparten_la_capacidad_de_su_zona(): void
    {
        Carbon::setTestNow('2026-11-12 12:00:00');

        [$evento, $zona, $tarifaRegular] = $this->crearEvento(5000, 2);
        $tarifaPromocional = Tarifa::create([
            'zona_id' => $zona->id,
            'nombre' => 'Promocional',
            'precio' => 80,
            'activo' => true,
        ]);

        $primerCliente = User::factory()->create();
        $segundoCliente = User::factory()->create();

        $this->actingAs($primerCliente)->post(
            route('reservas.guardar', $evento),
            [
                'tarifa_id' => $tarifaRegular->id,
                'cantidad' => 2,
                'dni' => ['71234567', '72345678'],
            ]
        )->assertSessionDoesntHaveErrors();

        $this->actingAs($segundoCliente)->post(
            route('reservas.guardar', $evento),
            [
                'tarifa_id' => $tarifaPromocional->id,
                'cantidad' => 1,
                'dni' => ['73456789'],
            ]
        )->assertSessionHasErrors('cantidad');

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_una_reserva_vencida_libera_la_disponibilidad_de_la_zona(): void
    {
        Carbon::setTestNow('2026-11-12 12:00:00');

        [$evento, $zona, $tarifa] = $this->crearEvento(5000, 1);
        $primerCliente = User::factory()->create();
        $segundoCliente = User::factory()->create();

        $this->actingAs($primerCliente)->post(
            route('reservas.guardar', $evento),
            [
                'tarifa_id' => $tarifa->id,
                'cantidad' => 1,
                'dni' => ['71234567'],
            ]
        )->assertSessionDoesntHaveErrors();

        Carbon::setTestNow('2026-11-12 12:16:00');

        $this->actingAs($segundoCliente)->post(
            route('reservas.guardar', $evento),
            [
                'tarifa_id' => $tarifa->id,
                'cantidad' => 1,
                'dni' => ['72345678'],
            ]
        )->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('reservas', 2);
    }

    public function test_el_limite_de_seis_incluye_las_compras_confirmadas(): void
    {
        Carbon::setTestNow('2026-11-12 12:00:00');

        [$evento, $zona, $tarifa] = $this->crearEvento(5000, 20);
        $cliente = User::factory()->create();

        $confirmada = Reserva::create([
            'usuario_id' => $cliente->id,
            'evento_id' => $evento->id,
            'estado' => 'confirmada',
            'expira_en' => now()->subMinute(),
        ]);

        DetalleReserva::create([
            'reserva_id' => $confirmada->id,
            'tarifa_id' => $tarifa->id,
            'cantidad' => 4,
            'precio_unitario' => 100,
            'cargo_servicio_unitario' => 5,
        ]);

        $this->actingAs($cliente)->post(
            route('reservas.guardar', $evento),
            [
                'tarifa_id' => $tarifa->id,
                'cantidad' => 3,
                'dni' => ['71234567', '72345678', '73456789'],
            ]
        )->assertSessionHasErrors('cantidad');

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_otro_cliente_no_puede_ver_cancelar_ni_pagar_una_reserva_ajena(): void
    {
        [$evento, $zona, $tarifa] = $this->crearEvento(5000, 20);
        $propietario = User::factory()->create();
        $otroCliente = User::factory()->create();
        $reserva = Reserva::create([
            'usuario_id' => $propietario->id,
            'evento_id' => $evento->id,
            'estado' => 'activa',
            'expira_en' => now()->addMinutes(15),
        ]);

        $this->actingAs($otroCliente)
            ->get(route('reservas.mostrar', $reserva))
            ->assertForbidden();

        $this->actingAs($otroCliente)
            ->post(route('reservas.cancelar', $reserva))
            ->assertForbidden();

        $this->actingAs($otroCliente)
            ->get(route('reservas.pago', $reserva))
            ->assertForbidden();
    }

    private function crearEvento(int $aforo, int $capacidad): array
    {
        $organizador = User::factory()->create();
        $evento = Evento::create([
            'organizador_id' => $organizador->id,
            'titulo' => 'Evento de prueba',
            'slug' => 'evento-'.Str::lower(Str::random(12)),
            'categoria' => 'concierto',
            'descripcion' => 'Evento para pruebas de compra',
            'fecha_evento' => now()->addMonth(),
            'lugar' => 'Estadio de prueba',
            'ciudad' => 'Lima',
            'aforo_total' => $aforo,
            'estado' => 'publicado',
        ]);
        $zona = Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'Platinum',
            'capacidad' => $capacidad,
            'activo' => true,
        ]);
        $tarifa = Tarifa::create([
            'zona_id' => $zona->id,
            'nombre' => 'Regular',
            'precio' => 100,
            'activo' => true,
        ]);

        return [$evento, $zona, $tarifa];
    }
}

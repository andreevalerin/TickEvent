<?php

namespace Tests\Feature;

use App\Models\DetalleReserva;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservaMySqlTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Esta prueba transaccional requiere MySQL.');
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_reservas_mysql_respetan_plazo_disponibilidad_y_limite_acumulado(): void
    {
        Carbon::setTestNow('2026-11-13 12:00:00');
        [$evento, $tarifa] = $this->crearEvento(3);
        $primerCliente = User::factory()->create();
        $segundoCliente = User::factory()->create();

        $this->actingAs($primerCliente)
            ->post(route('reservas.guardar', $evento), [
                'tarifa_id' => $tarifa->id,
                'cantidad' => 2,
                'dni' => ['71234567', '72345678'],
            ])
            ->assertSessionDoesntHaveErrors();

        $primeraReserva = Reserva::where('usuario_id', $primerCliente->id)->sole();
        $this->assertSame('activa', $primeraReserva->estado);
        $this->assertTrue($primeraReserva->expira_en->equalTo(now()->addMinutes(15)));

        $this->actingAs($segundoCliente)
            ->post(route('reservas.guardar', $evento), [
                'tarifa_id' => $tarifa->id,
                'cantidad' => 2,
                'dni' => ['73456789', '74567890'],
            ])
            ->assertSessionHasErrors('cantidad');

        $this->actingAs($primerCliente)
            ->post(route('reservas.cancelar', $primeraReserva))
            ->assertRedirect();

        $this->actingAs($segundoCliente)
            ->post(route('reservas.guardar', $evento), [
                'tarifa_id' => $tarifa->id,
                'cantidad' => 2,
                'dni' => ['73456789', '74567890'],
            ])
            ->assertSessionDoesntHaveErrors();

        [$eventoLimite, $tarifaLimite] = $this->crearEvento(20);
        $clienteLimite = User::factory()->create();
        $confirmada = Reserva::create([
            'usuario_id' => $clienteLimite->id,
            'evento_id' => $eventoLimite->id,
            'estado' => 'confirmada',
            'expira_en' => now()->subMinute(),
        ]);
        DetalleReserva::create([
            'reserva_id' => $confirmada->id,
            'tarifa_id' => $tarifaLimite->id,
            'cantidad' => 4,
            'precio_unitario' => 100,
            'cargo_servicio_unitario' => 5,
        ]);

        $this->actingAs($clienteLimite)
            ->post(route('reservas.guardar', $eventoLimite), [
                'tarifa_id' => $tarifaLimite->id,
                'cantidad' => 3,
                'dni' => ['75678901', '76789012', '77890123'],
            ])
            ->assertSessionHasErrors('cantidad');
    }

    public function test_crud_de_zonas_funciona_en_mysql_y_restringe_eventos_ajenos(): void
    {
        [$evento, $tarifa] = $this->crearEvento(3);
        $organizador = $evento->organizador;
        $organizador->forceFill(['rol' => 'organizador'])->save();

        $this->actingAs($organizador)
            ->get(route('organizador.zonas.index'))
            ->assertOk()
            ->assertSee($evento->titulo)
            ->assertSee($tarifa->zona->nombre);

        $this->actingAs($organizador)
            ->post(route('organizador.zonas.store'), [
                'evento_id' => $evento->id,
                'nombre' => 'VIP transaccional',
                'capacidad' => 4,
                'activo' => 1,
                'tarifas' => [[
                    'nombre' => 'Regular transaccional',
                    'precio' => 75,
                    'activo' => 1,
                ]],
            ])
            ->assertRedirect(route('organizador.zonas.index'));

        $zona = Zona::where('evento_id', $evento->id)
            ->where('nombre', 'VIP transaccional')
            ->firstOrFail();
        $tarifaZona = $zona->tarifas()->where('nombre', 'Regular transaccional')->firstOrFail();

        $this->actingAs($organizador)
            ->put(route('organizador.zonas.update', $zona), [
                'nombre' => 'VIP actualizada',
                'capacidad' => 6,
                'activo' => 1,
                'tarifas' => [[
                    'id' => $tarifaZona->id,
                    'nombre' => 'Regular transaccional',
                    'precio' => 75,
                    'activo' => 1,
                ]],
            ])
            ->assertRedirect(route('organizador.zonas.index'));

        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'nombre' => 'VIP actualizada',
            'capacidad' => 6,
            'activo' => true,
        ]);

        $otroOrganizador = User::factory()->create();
        $otroOrganizador->forceFill(['rol' => 'organizador'])->save();

        $this->actingAs($otroOrganizador)
            ->put(route('organizador.zonas.update', $zona), [
                'nombre' => 'Intento ajeno',
                'capacidad' => 1,
                'activo' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($organizador)
            ->delete(route('organizador.zonas.destroy', $zona))
            ->assertRedirect(route('organizador.zonas.index'));

        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'activo' => false,
        ]);
    }

    private function crearEvento(int $capacidad): array
    {
        $organizador = User::factory()->create();
        $evento = Evento::create([
            'organizador_id' => $organizador->id,
            'titulo' => 'Evento MySQL transaccional',
            'slug' => 'evento-mysql-'.Str::lower(Str::random(12)),
            'categoria' => 'concierto',
            'descripcion' => 'Prueba transaccional del flujo de reservas',
            'fecha_evento' => now()->addMonth(),
            'lugar' => 'Estadio de prueba',
            'ciudad' => 'Lima',
            'aforo_total' => $capacidad,
            'estado' => 'publicado',
        ]);
        $zona = Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'Zona MySQL',
            'capacidad' => $capacidad,
            'activo' => true,
        ]);
        $tarifa = Tarifa::create([
            'zona_id' => $zona->id,
            'nombre' => 'Regular',
            'precio' => 100,
            'activo' => true,
        ]);

        return [$evento, $tarifa];
    }
}

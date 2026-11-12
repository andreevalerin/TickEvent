<?php

namespace Tests\Feature;

use App\Models\DetalleReserva;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PagoCulqiTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_intenta_cobrar_si_faltan_credenciales_de_pruebas(): void
    {
        [$cliente, $reserva] = $this->crearReserva();

        $this->actingAs($cliente)
            ->post(route('reservas.pago.procesar', $reserva), [
                'culqi_token' => 'tkn_test_12345678901234567',
            ])
            ->assertSessionHasErrors('pago');

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => 'activa',
        ]);
        $this->assertDatabaseCount('pagos', 0);
        Http::assertNothingSent();
    }

    public function test_confirma_la_reserva_solo_con_un_cargo_exitoso_verificado(): void
    {
        $this->configurarCulqi();
        [$cliente, $reserva] = $this->crearReserva();

        Http::fake([
            'https://api.culqi.com/v2/charges' => Http::response([
                'object' => 'charge',
                'id' => 'chr_test_12345678901234567',
                'current_amount' => 10500,
                'state' => 'Exitosa',
                'response_code' => 'venta_exitosa',
                'user_message' => 'Su compra ha sido exitosa.',
            ], 201),
        ]);

        $this->actingAs($cliente)
            ->post(route('reservas.pago.procesar', $reserva), [
                'culqi_token' => 'tkn_test_12345678901234567',
            ])
            ->assertRedirect(route('reservas.mostrar', $reserva));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => 'confirmada',
        ]);
        $this->assertDatabaseHas('pagos', [
            'reserva_id' => $reserva->id,
            'estado' => 'aprobado',
            'monto_centimos' => 10500,
            'proveedor_pago_id' => 'chr_test_12345678901234567',
            'codigo_respuesta' => 'venta_exitosa',
        ]);
        $this->assertDatabaseHas('entradas', [
            'reserva_id' => $reserva->id,
            'dni' => '71234567',
            'estado' => 'emitida',
        ]);
        Http::assertSent(function (Request $request) use ($reserva) {
            return $request->url() === 'https://api.culqi.com/v2/charges'
                && $request->hasHeader('Authorization', 'Bearer sk_test_valida')
                && $request['amount'] === 10500
                && $request['currency_code'] === 'PEN'
                && $request['capture'] === true
                && $request['metadata']['reserva_id'] === (string) $reserva->id;
        });
    }

    public function test_una_respuesta_no_verificable_no_confirma_la_reserva(): void
    {
        $this->configurarCulqi();
        [$cliente, $reserva] = $this->crearReserva();

        Http::fake([
            'https://api.culqi.com/v2/charges' => Http::response([
                'object' => 'charge',
                'id' => 'chr_test_12345678901234567',
                'current_amount' => 10400,
                'state' => 'Exitosa',
                'response_code' => 'venta_exitosa',
                'user_message' => 'Respuesta inconsistente.',
            ], 201),
        ]);

        $this->actingAs($cliente)
            ->post(route('reservas.pago.procesar', $reserva), [
                'culqi_token' => 'tkn_test_12345678901234567',
            ])
            ->assertSessionHasErrors('pago');

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => 'activa',
        ]);
        $this->assertDatabaseHas('pagos', [
            'reserva_id' => $reserva->id,
            'estado' => 'rechazado',
        ]);
    }

    private function configurarCulqi(): void
    {
        config()->set('services.culqi', [
            'public_key' => 'pk_test_valida',
            'secret_key' => 'sk_test_valida',
            'rsa_id' => 'rsa-pruebas',
            'rsa_public_key' => 'rsa-publica-pruebas',
            'base_url' => 'https://api.culqi.com/v2',
        ]);
    }

    private function crearReserva(): array
    {
        $organizador = User::factory()->create();
        $cliente = User::factory()->create();
        $evento = Evento::create([
            'organizador_id' => $organizador->id,
            'titulo' => 'Evento Culqi',
            'slug' => 'evento-culqi-'.Str::lower(Str::random(10)),
            'categoria' => 'concierto',
            'descripcion' => 'Evento de prueba para Culqi',
            'fecha_evento' => now()->addMonth(),
            'lugar' => 'Estadio de prueba',
            'ciudad' => 'Lima',
            'aforo_total' => 5000,
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
            'estado' => 'activa',
            'expira_en' => now()->addMinutes(15),
        ]);
        DetalleReserva::create([
            'reserva_id' => $reserva->id,
            'tarifa_id' => $tarifa->id,
            'cantidad' => 1,
            'precio_unitario' => 100,
            'cargo_servicio_unitario' => 5,
        ]);
        \Illuminate\Support\Facades\DB::table('reserva_asistentes')->insert([
            'reserva_id' => $reserva->id,
            'dni' => '71234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$cliente, $reserva];
    }
}

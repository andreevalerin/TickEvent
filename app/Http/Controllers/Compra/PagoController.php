<?php

namespace App\Http\Controllers\Compra;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Reserva;
use App\Services\EmisorEntradas;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PagoController extends Controller
{
    public function __construct(private EmisorEntradas $emisorEntradas)
    {
    }

    public function procesar(Request $request, Reserva $reserva): RedirectResponse
    {
        abort_unless(
            (int) $reserva->usuario_id === (int) $request->user()->id,
            403
        );

        if (!$this->culqiConfigurado()) {
            return back()->withErrors([
                'pago' => 'Culqi aún no está configurado con credenciales válidas de pruebas.',
            ]);
        }

        $datos = $request->validate([
            'culqi_token' => ['required', 'string', 'max:80', 'regex:/^tkn_test_[A-Za-z0-9]+$/'],
        ]);

        $preparacion = DB::transaction(function () use ($reserva, $datos) {
            $reservaBloqueada = Reserva::with('detalles')
                ->whereKey($reserva->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservaBloqueada->estado === 'confirmada') {
                $this->emisorEntradas->emitir($reservaBloqueada);

                return ['confirmada' => true];
            }

            if ($reservaBloqueada->estado === 'activa' && $reservaBloqueada->expira_en->isPast()) {
                $reservaBloqueada->update(['estado' => 'expirada']);
            }

            if ($reservaBloqueada->estado !== 'activa') {
                throw ValidationException::withMessages([
                    'pago' => 'La reserva ya no está disponible para pago.',
                ]);
            }

            $pago = Pago::where('reserva_id', $reservaBloqueada->id)
                ->lockForUpdate()
                ->first();

            if ($pago && in_array($pago->estado, ['procesando', 'pendiente_revision'], true)) {
                throw ValidationException::withMessages([
                    'pago' => 'Ya existe un pago en proceso para esta reserva.',
                ]);
            }

            if ($pago && $pago->estado === 'aprobado') {
                $reservaBloqueada->update(['estado' => 'confirmada']);
                $this->emisorEntradas->emitir($reservaBloqueada);

                return ['confirmada' => true];
            }

            $montoCentimos = (int) round($reservaBloqueada->detalles->sum(function ($detalle) {
                return $detalle->cantidad
                    * ($detalle->precio_unitario + $detalle->cargo_servicio_unitario);
            }) * 100);

            if ($pago) {
                $pago->fill([
                    'estado' => 'procesando',
                    'monto_centimos' => $montoCentimos,
                    'moneda' => 'PEN',
                    'proveedor_pago_id' => null,
                    'codigo_respuesta' => null,
                    'mensaje' => null,
                    'intentos' => $pago->intentos + 1,
                    'procesado_en' => null,
                ])->save();
            } else {
                $pago = Pago::create([
                    'reserva_id' => $reservaBloqueada->id,
                    'proveedor' => 'culqi',
                    'estado' => 'procesando',
                    'monto_centimos' => $montoCentimos,
                    'moneda' => 'PEN',
                    'intentos' => 1,
                ]);
            }

            $reservaBloqueada->update(['estado' => 'procesando_pago']);

            return [
                'confirmada' => false,
                'pago_id' => $pago->id,
                'monto_centimos' => $montoCentimos,
                'token' => $datos['culqi_token'],
                'reserva_id' => $reservaBloqueada->id,
                'evento' => $reservaBloqueada->evento()->value('titulo'),
            ];
        }, 3);

        if ($preparacion['confirmada']) {
            return redirect()
                ->route('reservas.mostrar', $reserva)
                ->with('estado', 'La compra ya estaba confirmada.');
        }

        try {
            $respuesta = Http::acceptJson()
                ->withToken(config('services.culqi.secret_key'))
                ->timeout(20)
                ->post(rtrim(config('services.culqi.base_url'), '/').'/charges', [
                    'amount' => $preparacion['monto_centimos'],
                    'currency_code' => 'PEN',
                    'email' => $request->user()->email,
                    'source_id' => $preparacion['token'],
                    'capture' => true,
                    'description' => Str::limit('Entradas TickEvent - '.$preparacion['evento'], 80, ''),
                    'metadata' => [
                        'reserva_id' => (string) $preparacion['reserva_id'],
                        'usuario_id' => (string) $request->user()->id,
                    ],
                ]);
        } catch (ConnectionException) {
            $this->registrarResultado(
                $preparacion['pago_id'],
                'pendiente_revision',
                null,
                'sin_respuesta',
                'No se pudo confirmar el resultado con Culqi.'
            );

            return redirect()
                ->route('reservas.mostrar', $reserva)
                ->withErrors([
                    'pago' => 'El resultado del pago no pudo verificarse. No intentes pagar nuevamente hasta revisarlo.',
                ]);
        }

        $respuestaPago = $respuesta->json();

        if ($this->esCargoAprobado($respuesta->status(), $respuestaPago, $preparacion['monto_centimos'])) {
            $this->registrarResultado(
                $preparacion['pago_id'],
                'aprobado',
                $respuestaPago['id'],
                $respuestaPago['response_code'],
                $respuestaPago['user_message'] ?? 'Pago confirmado por Culqi.'
            );

            return redirect()
                ->route('reservas.mostrar', $reserva)
                ->with('estado', 'Pago confirmado. Tu compra quedó registrada.');
        }

        if ($respuesta->status() === 200) {
            $this->registrarResultado(
                $preparacion['pago_id'],
                'requiere_3ds',
                $respuestaPago['id'] ?? null,
                $respuestaPago['response_code'] ?? 'requiere_3ds',
                'Culqi solicitó autenticación 3DS.'
            );

            return back()->withErrors([
                'pago' => 'Culqi solicitó autenticación 3DS. El cobro no fue confirmado.',
            ]);
        }

        $mensaje = $respuestaPago['user_message']
            ?? $respuestaPago['merchant_message']
            ?? $respuestaPago['message']
            ?? 'Culqi rechazó la operación.';

        $this->registrarResultado(
            $preparacion['pago_id'],
            'rechazado',
            $respuestaPago['charge_id'] ?? null,
            $respuestaPago['code'] ?? 'rechazado',
            $mensaje
        );

        return back()->withErrors(['pago' => $mensaje]);
    }

    private function culqiConfigurado(): bool
    {
        return str_starts_with((string) config('services.culqi.public_key'), 'pk_test_')
            && str_starts_with((string) config('services.culqi.secret_key'), 'sk_test_')
            && filled(config('services.culqi.rsa_id'))
            && filled(config('services.culqi.rsa_public_key'));
    }

    private function esCargoAprobado(int $estadoHttp, array $respuesta, int $montoCentimos): bool
    {
        return $estadoHttp === 201
            && ($respuesta['object'] ?? null) === 'charge'
            && ($respuesta['state'] ?? null) === 'Exitosa'
            && ($respuesta['response_code'] ?? null) === 'venta_exitosa'
            && (int) ($respuesta['current_amount'] ?? 0) === $montoCentimos
            && str_starts_with((string) ($respuesta['id'] ?? ''), 'chr_test_');
    }

    private function registrarResultado(
        int $pagoId,
        string $estado,
        ?string $proveedorPagoId,
        string $codigo,
        string $mensaje
    ): void {
        DB::transaction(function () use (
            $pagoId,
            $estado,
            $proveedorPagoId,
            $codigo,
            $mensaje
        ) {
            $pago = Pago::whereKey($pagoId)->lockForUpdate()->firstOrFail();
            $reserva = Reserva::whereKey($pago->reserva_id)->lockForUpdate()->firstOrFail();

            $pago->update([
                'estado' => $estado,
                'proveedor_pago_id' => $proveedorPagoId,
                'codigo_respuesta' => Str::limit($codigo, 80, ''),
                'mensaje' => Str::limit($mensaje, 500, ''),
                'procesado_en' => now(),
            ]);

            if ($estado === 'aprobado') {
                $reserva->update(['estado' => 'confirmada']);
                $this->emisorEntradas->emitir($reserva);

                return;
            }

            if ($estado === 'pendiente_revision') {
                return;
            }

            $reserva->update([
                'estado' => $reserva->expira_en->isFuture() ? 'activa' : 'expirada',
            ]);
        }, 3);
    }
}

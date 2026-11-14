<?php

namespace App\Http\Controllers\Compra;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    public function guardar(Request $request, Evento $evento)
    {
        $datos = $request->validate([
            'tarifa_id' => 'required|integer|exists:tarifas,id',
            'cantidad' => 'required|integer|between:1,6',
            'dni' => 'required|array|min:1|max:6',
            'dni.*' => 'required|digits:8|distinct'
        ]);

        $cantidad = (int) $datos['cantidad'];

        if (count($datos['dni']) !== $cantidad) {
            throw ValidationException::withMessages([
                'dni' => 'Debes ingresar un DNI por cada entrada.'
            ]);
        }

        $reserva = DB::transaction(function () use ($evento, $datos, $cantidad, $request) {
            $usuarioId = $request->user()->id;

            User::whereKey($usuarioId)
                ->lockForUpdate()
                ->firstOrFail();

            $evento = Evento::whereKey($evento->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($evento->estado !== 'publicado' || $evento->fecha_evento->isPast()) {
                throw ValidationException::withMessages([
                    'evento' => 'Este evento ya no está disponible.'
                ]);
            }

            Reserva::where('usuario_id', $usuarioId)
                ->where('estado', 'activa')
                ->where('expira_en', '<=', now())
                ->update(['estado' => 'expirada']);

            $otraReserva = Reserva::where('usuario_id', $usuarioId)
                ->where(function ($consulta) {
                    $consulta->where('estado', 'procesando_pago')
                        ->orWhere(function ($activas) {
                            $activas->where('estado', 'activa')
                                ->where('expira_en', '>', now());
                        });
                })
                ->first();

            if ($otraReserva) {
                return $otraReserva;
            }

            $tarifa = Tarifa::with('zona')
                ->whereKey($datos['tarifa_id'])
                ->where('activo', true)
                ->whereHas('zona', function ($consulta) use ($evento) {
                    $consulta->where('evento_id', $evento->id)
                        ->where('activo', true);
                })
                ->first();

            if (!$tarifa) {
                throw ValidationException::withMessages([
                    'tarifa_id' => 'Esta tarifa no está disponible.'
                ]);
            }

            $contarOcupadas = function ($consulta) {
                return $consulta->where(function ($filtro) {
                    $filtro->where('r.estado', 'confirmada')
                        ->orWhere('r.estado', 'procesando_pago')
                        ->orWhere(function ($activas) {
                            $activas->where('r.estado', 'activa')
                                ->where('r.expira_en', '>', now());
                        });
                })->sum('d.cantidad');
            };

            $compradasCliente = $contarOcupadas(
                DB::table('detalle_reserva as d')
                    ->join('reservas as r', 'r.id', '=', 'd.reserva_id')
                    ->where('r.usuario_id', $usuarioId)
                    ->where('r.evento_id', $evento->id)
            );

            if ($compradasCliente + $cantidad > 6) {
                throw ValidationException::withMessages([
                    'cantidad' => 'El límite es de 6 entradas acumuladas por evento.'
                ]);
            }

            $ocupadasZona = $contarOcupadas(
                DB::table('detalle_reserva as d')
                    ->join('reservas as r', 'r.id', '=', 'd.reserva_id')
                    ->join('tarifas as t', 't.id', '=', 'd.tarifa_id')
                    ->where('t.zona_id', $tarifa->zona_id)
            );

            if ($ocupadasZona + $cantidad > $tarifa->zona->capacidad) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No hay suficientes entradas en esta zona.'
                ]);
            }

            $ocupadasEvento = $contarOcupadas(
                DB::table('detalle_reserva as d')
                    ->join('reservas as r', 'r.id', '=', 'd.reserva_id')
                    ->where('r.evento_id', $evento->id)
            );

            if ($ocupadasEvento + $cantidad > $evento->aforo_total) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No hay suficientes entradas para este evento.'
                ]);
            }

            $reserva = Reserva::create([
                'usuario_id' => $usuarioId,
                'evento_id' => $evento->id,
                'estado' => 'activa',
                'expira_en' => now()->addMinutes(15)
            ]);

            $reserva->detalles()->create([
                'tarifa_id' => $tarifa->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $tarifa->precio,
                'cargo_servicio_unitario' => $evento->cargoServicio()
            ]);

            foreach ($datos['dni'] as $dni) {
                DB::table('reserva_asistentes')->insert([
                    'reserva_id' => $reserva->id,
                    'dni' => $dni,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return $reserva;
        }, 3);

        $redireccion = redirect()->route('reservas.mostrar', $reserva);

        if (!$reserva->wasRecentlyCreated) {
            $redireccion->with('estado', 'Te llevamos a tu reserva pendiente para que puedas finalizarla o cancelarla.');
        }

        return $redireccion;
    }

    public function mostrar(Request $request, Reserva $reserva)
    {
        abort_unless($reserva->usuario_id == $request->user()->id, 403);

        if ($reserva->estado === 'activa' && $reserva->expira_en->isPast()) {
            $reserva->update(['estado' => 'expirada']);
        }

        $reserva->load('evento', 'detalles.tarifa.zona', 'pago');

        $asistentes = DB::table('reserva_asistentes')
            ->where('reserva_id', $reserva->id)
            ->get();

        return view('publico.eventos.reserva', compact('reserva', 'asistentes'));
    }

    public function cancelar(Request $request, Reserva $reserva)
    {
        abort_unless($reserva->usuario_id == $request->user()->id, 403);

        if ($reserva->estado === 'activa') {
            $reserva->update(['estado' => 'cancelada']);
        }

        return redirect()->route('eventos.comprar', $reserva->evento);
    }

    public function pago(Request $request, Reserva $reserva)
    {
        abort_unless(
            (int) $reserva->usuario_id === (int) $request->user()->id,
            403
        );

        if ($reserva->estado === 'activa' && $reserva->expira_en->isPast()) {
            $reserva->update(['estado' => 'expirada']);
        }

        if ($reserva->estado !== 'activa') {
            return redirect()->route('reservas.mostrar', $reserva);
        }

        $reserva->load('evento', 'detalles.tarifa.zona');

        $subtotal = $reserva->detalles->sum(function ($detalle) {
            return $detalle->precio_unitario * $detalle->cantidad;
        });

        $servicio = $reserva->detalles->sum(function ($detalle) {
            return $detalle->cargo_servicio_unitario * $detalle->cantidad;
        });

        $total = $subtotal + $servicio;
        $totalCentimos = (int) round($total * 100);
        $culqiConfigurado = str_starts_with((string) config('services.culqi.public_key'), 'pk_test_')
            && str_starts_with((string) config('services.culqi.secret_key'), 'sk_test_')
            && filled(config('services.culqi.rsa_id'))
            && filled(config('services.culqi.rsa_public_key'));
        $culqiPublicKey = $culqiConfigurado
            ? config('services.culqi.public_key')
            : null;
        $culqiRsaId = $culqiConfigurado
            ? config('services.culqi.rsa_id')
            : null;
        $culqiRsaPublicKey = $culqiConfigurado
            ? config('services.culqi.rsa_public_key')
            : null;

        return view('publico.eventos.pago', compact(
            'reserva',
            'subtotal',
            'servicio',
            'total',
            'totalCentimos',
            'culqiConfigurado',
            'culqiPublicKey',
            'culqiRsaId',
            'culqiRsaPublicKey'
        ));
    }
}

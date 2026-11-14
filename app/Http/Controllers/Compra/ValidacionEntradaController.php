<?php

namespace App\Http\Controllers\Compra;

use App\Http\Controllers\Controller;
use App\Models\Entrada;
use App\Models\ValidacionEntrada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValidacionEntradaController extends Controller
{
    public function mostrar(Request $request)
    {
        abort_unless($this->puedeAbrirControl($request), 403);

        $eventosAsignados = $this->eventosAsignados($request);
        $eventoSeleccionado = $this->eventoSeleccionado($request, $eventosAsignados);

        return view('publico.entradas.validar', compact('eventosAsignados', 'eventoSeleccionado'));
    }

    public function validar(Request $request)
    {
        abort_unless($this->puedeAbrirControl($request), 403);

        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:80'],
            'evento_id' => ['required', 'integer'],
        ]);
        $eventosAsignados = $this->eventosAsignados($request);
        abort_if($eventosAsignados->isEmpty(), 403);
        $eventoSeleccionado = $this->eventoSeleccionado($request, $eventosAsignados);
        $codigo = strtoupper(trim($datos['codigo']));

        $resultado = DB::transaction(function () use ($request, $codigo, $eventoSeleccionado) {
            $entrada = Entrada::where('codigo', $codigo)
                ->lockForUpdate()
                ->first();

            if (!$entrada) {
                $this->registrarIntento($request, null, $codigo, 'rechazada', 'Código no encontrado');

                return [
                    'permitido' => false,
                    'motivo' => 'El código no corresponde a una entrada emitida por TickEvent.',
                    'entrada' => null,
                ];
            }

            $entrada->load('reserva.evento', 'tarifa.zona', 'validador');

            if (!$this->puedeValidarEvento($request, $entrada->reserva->evento)
                || $entrada->reserva->evento_id !== $eventoSeleccionado->id) {
                $this->registrarIntento($request, $entrada, $codigo, 'rechazada', 'Evento no autorizado');

                return ['sin_permiso' => true];
            }

            if ($entrada->estado === 'usada') {
                $this->registrarIntento($request, $entrada, $codigo, 'rechazada', 'Entrada ya utilizada');

                return [
                    'permitido' => false,
                    'motivo' => 'Esta entrada ya fue utilizada.',
                    'entrada' => $entrada,
                ];
            }

            if ($entrada->estado !== 'emitida' || $entrada->reserva->estado !== 'confirmada') {
                $this->registrarIntento($request, $entrada, $codigo, 'rechazada', 'Entrada no vigente');

                return [
                    'permitido' => false,
                    'motivo' => 'La entrada no está vigente.',
                    'entrada' => $entrada,
                ];
            }

            $entrada->update([
                'estado' => 'usada',
                'usada_en' => now(),
                'validada_por' => $request->user()->id,
            ]);
            $this->registrarIntento($request, $entrada, $codigo, 'aceptada', 'Acceso permitido');

            return [
                'permitido' => true,
                'motivo' => 'La entrada es válida y puede ingresar al evento.',
                'entrada' => $entrada->fresh(['reserva.evento', 'tarifa.zona', 'validador']),
            ];
        }, 3);

        if ($resultado['sin_permiso'] ?? false) {
            abort(403);
        }

        $resultado['eventosAsignados'] = $eventosAsignados;
        $resultado['eventoSeleccionado'] = $eventoSeleccionado;

        return view('publico.entradas.validar', $resultado);
    }

    private function puedeAbrirControl(Request $request): bool
    {
        $usuario = $request->user();

        return $usuario->rol === 'personal_acceso';
    }

    private function puedeValidarEvento(Request $request, $evento): bool
    {
        $usuario = $request->user();

        return $usuario->rol === 'personal_acceso'
            && $usuario->eventosAsignadosAcceso()->whereKey($evento->id)->exists();
    }

    private function registrarIntento(
        Request $request,
        ?Entrada $entrada,
        string $codigo,
        string $resultado,
        string $motivo
    ): void {
        ValidacionEntrada::create([
            'entrada_id' => $entrada?->id,
            'operador_id' => $request->user()->id,
            'codigo_hash' => hash('sha256', $codigo),
            'resultado' => $resultado,
            'motivo' => $motivo,
            'ip' => $request->ip(),
        ]);
    }

    private function eventosAsignados(Request $request)
    {
        return $request->user()->eventosAsignadosAcceso()
            ->where('estado', 'publicado')
            ->orderBy('fecha_evento')
            ->get();
    }

    private function eventoSeleccionado(Request $request, $eventosAsignados)
    {
        if (!$request->filled('evento_id')) {
            return $eventosAsignados->first();
        }

        $evento = $eventosAsignados->firstWhere('id', (int) $request->input('evento_id'));
        abort_unless($evento, 403);

        return $evento;
    }
}

<?php

namespace App\Http\Controllers\Organizador;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ZonaController extends Controller
{
    public function index(Request $request)
    {
        $zonas = Zona::with(['evento', 'tarifas' => fn ($consulta) => $consulta->orderBy('nombre')])
            ->withCount('tarifas')
            ->whereHas('evento', function ($consulta) use ($request) {
                $consulta->where('organizador_id', $request->user()->id);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('organizador.zonas.index', compact('zonas'));
    }

    public function create(Request $request)
    {
        $eventos = $this->eventosDisponibles($request);

        return view('organizador.zonas.form', [
            'zona' => new Zona(),
            'eventos' => $eventos,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validarZona($request);
        $evento = Evento::findOrFail($datos['evento_id']);
        $this->autorizarEvento($request, $evento);

        DB::transaction(function () use ($evento, $datos) {
            $eventoBloqueado = Evento::whereKey($evento->id)
                ->lockForUpdate()
                ->firstOrFail();
            $zona = $eventoBloqueado->zonas()->create([
                'nombre' => $datos['nombre'],
                'capacidad' => $datos['capacidad'],
                'activo' => (bool) ($datos['activo'] ?? false),
            ]);
            $this->sincronizarTarifas($zona, $datos['tarifas']);
            $this->actualizarAforo($eventoBloqueado);
        }, 3);

        return redirect()
            ->route('organizador.zonas.index')
            ->with('estado', 'Zona registrada correctamente.');
    }

    public function edit(Request $request, Zona $zona)
    {
        $this->autorizarZona($request, $zona);
        $zona->load(['tarifas' => fn ($consulta) => $consulta->orderBy('id')]);

        return view('organizador.zonas.form', [
            'zona' => $zona,
            'eventos' => collect([$zona->evento]),
        ]);
    }

    public function update(Request $request, Zona $zona)
    {
        $this->autorizarZona($request, $zona);
        $datos = $this->validarZona($request, $zona);

        DB::transaction(function () use ($zona, $datos) {
            $zonaBloqueada = Zona::whereKey($zona->id)
                ->lockForUpdate()
                ->firstOrFail();
            $zonaBloqueada->update([
                'nombre' => $datos['nombre'],
                'capacidad' => $datos['capacidad'],
                'activo' => (bool) ($datos['activo'] ?? false),
            ]);
            $this->sincronizarTarifas($zonaBloqueada, $datos['tarifas']);
            $this->actualizarAforo($zonaBloqueada->evento);
        }, 3);

        return redirect()
            ->route('organizador.zonas.index')
            ->with('estado', 'Zona actualizada correctamente.');
    }

    public function destroy(Request $request, Zona $zona)
    {
        $this->autorizarZona($request, $zona);

        DB::transaction(function () use ($zona) {
            $zonaBloqueada = Zona::whereKey($zona->id)
                ->lockForUpdate()
                ->firstOrFail();
            $zonaBloqueada->update(['activo' => false]);
            $zonaBloqueada->tarifas()->update(['activo' => false]);
            $this->actualizarAforo($zonaBloqueada->evento);
        }, 3);

        return redirect()
            ->route('organizador.zonas.index')
            ->with('estado', 'Zona desactivada correctamente.');
    }

    private function validarZona(Request $request, ?Zona $zona = null): array
    {
        $eventoId = $zona?->evento_id ?? $request->input('evento_id');

        $datos = $request->validate([
            'evento_id' => [$zona ? 'nullable' : 'required', 'integer', 'exists:eventos,id'],
            'nombre' => [
                'required',
                'string',
                'max:60',
                Rule::unique('zonas', 'nombre')
                    ->where('evento_id', $eventoId)
                    ->ignore($zona?->id),
            ],
            'capacidad' => ['required', 'integer', 'min:1', 'max:1000000'],
            'activo' => ['nullable', 'boolean'],
            'tarifas' => ['required', 'array', 'min:1', 'max:20'],
            'tarifas.*.id' => ['nullable', 'integer'],
            'tarifas.*.nombre' => ['required', 'string', 'max:60'],
            'tarifas.*.precio' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'tarifas.*.activo' => ['nullable', 'boolean'],
        ]);

        $nombres = collect($datos['tarifas'])
            ->pluck('nombre')
            ->map(fn ($nombre) => mb_strtolower(trim($nombre)));

        if ($nombres->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'tarifas' => 'No repitas el nombre de una tarifa dentro de la misma zona.',
            ]);
        }

        if ($zona) {
            foreach ($datos['tarifas'] as $indice => $tarifa) {
                $duplicada = $zona->tarifas()
                    ->whereRaw('LOWER(nombre) = ?', [mb_strtolower(trim($tarifa['nombre']))])
                    ->when(
                        !empty($tarifa['id']),
                        fn ($consulta) => $consulta->where('id', '!=', $tarifa['id'])
                    )
                    ->exists();

                if ($duplicada) {
                    throw ValidationException::withMessages([
                        "tarifas.$indice.nombre" => 'Ya existe una tarifa con ese nombre en la zona.',
                    ]);
                }
            }
        }

        if ((bool) ($datos['activo'] ?? false)) {
            $tieneTarifaActiva = collect($datos['tarifas'])
                ->contains(fn ($tarifa) => (bool) ($tarifa['activo'] ?? false));

            if (!$tieneTarifaActiva) {
                throw ValidationException::withMessages([
                    'tarifas' => 'Una zona activa debe tener al menos una tarifa activa.',
                ]);
            }
        }

        return $datos;
    }

    private function eventosDisponibles(Request $request)
    {
        return Evento::where('organizador_id', $request->user()->id)
            ->where('estado', '!=', 'retirado')
            ->orderBy('titulo')
            ->get();
    }

    private function autorizarZona(Request $request, Zona $zona): void
    {
        $zona->loadMissing('evento');
        $this->autorizarEvento($request, $zona->evento);
    }

    private function autorizarEvento(Request $request, Evento $evento): void
    {
        abort_unless((int) $evento->organizador_id === (int) $request->user()->id, 403);
        abort_if($evento->estado === 'retirado', 403);
    }

    private function actualizarAforo(Evento $evento): void
    {
        $evento->update([
            'aforo_total' => $evento->zonas()->where('activo', true)->sum('capacidad'),
        ]);
    }

    private function sincronizarTarifas(Zona $zona, array $tarifas): void
    {
        foreach ($tarifas as $datosTarifa) {
            if (!empty($datosTarifa['id'])) {
                $tarifa = $zona->tarifas()
                    ->whereKey($datosTarifa['id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $tarifa->update([
                    'nombre' => trim($datosTarifa['nombre']),
                    'precio' => $datosTarifa['precio'],
                    'activo' => (bool) ($datosTarifa['activo'] ?? false),
                ]);

                continue;
            }

            $zona->tarifas()->create([
                'nombre' => trim($datosTarifa['nombre']),
                'precio' => $datosTarifa['precio'],
                'activo' => (bool) ($datosTarifa['activo'] ?? true),
            ]);
        }
    }
}

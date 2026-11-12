<?php

namespace App\Http\Controllers\Organizador;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventoController extends Controller
{
    public function index(Request $request)
    {
        $eventos = Evento::where('organizador_id', $request->user()->id)
            ->withCount('zonas')
            ->orderByDesc('fecha_evento')
            ->get();

        return view('organizador.eventos.index', compact('eventos'));
    }

    public function create()
    {
        return view('organizador.eventos.form', [
            'evento' => new Evento(),
            'zonasFormulario' => [],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validarDatos($request);
        $imagenes = $this->guardarImagenes($request);

        $evento = DB::transaction(function () use ($request, $datos, $imagenes) {
            $evento = Evento::create([
                'organizador_id' => $request->user()->id,
                'titulo' => $datos['titulo'],
                'slug' => $this->generarSlug($datos['titulo']),
                'categoria' => $datos['categoria'],
                'descripcion' => $datos['descripcion'] ?? null,
                'fecha_evento' => $datos['fecha_evento'],
                'lugar' => $datos['lugar'],
                'direccion' => $datos['direccion'] ?? null,
                'ciudad' => $datos['ciudad'],
                'aforo_total' => 1,
                'imagen_principal' => $imagenes['imagen_principal'] ?? null,
                'imagen_plano' => $imagenes['imagen_plano'] ?? null,
                'estado' => $datos['estado'],
            ]);

            $this->sincronizarZonas($evento, $datos['zonas']);

            return $evento;
        }, 3);

        return redirect()
            ->route('organizador.eventos.index')
            ->with('estado', 'Evento creado correctamente.');
    }

    public function edit(Request $request, Evento $evento)
    {
        $this->autorizarPropietario($request, $evento);
        $evento->load(['zonas' => function ($consulta) {
            $consulta->with('tarifas')->orderBy('id');
        }]);
        $zonasFormulario = $evento->zonas->map(function ($zona) {
            return [
                'id' => $zona->id,
                'nombre' => $zona->nombre,
                'capacidad' => $zona->capacidad,
                'activo' => $zona->activo,
                'tarifas' => $zona->tarifas->map(function ($tarifa) {
                    return [
                        'id' => $tarifa->id,
                        'nombre' => $tarifa->nombre,
                        'precio' => $tarifa->precio,
                        'activo' => $tarifa->activo,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return view('organizador.eventos.form', compact('evento', 'zonasFormulario'));
    }

    public function update(Request $request, Evento $evento)
    {
        $this->autorizarPropietario($request, $evento);
        $datos = $this->validarDatos($request);
        $imagenes = $this->guardarImagenes($request);

        DB::transaction(function () use ($evento, $datos, $imagenes) {
            $eventoBloqueado = Evento::whereKey($evento->id)
                ->lockForUpdate()
                ->firstOrFail();
            $eventoBloqueado->update([
                'titulo' => $datos['titulo'],
                'categoria' => $datos['categoria'],
                'descripcion' => $datos['descripcion'] ?? null,
                'fecha_evento' => $datos['fecha_evento'],
                'lugar' => $datos['lugar'],
                'direccion' => $datos['direccion'] ?? null,
                'ciudad' => $datos['ciudad'],
                'imagen_principal' => $imagenes['imagen_principal']
                    ?? $eventoBloqueado->imagen_principal,
                'imagen_plano' => $imagenes['imagen_plano']
                    ?? $eventoBloqueado->imagen_plano,
                'estado' => $datos['estado'],
            ]);

            $this->sincronizarZonas($eventoBloqueado, $datos['zonas']);
        }, 3);

        return redirect()
            ->route('organizador.eventos.index')
            ->with('estado', 'Evento actualizado correctamente.');
    }

    public function destroy(Request $request, Evento $evento)
    {
        $this->autorizarPropietario($request, $evento);

        DB::transaction(function () use ($evento) {
            $eventoBloqueado = Evento::whereKey($evento->id)
                ->lockForUpdate()
                ->firstOrFail();
            $eventoBloqueado->update(['estado' => 'retirado']);
            $zonaIds = $eventoBloqueado->zonas()->pluck('id');
            Zona::whereIn('id', $zonaIds)->update(['activo' => false]);
            Tarifa::whereIn('zona_id', $zonaIds)->update(['activo' => false]);
        }, 3);

        return redirect()
            ->route('organizador.eventos.index')
            ->with('estado', 'Evento retirado de la venta.');
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'in:concierto,partido'],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'fecha_evento' => ['required', 'date'],
            'lugar' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:180'],
            'ciudad' => ['required', 'string', 'max:80'],
            'estado' => ['required', 'in:borrador,publicado'],
            'imagen_principal' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'imagen_plano' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'zonas' => ['required', 'array', 'min:1', 'max:30'],
            'zonas.*.id' => ['nullable', 'integer'],
            'zonas.*.nombre' => ['required', 'string', 'max:60'],
            'zonas.*.capacidad' => ['required', 'integer', 'min:1', 'max:1000000'],
            'zonas.*.activo' => ['nullable', 'boolean'],
            'zonas.*.tarifas' => ['required', 'array', 'min:1', 'max:20'],
            'zonas.*.tarifas.*.id' => ['nullable', 'integer'],
            'zonas.*.tarifas.*.nombre' => ['required', 'string', 'max:60'],
            'zonas.*.tarifas.*.precio' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'zonas.*.tarifas.*.activo' => ['nullable', 'boolean'],
        ]);
    }

    private function guardarImagenes(Request $request): array
    {
        $imagenes = [];

        foreach (['imagen_principal', 'imagen_plano'] as $campo) {
            if ($request->hasFile($campo)) {
                $imagenes[$campo] = 'storage/'.$request->file($campo)->store('eventos', 'public');
            }
        }

        return $imagenes;
    }

    private function sincronizarZonas(Evento $evento, array $zonas): void
    {
        $zonaIds = [];
        $aforo = 0;

        foreach ($zonas as $datosZona) {
            if (!empty($datosZona['id'])) {
                $zona = $evento->zonas()
                    ->whereKey($datosZona['id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $zona->update([
                    'nombre' => $datosZona['nombre'],
                    'capacidad' => $datosZona['capacidad'],
                    'activo' => (bool) ($datosZona['activo'] ?? false),
                ]);
            } else {
                $zona = $evento->zonas()->create([
                    'nombre' => $datosZona['nombre'],
                    'capacidad' => $datosZona['capacidad'],
                    'activo' => (bool) ($datosZona['activo'] ?? true),
                ]);
            }

            $zonaIds[] = $zona->id;
            $aforo += (int) $datosZona['capacidad'];
            $tarifaIds = [];

            foreach ($datosZona['tarifas'] as $datosTarifa) {
                if (!empty($datosTarifa['id'])) {
                    $tarifa = $zona->tarifas()
                        ->whereKey($datosTarifa['id'])
                        ->lockForUpdate()
                        ->firstOrFail();
                    $tarifa->update([
                        'nombre' => $datosTarifa['nombre'],
                        'precio' => $datosTarifa['precio'],
                        'activo' => (bool) ($datosTarifa['activo'] ?? false),
                    ]);
                } else {
                    $tarifa = $zona->tarifas()->create([
                        'nombre' => $datosTarifa['nombre'],
                        'precio' => $datosTarifa['precio'],
                        'activo' => (bool) ($datosTarifa['activo'] ?? true),
                    ]);
                }

                $tarifaIds[] = $tarifa->id;
            }

            $zona->tarifas()->whereNotIn('id', $tarifaIds)->update(['activo' => false]);
        }

        $evento->zonas()->whereNotIn('id', $zonaIds)->update(['activo' => false]);
        $evento->update(['aforo_total' => $aforo]);
    }

    private function generarSlug(string $titulo): string
    {
        $base = Str::slug($titulo) ?: 'evento';
        $slug = $base;
        $numero = 2;

        while (Evento::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$numero;
            $numero++;
        }

        return $slug;
    }

    private function autorizarPropietario(Request $request, Evento $evento): void
    {
        abort_unless(
            (int) $evento->organizador_id === (int) $request->user()->id,
            403
        );
    }
}

<?php

namespace App\Http\Controllers\Organizador;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class EventoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'],
            'vista' => ['nullable', 'string', 'in:todos,proximos,pasados,borradores,retirados'],
            'orden' => ['nullable', 'string', 'in:recientes,proximos,titulo'],
        ], ['in' => 'Selecciona un filtro válido.', 'string' => 'El filtro debe ser texto.', 'max' => 'La búsqueda admite hasta 120 caracteres.']);
        $busqueda = trim($filtros['buscar'] ?? '');
        $vista = $filtros['vista'] ?? 'todos';
        $orden = $filtros['orden'] ?? 'recientes';
        $consulta = Evento::where('organizador_id', $request->user()->id);
        $resumen = (clone $consulta)->selectRaw('estado, COUNT(*) as cantidad, SUM(aforo_total) as aforo')->groupBy('estado')->get()->keyBy('estado');
        $metricas = [
            'total' => $resumen->sum('cantidad'),
            'publicados' => (int) ($resumen->get('publicado')?->cantidad ?? 0),
            'borradores' => (int) ($resumen->get('borrador')?->cantidad ?? 0),
            'aforo' => (int) ($resumen->get('publicado')?->aforo ?? 0),
        ];
        $consulta->when($busqueda !== '', function (Builder $consulta) use ($busqueda): void {
            $consulta->where(function (Builder $texto) use ($busqueda): void {
                $texto->where('titulo', 'like', '%'.$busqueda.'%')->orWhere('lugar', 'like', '%'.$busqueda.'%');
            });
        });
        match ($vista) {
            'proximos' => $consulta->where('estado', 'publicado')->where('fecha_evento', '>=', now()),
            'pasados' => $consulta->where('fecha_evento', '<', now()),
            'borradores' => $consulta->where('estado', 'borrador'),
            'retirados' => $consulta->where('estado', 'retirado'),
            default => null,
        };
        match ($orden) {
            'proximos' => $consulta->orderBy('fecha_evento'),
            'titulo' => $consulta->orderBy('titulo'),
            default => $consulta->orderByDesc('fecha_evento'),
        };
        $eventos = $consulta->withCount('zonas')->orderBy('id')->paginate(12)->withQueryString();

        return view('organizador.eventos.index', compact('eventos', 'metricas', 'busqueda', 'vista', 'orden'));
    }

    public function create(): View
    {
        return view('organizador.eventos.form', [
            'evento' => new Evento(),
            'zonasFormulario' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarDatos($request);
        $this->guardarEvento($request, $datos);

        return redirect()
            ->route('organizador.eventos.index')
            ->with('estado', 'Evento creado correctamente.');
    }

    public function edit(Request $request, Evento $evento): View
    {
        $this->autorizarPropietario($request, $evento);
        $evento->load(['zonas' => function (HasMany $consulta): void {
            $consulta->with('tarifas')->orderBy('id');
        }]);
        $zonasFormulario = $evento->zonas->map(function (Zona $zona): array {
            return [
                'id' => $zona->id,
                'nombre' => $zona->nombre,
                'capacidad' => $zona->capacidad,
                'activo' => $zona->activo,
                'tarifas' => $zona->tarifas->map(function (Tarifa $tarifa): array {
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

    public function update(Request $request, Evento $evento): RedirectResponse
    {
        $this->autorizarPropietario($request, $evento);
        $datos = $this->validarDatos($request, $evento);
        $this->guardarEvento($request, $datos, $evento);

        return redirect()
            ->route('organizador.eventos.index')
            ->with('estado', 'Evento actualizado correctamente.');
    }

    public function destroy(Request $request, Evento $evento): RedirectResponse
    {
        $this->autorizarPropietario($request, $evento);

        DB::transaction(function () use ($evento): void {
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

    private function validarDatos(Request $request, ?Evento $evento = null): array
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'in:concierto,partido'],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'fecha_evento' => ['required', 'date'],
            'lugar' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:180'],
            'ciudad' => ['required', 'string', 'max:80'],
            'estado' => ['required', $evento?->estado === 'retirado' ? 'in:borrador,publicado,retirado' : 'in:borrador,publicado'],
            'imagen_principal' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'imagen_plano' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'zonas' => ['required', 'array', 'min:1', 'max:30'],
            'zonas.*' => ['array'],
            'zonas.*.id' => ['nullable', 'integer', 'distinct'],
            'zonas.*.nombre' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'zonas.*.capacidad' => ['required', 'integer', 'min:1', 'max:1000000'],
            'zonas.*.activo' => ['nullable', 'boolean'],
            'zonas.*.tarifas' => ['required', 'array', 'min:1', 'max:20'],
            'zonas.*.tarifas.*' => ['array'],
            'zonas.*.tarifas.*.id' => ['nullable', 'integer', 'distinct'],
            'zonas.*.tarifas.*.nombre' => ['required', 'string', 'max:60'],
            'zonas.*.tarifas.*.precio' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'zonas.*.tarifas.*.activo' => ['nullable', 'boolean'],
        ], [
            'required' => 'Completa el campo :attribute.',
            'zonas.*.nombre.distinct' => 'Los nombres de las zonas no pueden repetirse.',
            'distinct' => 'No puedes enviar el mismo registro más de una vez.',
            'zonas.*.capacidad.min' => 'La capacidad debe ser de al menos una entrada.',
            'zonas.*.tarifas.*.precio.min' => 'El precio debe ser mayor que cero.',
            'image' => 'Selecciona una imagen válida.',
            'mimes' => 'Usa una imagen JPG, PNG o WebP.',
            'imagen_principal.max' => 'La portada no puede superar los 5 MB.',
            'imagen_plano.max' => 'El plano no puede superar los 5 MB.',
        ]);
        foreach ($datos['zonas'] as $indice => $zona) {
            $nombres = array_map(fn (array $tarifa): string => Str::lower($tarifa['nombre']), $zona['tarifas']);
            if (count($nombres) !== count(array_unique($nombres))) {
                throw ValidationException::withMessages(["zonas.$indice.tarifas" => 'Los nombres de las tarifas no pueden repetirse dentro de una zona.']);
            }
        }

        return $datos;
    }

    private function guardarEvento(Request $request, array $datos, ?Evento $evento = null): void
    {
        $imagenes = [];
        try {
            DB::transaction(function () use ($request, $datos, $evento, &$imagenes): void {
                $registro = $evento ? Evento::whereKey($evento->id)->lockForUpdate()->firstOrFail() : new Evento();
                $this->validarZonas($registro, $datos['zonas']);
                $registro->fill(collect($datos)->only(['titulo', 'categoria', 'descripcion', 'fecha_evento', 'lugar', 'direccion', 'ciudad', 'estado'])->all());
                if (! $registro->exists) {
                    $registro->organizador_id = $request->user()->id;
                    $registro->slug = $this->generarSlug($datos['titulo']);
                    $registro->aforo_total = 1;
                }
                $registro->save();
                $this->sincronizarZonas($registro, $datos['zonas']);
                $this->guardarImagenes($request, $imagenes);
                $registro->update($imagenes);
            });
        } catch (Throwable $error) {
            Storage::disk('public')->delete(array_map(fn (string $ruta): string => Str::after($ruta, 'storage/'), $imagenes));
            throw $error;
        }
    }

    private function validarZonas(Evento $evento, array $zonas): void
    {
        $existentes = $evento->exists ? $evento->zonas()->with('tarifas')->get()->keyBy('id') : collect();
        $ocupadas = DB::table('detalle_reserva as d')->join('reservas as r', 'r.id', '=', 'd.reserva_id')
            ->join('tarifas as t', 't.id', '=', 'd.tarifa_id')->where('r.evento_id', $evento->id)
            ->where(function (QueryBuilder $consulta): void {
                $consulta->whereIn('r.estado', ['confirmada', 'procesando_pago'])->orWhere(function (QueryBuilder $activas): void {
                    $activas->where('r.estado', 'activa')->where('r.expira_en', '>', now());
                });
            })->groupBy('t.zona_id')->selectRaw('t.zona_id, SUM(d.cantidad) as cantidad')->pluck('cantidad', 't.zona_id');
        foreach ($zonas as $indice => $datosZona) {
            $zona = $existentes->get($datosZona['id'] ?? null);
            abort_if(! empty($datosZona['id']) && ! $zona, 404);
            foreach ($existentes as $otraZona) {
                if ($otraZona->id !== $zona?->id && Str::lower($otraZona->nombre) === Str::lower($datosZona['nombre'])) {
                    throw ValidationException::withMessages(["zonas.$indice.nombre" => 'Ya existe una zona con ese nombre. Edita la zona existente o elige otro nombre.']);
                }
            }
            if ($zona && $datosZona['capacidad'] < (int) $ocupadas->get($zona->id, 0)) {
                throw ValidationException::withMessages(["zonas.$indice.capacidad" => 'La capacidad no puede ser menor que las entradas vendidas o reservadas.']);
            }
            foreach ($datosZona['tarifas'] as $indiceTarifa => $datosTarifa) {
                $tarifa = $zona?->tarifas->firstWhere('id', $datosTarifa['id'] ?? null);
                abort_if(! empty($datosTarifa['id']) && ! $tarifa, 404);
                foreach ($zona?->tarifas ?? [] as $otraTarifa) {
                    if ($otraTarifa->id !== $tarifa?->id && Str::lower($otraTarifa->nombre) === Str::lower($datosTarifa['nombre'])) {
                        throw ValidationException::withMessages(["zonas.$indice.tarifas.$indiceTarifa.nombre" => 'Ya existe una tarifa con ese nombre en esta zona.']);
                    }
                }
            }
        }
        foreach ($ocupadas as $zonaId => $cantidad) {
            if ($cantidad > 0 && ! in_array($zonaId, array_column($zonas, 'id'))) {
                throw ValidationException::withMessages(['zonas' => 'No puedes quitar una zona con entradas vendidas o reservadas. Puedes desactivarla para detener nuevas ventas.']);
            }
        }
    }

    private function guardarImagenes(Request $request, array &$imagenes): void
    {
        foreach (['imagen_principal', 'imagen_plano'] as $campo) {
            if ($request->hasFile($campo)) {
                $ruta = $request->file($campo)->store('eventos', 'public');
                if (! $ruta) {
                    throw ValidationException::withMessages([$campo => 'No se pudo guardar la imagen. Vuelve a intentarlo.']);
                }
                $imagenes[$campo] = 'storage/'.$ruta;
            }
        }

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

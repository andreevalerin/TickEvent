<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventoController extends Controller
{
    public function index()
    {
        $eventos = Evento::with('organizador')
            ->orderByDesc('fecha_evento')
            ->get();

        return view('admin.eventos.index', compact('eventos'));
    }

    public function actualizarEstado(Request $request, Evento $evento)
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:borrador,publicado,retirado'],
        ]);

        DB::transaction(function () use ($evento, $datos) {
            $eventoBloqueado = Evento::whereKey($evento->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($eventoBloqueado->estado === 'retirado' && $datos['estado'] !== 'retirado') {
                throw ValidationException::withMessages([
                    'estado' => 'Un evento retirado por administración no puede reactivarse.',
                ]);
            }

            if ($datos['estado'] === 'publicado') {
                $zonasActivas = $eventoBloqueado->zonas()->where('activo', true);
                $tieneZonaVendible = (clone $zonasActivas)
                    ->whereHas('tarifas', fn ($consulta) => $consulta->where('activo', true))
                    ->exists();
                $tieneZonaSinTarifa = (clone $zonasActivas)
                    ->whereDoesntHave('tarifas', fn ($consulta) => $consulta->where('activo', true))
                    ->exists();

                if (!$tieneZonaVendible || $tieneZonaSinTarifa) {
                    throw ValidationException::withMessages([
                        'estado' => 'Para publicar, cada zona activa debe tener al menos una tarifa activa.',
                    ]);
                }
            }

            $eventoBloqueado->update(['estado' => $datos['estado']]);

            if ($datos['estado'] === 'retirado') {
                $zonaIds = $eventoBloqueado->zonas()->pluck('id');
                Zona::whereIn('id', $zonaIds)->update(['activo' => false]);
                Tarifa::whereIn('zona_id', $zonaIds)->update(['activo' => false]);
            }
        }, 3);

        return back()->with('estado', 'Estado del evento actualizado.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

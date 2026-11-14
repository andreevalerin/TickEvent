<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    public function index(Request $request)
    {
        $consulta = Evento::with('zonas.tarifas')
            ->where('estado', 'publicado');

        if ($request->filled('q')) {
            $busqueda = trim($request->string('q')->toString());
            $consulta->where(function ($filtro) use ($busqueda) {
                $filtro->where('titulo', 'like', '%'.$busqueda.'%')
                    ->orWhere('lugar', 'like', '%'.$busqueda.'%')
                    ->orWhere('ciudad', 'like', '%'.$busqueda.'%');
            });
        }

        if (in_array($request->input('categoria'), ['concierto', 'partido'], true)) {
            $consulta->where('categoria', $request->input('categoria'));
        }

        $eventos = $consulta->orderBy('fecha_evento')->get();

        return view('publico.eventos.index', [
            'eventos' => $eventos,
            'titulo' => $request->filled('q') ? 'Resultados de búsqueda' : 'Todos los eventos'
        ]);
    }

    public function conciertos()
    {
        $eventos = Evento::with('zonas.tarifas')
            ->where('estado', 'publicado')
            ->where('categoria', 'concierto')
            ->orderBy('fecha_evento')
            ->get();

        return view('publico.eventos.index', [
            'eventos' => $eventos,
            'titulo' => 'Conciertos'
        ]);
    }

    public function partidos()
    {
        $eventos = Evento::with('zonas.tarifas')
            ->where('estado', 'publicado')
            ->where('categoria', 'partido')
            ->orderBy('fecha_evento')
            ->get();

        return view('publico.eventos.index', [
            'eventos' => $eventos,
            'titulo' => 'Partidos'
        ]);
    }

    public function comprar(Evento $evento)
    {
    abort_unless($evento->estado === 'publicado', 404);

    $evento->load([
        'zonas' => fn ($consulta) => $consulta
            ->where('activo', true)
            ->whereHas('tarifas', fn ($tarifas) => $tarifas->where('activo', true)),
        'zonas.tarifas' => fn ($consulta) => $consulta->where('activo', true),
    ]);

    return view('publico.eventos.comprar', compact('evento'));
    }

    public function show(Evento $evento)
    {
        abort_unless($evento->estado === 'publicado', 404);

        $evento->load('zonas.tarifas');

        return view('publico.eventos.show', compact('evento'));
    }

}

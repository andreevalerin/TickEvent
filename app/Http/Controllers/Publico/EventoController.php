<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Evento;

class EventoController extends Controller
{
    public function index()
    {
        $eventos = Evento::where('estado', 'publicado')
            ->orderBy('fecha_evento')
            ->get();

        return view('publico.eventos.index', [
            'eventos' => $eventos,
            'titulo' => 'Todos los eventos'
        ]);
    }

    public function conciertos()
    {
        $eventos = Evento::where('estado', 'publicado')
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
        $eventos = Evento::where('estado', 'publicado')
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

    $evento->load('zonas.tarifas');

    return view('publico.eventos.comprar', compact('evento'));
    }

    public function show(Evento $evento)
    {
        abort_unless($evento->estado === 'publicado', 404);

        $evento->load('zonas.tarifas');

        return view('publico.eventos.show', compact('evento'));
    }

}

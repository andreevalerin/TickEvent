<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    public function index()
    {
        return view('publico.eventos.index');
    }

    public function conciertos()
    {
        return 'Catálogo de conciertos';
    }

    public function partidos()
    {
        return 'Catálogo de partidos';
    }

    public function show(string $evento)
    {
        return "Detalle del evento: {$evento}";
    }
}
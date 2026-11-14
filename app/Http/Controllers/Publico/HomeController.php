<?php


namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Evento;

class HomeController extends Controller
{
    public function index()
    {
        $eventos = Evento::with('zonas.tarifas')
            ->where('estado', 'publicado')
            ->orderBy('fecha_evento')
            ->get();

        return view('publico.home', compact('eventos'));
    }
}

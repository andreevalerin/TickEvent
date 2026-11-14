<?php


namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()?->rol === 'administrador') {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()?->rol === 'organizador') {
            return redirect()->route('organizador.eventos.index');
        }

        if ($request->user()?->rol === 'personal_acceso') {
            return redirect()->route('entradas.validar');
        }

        $eventos = Evento::with('zonas.tarifas')
            ->where('estado', 'publicado')
            ->orderBy('fecha_evento')
            ->get();

        return view('publico.home', compact('eventos'));
    }
}

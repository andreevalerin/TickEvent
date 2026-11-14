<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entrada;

class EntradaController extends Controller
{
    public function index()
    {
        $entradas = Entrada::with([
            'reserva.usuario',
            'reserva.evento',
            'tarifa.zona',
            'validador',
        ])->latest()->paginate(20);

        $metricas = [
            'total' => Entrada::count(),
            'emitidas' => Entrada::where('estado', 'emitida')->count(),
            'usadas' => Entrada::where('estado', 'usada')->count(),
        ];

        return view('admin.entradas.index', compact('entradas', 'metricas'));
    }
}

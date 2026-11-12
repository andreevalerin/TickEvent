<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entrada;
use App\Models\Evento;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $metricas = [
            'eventos_publicados' => Evento::where('estado', 'publicado')->count(),
            'reservas_confirmadas' => Reserva::where('estado', 'confirmada')->count(),
            'pagos_aprobados' => Pago::where('estado', 'aprobado')->count(),
            'entradas_emitidas' => Entrada::count(),
            'usuarios' => User::count(),
            'organizadores' => User::where('rol', 'organizador')->count(),
        ];
        $eventos = Evento::with('organizador')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('metricas', 'eventos'));
    }
}

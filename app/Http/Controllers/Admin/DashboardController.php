<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entrada;
use App\Models\Evento;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $metricas = [
            'eventos_publicados' => Evento::where('estado', 'publicado')->count(),
            'entradas_vendidas' => Entrada::query()
                ->join('reservas', 'reservas.id', '=', 'entradas.reserva_id')
                ->join('pagos', 'pagos.reserva_id', '=', 'reservas.id')
                ->where('pagos.estado', 'aprobado')
                ->count(),
            'ingresos_centimos' => (int) Pago::where('estado', 'aprobado')->sum('monto_centimos'),
            'organizadores' => User::where('rol', 'organizador')->count(),
        ];

        $ventas = Entrada::query()
            ->join('reservas', 'reservas.id', '=', 'entradas.reserva_id')
            ->join('pagos', 'pagos.reserva_id', '=', 'reservas.id')
            ->where('pagos.estado', 'aprobado')
            ->selectRaw('DATE(COALESCE(pagos.procesado_en, pagos.updated_at)) as fecha, COUNT(entradas.id) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->limit(14)
            ->get();

        $categorias = Entrada::query()
            ->join('reservas', 'reservas.id', '=', 'entradas.reserva_id')
            ->join('pagos', 'pagos.reserva_id', '=', 'reservas.id')
            ->join('eventos', 'eventos.id', '=', 'reservas.evento_id')
            ->where('pagos.estado', 'aprobado')
            ->select('eventos.categoria', DB::raw('COUNT(entradas.id) as total'))
            ->groupBy('eventos.categoria')
            ->orderByDesc('total')
            ->get();

        $eventos = Evento::with('organizador')
            ->where('estado', 'publicado')
            ->where('fecha_evento', '>=', now())
            ->orderBy('fecha_evento')
            ->limit(4)
            ->get();

        $vendidasPorEvento = Entrada::query()
            ->join('reservas', 'reservas.id', '=', 'entradas.reserva_id')
            ->join('pagos', 'pagos.reserva_id', '=', 'reservas.id')
            ->where('pagos.estado', 'aprobado')
            ->whereIn('reservas.evento_id', $eventos->pluck('id'))
            ->select('reservas.evento_id', DB::raw('COUNT(entradas.id) as total'))
            ->groupBy('reservas.evento_id')
            ->pluck('total', 'evento_id');

        return view('admin.dashboard', compact('metricas', 'ventas', 'categorias', 'eventos', 'vendidasPorEvento'));
    }
}

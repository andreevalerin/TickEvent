<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entrada;
use App\Models\Pago;

class VentaController extends Controller
{
    public function index()
    {
        $pagos = Pago::with(['reserva.usuario', 'reserva.evento', 'reserva.entradas'])
            ->where('estado', 'aprobado')
            ->latest('procesado_en')
            ->paginate(20);

        $totalVentas = Pago::where('estado', 'aprobado')->count();
        $ingresosCentimos = (int) Pago::where('estado', 'aprobado')->sum('monto_centimos');
        $entradasVendidas = Entrada::query()
            ->join('reservas', 'reservas.id', '=', 'entradas.reserva_id')
            ->join('pagos', 'pagos.reserva_id', '=', 'reservas.id')
            ->where('pagos.estado', 'aprobado')
            ->count();

        $metricas = [
            'ventas' => $totalVentas,
            'ingresos_centimos' => $ingresosCentimos,
            'entradas' => $entradasVendidas,
            'promedio_centimos' => $totalVentas > 0 ? intdiv($ingresosCentimos, $totalVentas) : 0,
        ];

        return view('admin.ventas.index', compact('pagos', 'metricas'));
    }
}

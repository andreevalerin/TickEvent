<?php

namespace App\Http\Controllers\Organizador;

use App\Http\Controllers\Controller;
use App\Models\Entrada;
use App\Models\Pago;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    public function ventas(Request $request)
    {
        $pagos = Pago::with(['reserva.usuario', 'reserva.evento', 'reserva.entradas'])
            ->where('estado', 'aprobado')
            ->whereHas('reserva.evento', function ($consulta) use ($request) {
                $consulta->where('organizador_id', $request->user()->id);
            })
            ->latest('procesado_en')
            ->paginate(20);

        $eventoIds = $request->user()->eventosOrganizados()->pluck('id');
        $ingresosCentimos = (int) Pago::query()
            ->join('reservas', 'reservas.id', '=', 'pagos.reserva_id')
            ->where('pagos.estado', 'aprobado')
            ->whereIn('reservas.evento_id', $eventoIds)
            ->sum('pagos.monto_centimos');
        $entradasVendidas = Entrada::query()
            ->join('reservas', 'reservas.id', '=', 'entradas.reserva_id')
            ->join('pagos', 'pagos.reserva_id', '=', 'reservas.id')
            ->where('pagos.estado', 'aprobado')
            ->whereIn('reservas.evento_id', $eventoIds)
            ->count();

        return view('organizador.ventas', compact('pagos', 'ingresosCentimos', 'entradasVendidas'));
    }

    public function perfil(Request $request)
    {
        $usuario = $request->user();
        $totales = [
            'eventos' => $usuario->eventosOrganizados()->count(),
            'publicados' => $usuario->eventosOrganizados()->where('estado', 'publicado')->count(),
        ];

        return view('organizador.perfil', compact('usuario', 'totales'));
    }
}

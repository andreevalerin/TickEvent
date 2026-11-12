<?php

namespace App\Http\Controllers\Compra;

use App\Http\Controllers\Controller;
use App\Models\Entrada;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    public function index(Request $request)
    {
        $entradas = Entrada::with('reserva.evento', 'tarifa.zona')
            ->whereHas('reserva', function ($consulta) use ($request) {
                $consulta->where('usuario_id', $request->user()->id);
            })
            ->latest()
            ->get();

        return view('publico.entradas.index', compact('entradas'));
    }

    public function show(Request $request, Entrada $entrada)
    {
        abort_unless(
            (int) $entrada->reserva()->value('usuario_id') === (int) $request->user()->id,
            403
        );

        $entrada->load('reserva.evento', 'tarifa.zona');

        return view('publico.entradas.show', compact('entrada'));
    }
}

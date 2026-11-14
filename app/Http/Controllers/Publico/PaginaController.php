<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;

class PaginaController extends Controller
{
    public function contacto()
    {
        return view('publico.paginas.contacto');
    }

    public function cancelaciones()
    {
        return view('publico.paginas.cancelaciones');
    }

    public function reembolsos()
    {
        return view('publico.paginas.reembolsos');
    }

    public function cambiosFecha()
    {
        return view('publico.paginas.cambios-fecha');
    }
}

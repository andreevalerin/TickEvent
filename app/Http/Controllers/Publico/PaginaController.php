<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PaginaController extends Controller
{
    public function contacto(): View
    {
        return view('publico.contacto');
    }

    public function cancelaciones()
    {
        return 'Política de cancelaciones';
    }

    public function reembolsos()
    {
        return 'Política de reembolsos';
    }

    public function cambiosFecha()
    {
        return 'Política de cambios de fecha';
    }
}

<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Services\CatalogoPublico;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(CatalogoPublico $catalogo): View
    {
        $eventos = $catalogo->eventos()->limit(6)->get();

        return view('publico.home', compact('eventos'));
    }
}

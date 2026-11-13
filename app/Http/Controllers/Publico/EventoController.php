<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Services\CatalogoPublico;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventoController extends Controller
{
    public function __construct(private CatalogoPublico $catalogo) {}

    public function index(Request $request): View
    {
        return $this->listar($request, 'publico.eventos.index', 'Todos los eventos');
    }

    public function conciertos(Request $request): View
    {
        return $this->listar($request, 'publico.eventos.conciertos', 'Conciertos', 'concierto');
    }

    public function partidos(Request $request): View
    {
        return $this->listar($request, 'publico.eventos.partidos', 'Partidos', 'partido');
    }

    public function comprar(Request $request, Evento $evento): View
    {
        abort_unless($evento->estado === 'publicado', 404);

        $evento->load('zonas.tarifas');

        $idSolicitado = filter_var($request->query('tarifa_id'), FILTER_VALIDATE_INT);
        $tarifaSeleccionada = $this->catalogo->sectores($evento)
            ->where('disponibles', '>', 0)
            ->firstWhere('id', $idSolicitado)['id'] ?? null;

        return view('publico.eventos.comprar', compact('evento', 'tarifaSeleccionada'));
    }

    public function show(Evento $evento): View
    {
        abort_unless($evento->estado === 'publicado', 404);

        $sectores = $this->catalogo->sectores($evento);

        return view('publico.eventos.show', compact('evento', 'sectores'));
    }

    private function listar(Request $request, string $vista, string $titulo, ?string $categoria = null): View
    {
        $datos = $request->validate([
            'buscar' => 'nullable|string|max:120',
            'categoria' => 'nullable|string|in:concierto,partido',
        ], [
            'buscar.string' => 'Escribe el nombre de un artista, equipo o evento.',
            'buscar.max' => 'La búsqueda admite hasta 120 caracteres.',
            'categoria.in' => 'Elige una categoría válida.',
            'categoria.string' => 'Elige una categoría válida.',
        ]);

        $busqueda = trim($datos['buscar'] ?? '');
        $categoria = $categoria ?? ($datos['categoria'] ?? null);
        $eventos = $this->catalogo->eventos($busqueda, $categoria)->get();

        return view($vista, compact('eventos', 'titulo', 'busqueda', 'categoria'));
    }
}

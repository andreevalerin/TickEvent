@php
    $categoriaActual = $categoria ?? null;
    $filtros = empty($busqueda) ? [] : ['buscar' => $busqueda];
@endphp
<nav class="filter-tabs" aria-label="Categorías de eventos">
    <a href="{{ route('eventos.index', $filtros) }}" @class(['active' => !$categoriaActual]) @if(!$categoriaActual) aria-current="page" @endif>Todos</a>
    <a href="{{ route('conciertos.index', $filtros) }}" @class(['active' => $categoriaActual === 'concierto']) @if($categoriaActual === 'concierto') aria-current="page" @endif>Conciertos</a>
    <a href="{{ route('partidos.index', $filtros) }}" @class(['active' => $categoriaActual === 'partido']) @if($categoriaActual === 'partido') aria-current="page" @endif>Deportes</a>
</nav>

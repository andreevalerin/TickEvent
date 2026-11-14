@extends('layouts.principal')

@section('titulo', $titulo . ' - TickEvent')

@section('contenido')

<main class="catalogo-eventos">
    <header class="catalogo-cabecera">
        <span class="etiqueta">CARTELERA TICKEVENT</span>
        <h1>{{ $titulo }}</h1>
        <p>Encuentra conciertos y partidos, consulta las zonas disponibles y elige tu próxima experiencia.</p>

        <form action="{{ route('eventos.index') }}" method="GET" class="catalogo-buscador">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por evento, lugar o ciudad">
            <select name="categoria">
                <option value="">Todas las categorías</option>
                <option value="concierto" @selected(request('categoria') === 'concierto')>Conciertos</option>
                <option value="partido" @selected(request('categoria') === 'partido')>Partidos</option>
            </select>
            <button type="submit">Buscar</button>
        </form>

        <nav aria-label="Filtrar eventos por categoría">
            <a href="{{ route('eventos.index') }}" @class(['activa' => $titulo === 'Todos los eventos'])>Todos</a>
            <a href="{{ route('conciertos.index') }}" @class(['activa' => $titulo === 'Conciertos'])>Conciertos</a>
            <a href="{{ route('partidos.index') }}" @class(['activa' => $titulo === 'Partidos'])>Partidos</a>
        </nav>
    </header>

    @if($eventos->isEmpty())
        <section class="estado-vacio">
            <h2>Por el momento no hay eventos disponibles</h2>
            <p>Consulta nuevamente cuando se publique la próxima cartelera.</p>
        </section>
    @else
        <section class="eventos-grid">
            @foreach($eventos as $evento)
                @include('partials.tarjeta-evento', ['evento' => $evento])
            @endforeach
        </section>
    @endif
</main>

@endsection

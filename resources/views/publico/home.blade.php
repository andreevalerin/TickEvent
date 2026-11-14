
@extends('layouts.principal')

@section('titulo', 'Inicio - TickEvent')

@section('contenido')

<main class="inicio">
    <section class="inicio-hero">
        <div class="inicio-hero-contenido">
            <span class="etiqueta">MÚSICA, PASIÓN Y MOMENTOS EN VIVO</span>
            <h1>Tu próxima entrada está <em>aquí</em></h1>
            <p>Conciertos y partidos en Perú. Elige tu próximo gran momento.</p>
        </div>
    </section>

    <form action="{{ route('eventos.index') }}" method="GET" class="inicio-buscador">
        <label>
            <span>¿Qué quieres vivir?</span>
            <input type="search" name="q" placeholder="Busca un artista, equipo o evento">
        </label>
        <label>
            <span>Categoría</span>
            <select name="categoria">
                <option value="">Todos los eventos</option>
                <option value="concierto">Conciertos</option>
                <option value="partido">Partidos</option>
            </select>
        </label>
        <button type="submit">Buscar</button>
    </form>

    <nav class="inicio-categorias" aria-label="Explorar por categoría">
        <a href="{{ route('eventos.index') }}" class="activa">Todos</a>
        <a href="{{ route('conciertos.index') }}">Conciertos</a>
        <a href="{{ route('partidos.index') }}">Partidos</a>
    </nav>

    <section class="seccion-eventos">
        <div class="seccion-cabecera">
            <div>
                <span class="etiqueta">CARTELERA</span>
                <h2>Próximos eventos</h2>
                <p>Elige el evento y la zona que prefieras.</p>
            </div>

            <a href="{{ route('eventos.index') }}">Ver toda la cartelera</a>
        </div>

        @if($eventos->isEmpty())
            <div class="estado-vacio">
                <h3>No hay eventos publicados por el momento</h3>
                <p>Vuelve pronto para consultar la nueva cartelera.</p>
            </div>
        @else
            <div class="eventos-grid">
                @foreach($eventos as $evento)
                    @include('partials.tarjeta-evento', ['evento' => $evento])
                @endforeach
            </div>
        @endif
    </section>

</main>

@endsection

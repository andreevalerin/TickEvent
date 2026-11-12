@extends('layouts.principal')

@section('titulo', 'Mis eventos - TickEvent')

@section('contenido')

<main class="compra-contenedor">
    <div class="organizador-cabecera">
        <div>
            <span class="etiqueta">PANEL DEL ORGANIZADOR</span>
            <h1>Mis eventos</h1>
            <p>Administra tus eventos, zonas, capacidades y tarifas.</p>
        </div>

        <a href="{{ route('organizador.eventos.create') }}" class="boton-comprar-link">
            Crear evento
        </a>
    </div>

    @if(session('estado'))
        <div class="compra-exito">{{ session('estado') }}</div>
    @endif

    @if($eventos->isEmpty())
        <section class="compra-panel entradas-vacio">
            <h2>Aún no registraste eventos</h2>
            <p>Crea tu primer evento y configura sus zonas y tarifas.</p>
        </section>
    @else
        <div class="organizador-eventos">
            @foreach($eventos as $evento)
                <article class="organizador-evento">
                    <div>
                        <span class="evento-estado {{ $evento->estado }}">
                            {{ strtoupper($evento->estado) }}
                        </span>
                        <h2>{{ $evento->titulo }}</h2>
                        <p>
                            {{ $evento->fecha_evento->format('d/m/Y H:i') }}
                            · {{ $evento->lugar }}, {{ $evento->ciudad }}
                        </p>
                    </div>

                    <div class="organizador-metricas">
                        <span><strong>{{ $evento->zonas_count }}</strong> zonas</span>
                        <span><strong>{{ number_format($evento->aforo_total) }}</strong> de aforo</span>
                    </div>

                    <div class="organizador-acciones">
                        @if($evento->estado === 'publicado')
                            <a href="{{ route('eventos.show', $evento) }}" class="boton-secundario">
                                Ver
                            </a>
                        @endif

                        <a href="{{ route('organizador.eventos.edit', $evento) }}" class="boton-secundario">
                            Editar
                        </a>

                        @if($evento->estado !== 'retirado')
                            <form action="{{ route('organizador.eventos.destroy', $evento) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="boton-retirar">
                                    Retirar
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</main>

@endsection

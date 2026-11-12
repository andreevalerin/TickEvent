@extends('layouts.principal')

@section('titulo', $titulo . ' - TickEvent')

@section('contenido')

<section class="contenido-evento" style="padding-top: 30px;">
    <h1>{{ $titulo }}</h1>

    <p>Encuentra tus próximos conciertos y partidos.</p>

    @forelse($eventos as $evento)

        <div class="panel-sectores" style="padding: 20px; margin-bottom: 15px;">

            <span class="etiqueta">
                {{ strtoupper($evento->categoria) }}
            </span>

            <h2>{{ $evento->titulo }}</h2>

            <p>
                {{ $evento->fecha_evento->format('d/m/Y') }}
                · {{ $evento->lugar }}
                · {{ $evento->ciudad }}
            </p>

            <a href="{{ route('eventos.show', $evento) }}">
                Ver evento y tarifas
            </a>

        </div>

    @empty

        <p>Por el momento no hay eventos disponibles.</p>

    @endforelse

</section>

@endsection

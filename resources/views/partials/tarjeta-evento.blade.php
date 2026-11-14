@php
    $precioMinimo = $evento->zonas
        ->where('activo', true)
        ->flatMap(fn ($zona) => $zona->tarifas->where('activo', true))
        ->min('precio');
@endphp

<article class="evento-tarjeta">
    <a href="{{ route('eventos.show', $evento) }}" class="evento-tarjeta-imagen">
        @if($evento->imagen_principal)
            <img
                src="{{ asset($evento->imagen_principal) }}"
                alt="{{ $evento->titulo }}"
                loading="lazy"
            >
        @else
            <span class="evento-tarjeta-sin-imagen">{{ strtoupper($evento->categoria) }}</span>
        @endif

        <span class="evento-categoria {{ $evento->categoria }}">
            {{ $evento->categoria === 'partido' ? 'Fútbol' : 'Concierto' }}
        </span>
    </a>

    <div class="evento-tarjeta-cuerpo">
        <time datetime="{{ $evento->fecha_evento->toIso8601String() }}">
            {{ strtoupper($evento->fecha_evento->translatedFormat('d M Y')) }}
        </time>

        <h2>
            <a href="{{ route('eventos.show', $evento) }}">{{ $evento->titulo }}</a>
        </h2>

        <p>{{ $evento->lugar }} · {{ $evento->ciudad }}</p>

        <div class="evento-tarjeta-pie">
            <div>
                @if($precioMinimo !== null)
                    <span>Desde</span>
                    <strong>S/ {{ number_format($precioMinimo, 2) }}</strong>
                @else
                    <span>Tarifas por publicar</span>
                @endif
            </div>

            <a href="{{ route('eventos.show', $evento) }}">Ver entradas</a>
        </div>
    </div>
</article>

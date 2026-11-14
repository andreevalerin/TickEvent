@extends('layouts.principal')

@section('titulo', $evento->titulo . ' - TickEvent')

@section('contenido')

<section class="portada-evento">
    @if($evento->imagen_principal)
        <img
            src="{{ asset($evento->imagen_principal) }}"
            alt="Portada de {{ $evento->titulo }}"
            class="portada-imagen"
        >
    @else
        <div class="portada-sin-imagen">
            <span class="etiqueta">
                {{ strtoupper($evento->categoria) }}
            </span>

            <h1>{{ $evento->titulo }}</h1>
            <p>{{ $evento->descripcion }}</p>

            <div class="datos-evento">
                <span>{{ $evento->fecha_evento->format('d/m/Y') }}</span>
                <span>{{ $evento->fecha_evento->format('H:i') }}</span>
                <span>{{ $evento->lugar }}, {{ $evento->ciudad }}</span>
            </div>
        </div>
    @endif
</section>

<main class="contenido-evento">

    <div class="pestanas" role="tablist" aria-label="Información del evento">
        <button
            type="button"
            class="pestana activa"
            data-panel="tarifario"
            role="tab"
            aria-selected="true"
            aria-controls="tarifario"
        >
            Tarifario
        </button>

        <button
            type="button"
            class="pestana"
            data-panel="acerca"
            role="tab"
            aria-selected="false"
            aria-controls="acerca"
        >
            Acerca del evento
        </button>

        <button
            type="button"
            class="pestana"
            data-panel="terminos"
            role="tab"
            aria-selected="false"
            aria-controls="terminos"
        >
            Términos y condiciones
        </button>
    </div>

    <section id="tarifario" role="tabpanel">
        @php
            $zonasActivas = $evento->zonas->where('activo', true);
            $tarifasActivas = $zonasActivas->flatMap(fn ($zona) => $zona->tarifas->where('activo', true));
        @endphp
        <div class="tarifario-dinamico">
            <div class="tarifario-plano">
                @if($evento->imagen_plano)
                    <img src="{{ asset($evento->imagen_plano) }}" alt="Plano de zonas de {{ $evento->titulo }}" @class(['compuesto' => str_contains($evento->imagen_plano, 'robbie-williams/tarifario.png')])>
                @else
                    <div class="tarifario-pendiente">Plano de zonas pendiente de publicación.</div>
                @endif
            </div>
            <div class="tarifario-lista">
                <div class="tarifario-lista-cabecera"><strong>Sector</strong><strong>Tarifa y precio</strong></div>
                @forelse($zonasActivas as $zona)
                    @foreach($zona->tarifas->where('activo', true) as $tarifa)
                        <div class="tarifario-fila">
                            <span class="tarifario-marca" aria-hidden="true"></span>
                            <span><strong>{{ $zona->nombre }}</strong><small>{{ $tarifa->nombre }}</small></span>
                            <strong>S/ {{ number_format($tarifa->precio, 2) }}</strong>
                        </div>
                    @endforeach
                @empty
                    <div class="tarifario-pendiente">No hay zonas con tarifas activas.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="acerca" class="panel-informativo" role="tabpanel" hidden>
        <h2>Acerca del evento</h2>

        <h3>{{ $evento->titulo }}</h3>

        @if($evento->descripcion)
            <p>{{ $evento->descripcion }}</p>
        @endif

        <p>
            <strong>Fecha:</strong>
            {{ $evento->fecha_evento->format('d/m/Y') }}
        </p>

        <p>
            <strong>Hora:</strong>
            {{ $evento->fecha_evento->format('H:i') }}
        </p>

        <p>
            <strong>Lugar:</strong>
            {{ $evento->lugar }}, {{ $evento->ciudad }}
        </p>

        <p>
            <strong>Distribución:</strong>
            Las entradas se venden por zonas.
            No se asignan asientos numerados.
        </p>
    </section>

    <section id="terminos" class="panel-informativo" role="tabpanel" hidden>
        <h2>Términos y condiciones</h2>

        <p>
            Cada entrada permite el acceso a la zona adquirida
            y corresponde a un asistente.
        </p>

        <p>
            El límite de compra es de seis entradas por cliente
            y por evento, incluyendo compras anteriores.
        </p>

        <p>
            La disponibilidad de entradas se confirmará
            durante el proceso de compra.
        </p>

        <p>
            La compra está sujeta a las políticas de TickEvent.
        </p>

        <a href="{{ route('politicas.cancelaciones') }}">
            Consultar política de cancelaciones
        </a>
    </section>

    <div class="barra-cta-evento">
        <div class="barra-cta-info">
            <strong>VENTA GENERAL</strong>
            <span class="barra-disponibilidad {{ $tarifasActivas->isNotEmpty() ? 'disponible' : '' }}">{{ $tarifasActivas->isNotEmpty() ? 'Disponible' : 'No disponible' }}</span>
            <span>Entradas por zonas · Sin asientos numerados</span>
        </div>

        @if(!auth()->check() || auth()->user()->rol === 'cliente')
            <a href="{{ route('eventos.comprar', $evento) }}" class="boton-comprar-link">Comprar ahora →</a>
        @elseif(auth()->user()->rol === 'organizador')
            <a href="{{ route('organizador.eventos.index') }}" class="boton-comprar-link">Volver a mis eventos</a>
        @elseif(auth()->user()->rol === 'administrador')
            <a href="{{ route('admin.eventos.index') }}" class="boton-comprar-link">Volver a eventos</a>
        @endif
    </div>

</main>

<script>
    document.querySelectorAll('.pestana').forEach(boton => {
        boton.addEventListener('click', () => {
            document.querySelectorAll('.pestana').forEach(pestana => {
                pestana.classList.remove('activa');
                pestana.setAttribute('aria-selected', 'false');
            });

            boton.classList.add('activa');
            boton.setAttribute('aria-selected', 'true');

            ['tarifario', 'acerca', 'terminos'].forEach(id => {
                document.getElementById(id).hidden =
                    id !== boton.dataset.panel;
            });
        });
    });
</script>

@endsection

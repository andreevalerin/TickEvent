@extends('layouts.principal')

@section('titulo', 'Entrada - ' . $entrada->reserva->evento->titulo)

@section('contenido')

<main class="compra-contenedor">
    <a href="{{ route('entradas.index') }}" class="volver-evento">
        ← Volver a mis entradas
    </a>

    <article class="entrada-digital">
        <header>
            <span class="logo-entrada">▣ TICKEVENT</span>
            <span class="entrada-estado {{ $entrada->estado }}">
                {{ strtoupper($entrada->estado) }}
            </span>
        </header>

        @if($entrada->reserva->evento->imagen_principal)
            <img
                src="{{ asset($entrada->reserva->evento->imagen_principal) }}"
                alt="{{ $entrada->reserva->evento->titulo }}"
                class="entrada-digital-imagen"
            >
        @endif

        <div class="entrada-digital-contenido">
            <span class="etiqueta">ENTRADA NOMINATIVA</span>
            <h1>{{ $entrada->reserva->evento->titulo }}</h1>
            <p>{{ $entrada->reserva->evento->descripcion }}</p>

            <dl class="entrada-datos">
                <div>
                    <dt>Fecha</dt>
                    <dd>{{ $entrada->reserva->evento->fecha_evento->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt>Lugar</dt>
                    <dd>{{ $entrada->reserva->evento->lugar }}, {{ $entrada->reserva->evento->ciudad }}</dd>
                </div>
                <div>
                    <dt>Zona</dt>
                    <dd>{{ $entrada->tarifa->zona->nombre }}</dd>
                </div>
                <div>
                    <dt>Tarifa</dt>
                    <dd>{{ $entrada->tarifa->nombre }}</dd>
                </div>
                <div>
                    <dt>Asistente</dt>
                    <dd>DNI {{ $entrada->dni }}</dd>
                </div>
            </dl>

            <div class="entrada-codigo">
                <span>Código de acceso</span>
                <strong>{{ $entrada->codigo }}</strong>
            </div>

            <p class="compra-nota">
                La validación manual está habilitada. La representación QR
                se añadirá cuando se autorice un generador QR confiable.
            </p>
        </div>
    </article>
</main>

@endsection

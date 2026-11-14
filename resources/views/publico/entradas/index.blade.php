@extends('layouts.principal')

@section('titulo', 'Mis entradas - TickEvent')

@section('contenido')

<main class="compra-contenedor">
    <div class="compra-titulo">
        <span class="etiqueta">MIS COMPRAS</span>
        <h1>Mis entradas</h1>
        <p>Entradas emitidas después de pagos confirmados.</p>
    </div>

    @if($entradas->isEmpty())
        <section class="compra-panel entradas-vacio">
            <h2>Aún no tienes entradas emitidas</h2>
            <p>Cuando Culqi confirme una compra, tus entradas aparecerán aquí.</p>
            <a href="{{ route('eventos.index') }}" class="compra-continuar">
                Ver eventos
            </a>
        </section>
    @else
        <div class="entradas-lista">
            @foreach($entradas as $entrada)
                <article class="entrada-resumen">
                    @if($entrada->reserva->evento->imagen_principal)
                        <img
                            src="{{ asset($entrada->reserva->evento->imagen_principal) }}"
                            alt="{{ $entrada->reserva->evento->titulo }}"
                            class="entrada-resumen-imagen"
                        >
                    @endif

                    <div>
                        <span class="entrada-estado {{ $entrada->estado }}">
                            {{ strtoupper($entrada->estado) }}
                        </span>
                        <h2>{{ $entrada->reserva->evento->titulo }}</h2>
                        <p>
                            {{ $entrada->reserva->evento->fecha_evento->format('d/m/Y H:i') }}
                            · {{ $entrada->reserva->evento->lugar }}
                        </p>
                    </div>

                    <div class="entrada-resumen-datos">
                        <span>{{ $entrada->tarifa->zona->nombre }}</span>
                        <strong>DNI {{ $entrada->dni }}</strong>
                    </div>

                    <a href="{{ route('entradas.show', $entrada) }}" class="boton-comprar-link">
                        Ver entrada
                    </a>
                </article>
            @endforeach
        </div>
    @endif
</main>

@endsection

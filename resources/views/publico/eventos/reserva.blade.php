@extends('layouts.principal')

@section('titulo', 'Reserva - TickEvent')

@section('contenido')

<main class="compra-contenedor">

    <div class="compra-evento-contexto">
        @if($reserva->evento->imagen_principal)
            <img src="{{ asset($reserva->evento->imagen_principal) }}" alt="{{ $reserva->evento->titulo }}">
        @endif

        <div>
            <span class="etiqueta">RESERVA DE ENTRADAS</span>
            <h1>{{ $reserva->evento->titulo }}</h1>
            <p>Reserva N.º {{ $reserva->id }}</p>
        </div>
    </div>

    <div class="compra-pasos">
        <div class="compra-paso activo">
            <span class="compra-paso-numero">✓</span>
            <span>Sectores</span>
        </div>
        <div class="compra-paso-linea"></div>
        <div class="compra-paso activo">
            <span class="compra-paso-numero">✓</span>
            <span>Tarifa</span>
        </div>
        <div class="compra-paso-linea"></div>
        <div class="compra-paso activo">
            <span class="compra-paso-numero">3</span>
            <span>Resumen</span>
        </div>
        <div class="compra-paso-linea"></div>
        <div class="compra-paso {{ $reserva->estado === 'confirmada' ? 'activo' : '' }}">
            <span class="compra-paso-numero">4</span>
            <span>Pago</span>
        </div>
    </div>

    @if(session('estado'))
        <div class="compra-exito">
            {{ session('estado') }}
        </div>
    @endif

    @if($errors->any())
        <div class="compra-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="compra-panel reserva-panel-principal">

        @if($reserva->estado === 'activa')
            <h2>Reserva realizada</h2>
            <p>Tus entradas están reservadas temporalmente.</p>

            <div class="compra-total">
                <span>Tiempo restante</span>
                <strong id="contador"
                    data-expira="{{ $reserva->expira_en->toIso8601String() }}">
                    15:00
                </strong>
            </div>
        @elseif($reserva->estado === 'procesando_pago')
            <h2>Pago en verificación</h2>
            <p>
                TickEvent todavía no ha recibido una confirmación concluyente
                de Culqi. La reserva no se marcará como pagada hasta verificarla.
            </p>
        @elseif($reserva->estado === 'confirmada')
            <h2>Compra confirmada</h2>
            <p>El pago fue verificado y la reserva quedó confirmada.</p>
        @else
            <h2>Reserva {{ $reserva->estado }}</h2>
            <p>Esta reserva ya no está activa.</p>
        @endif

        @foreach($reserva->detalles as $detalle)
            <div class="compra-detalle">
                <div>
                    <span>Zona</span>
                    <strong>{{ $detalle->tarifa->zona->nombre }}</strong>
                </div>

                <div>
                    <span>Tarifa</span>
                    <strong>{{ $detalle->tarifa->nombre }}</strong>
                </div>

                <div>
                    <span>Cantidad</span>
                    <strong>{{ $detalle->cantidad }}</strong>
                </div>
            </div>

            <div class="compra-total">
                <strong>Total</strong>
                <strong>
                    S/ {{ number_format(
                        $detalle->cantidad *
                        ($detalle->precio_unitario + $detalle->cargo_servicio_unitario),
                        2
                    ) }}
                </strong>
            </div>
        @endforeach

        <h2>Asistentes</h2>

        @foreach($asistentes as $asistente)
            <p>DNI: {{ $asistente->dni }}</p>
        @endforeach

        @if($reserva->estado === 'activa')
            <p class="compra-nota">
                La reserva aún no está pagada.
                Los QR se generan después del pago confirmado.
            </p>

            <div class="reserva-acciones">
                <a
                    href="{{ route('reservas.pago', $reserva) }}"
                    class="compra-continuar"
                >
                    Continuar al pago
                </a>

                <form action="{{ route('reservas.cancelar', $reserva) }}" method="POST">
                    @csrf
                    <button type="submit" class="boton-secundario">
                        Cancelar reserva
                    </button>
                </form>
            </div>
        @elseif($reserva->estado === 'confirmada')
            <p class="compra-nota">
                Tus entradas nominativas ya fueron emitidas.
            </p>

            <a href="{{ route('entradas.index') }}" class="compra-continuar">
                Ver mis entradas
            </a>
        @endif

    </section>

</main>

@if($reserva->estado === 'activa')
<script>
    const contador = document.getElementById('contador');
    const vencimiento = new Date(contador.dataset.expira).getTime();

    function actualizarContador() {
        const restante = Math.max(0, vencimiento - Date.now());
        const minutos = Math.floor(restante / 60000);
        const segundos = Math.floor((restante % 60000) / 1000);

        contador.textContent =
            String(minutos).padStart(2, '0') + ':' +
            String(segundos).padStart(2, '0');

        if (restante === 0) {
            clearInterval(intervalo);
            window.location.reload();
        }
    }

    const intervalo = setInterval(actualizarContador, 1000);
    actualizarContador();
</script>
@endif

@endsection

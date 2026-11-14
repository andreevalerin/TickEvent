@extends('layouts.principal')

@section('titulo', 'Pago - ' . $reserva->evento->titulo)

@section('contenido')

<main class="compra-contenedor">

    <div class="pago-evento">
        @if($reserva->evento->imagen_principal)
            <img
                src="{{ asset($reserva->evento->imagen_principal) }}"
                alt="{{ $reserva->evento->titulo }}"
            >
        @endif

        <div>
            <h1>{{ $reserva->evento->titulo }}</h1>
            <p>{{ $reserva->evento->descripcion }}</p>
            <small>
                {{ $reserva->evento->fecha_evento->format('d/m/Y H:i') }}
                · {{ $reserva->evento->lugar }}, {{ $reserva->evento->ciudad }}
            </small>
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
            <span class="compra-paso-numero">✓</span>
            <span>Resumen</span>
        </div>

        <div class="compra-paso-linea"></div>

        <div class="compra-paso activo">
            <span class="compra-paso-numero">4</span>
            <span>Pago</span>
        </div>
    </div>

    @if($errors->any())
        <div class="compra-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="pago-columnas">

        <section class="compra-panel">
            <h2>Método de pago</h2>
            <p>Selecciona cómo deseas realizar tu pago.</p>

            <div class="pago-metodos">
                <button
                    type="button"
                    class="pago-metodo seleccionado"
                    @disabled(!$culqiConfigurado)
                >
                    <strong>Tarjeta</strong>
                    <small>Crédito o débito</small>
                </button>

                <button type="button" class="pago-metodo no-disponible" disabled>
                    <strong>Yape</strong>
                    <small>No disponible</small>
                </button>

                <button type="button" class="pago-metodo no-disponible" disabled>
                    <strong>Plin</strong>
                    <small>No disponible</small>
                </button>
            </div>

            <div class="pago-instrucciones">
                <h3>Pago con tarjeta mediante Culqi</h3>

                @if($culqiConfigurado)
                    <p>
                        Culqi abrirá su formulario seguro de pruebas.
                        TickEvent solo recibirá el token de pago y nunca
                        almacenará el número de tarjeta ni el CVV.
                    </p>
                @else
                    <p>
                        La integración está preparada, pero faltan las
                        credenciales válidas de Culqi en modo pruebas.
                        No se realizará ningún cobro ni se confirmará la reserva.
                    </p>
                @endif
            </div>

            <div id="pago-error-culqi" class="compra-error" hidden></div>

            <button
                class="pago-boton"
                type="button"
                id="abrir-culqi"
                @disabled(!$culqiConfigurado)
            >
                Pagar S/ {{ number_format($total, 2) }}
            </button>

            <p class="pago-advertencia">
                @if($culqiConfigurado)
                    Solo se confirmará la compra cuando Culqi responda
                    que el cargo de pruebas fue exitoso.
                @else
                    Configuración pendiente. Yape y Plin no están habilitados.
                @endif
            </p>

            @if($culqiConfigurado)
                <form
                    action="{{ route('reservas.pago.procesar', $reserva) }}"
                    method="POST"
                    id="formulario-culqi"
                    hidden
                >
                    @csrf
                    <input type="hidden" name="culqi_token" id="culqi-token">
                </form>
            @endif
        </section>

        <aside class="compra-panel pago-resumen">
            <h2>Resumen de compra</h2>

            <div class="pago-resumen-evento">
                @if($reserva->evento->imagen_principal)
                    <img src="{{ asset($reserva->evento->imagen_principal) }}" alt="{{ $reserva->evento->titulo }}">
                @endif
                <div>
                    <strong>{{ $reserva->evento->titulo }}</strong>
                    <span>{{ $reserva->evento->fecha_evento->translatedFormat('D. d M Y, H:i') }}</span>
                    <span>{{ $reserva->evento->lugar }}, {{ $reserva->evento->ciudad }}</span>
                </div>
            </div>

            @foreach($reserva->detalles as $detalle)
                <div class="compra-detalle">
                    <div>
                        <span>Sector</span>
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
            @endforeach

            <div class="compra-detalle">
                <div>
                    <span>Subtotal</span>
                    <strong>S/ {{ number_format($subtotal, 2) }}</strong>
                </div>

                <div>
                    <span>Cargo por servicio</span>
                    <strong>S/ {{ number_format($servicio, 2) }}</strong>
                </div>
            </div>

            <div class="compra-total">
                <strong>Total a pagar</strong>
                <strong>S/ {{ number_format($total, 2) }}</strong>
            </div>

            <div class="pago-tiempo">
                <span>Tiempo restante de la reserva</span>
                <strong
                    id="tiempo-pago"
                    data-expira="{{ $reserva->expira_en->toIso8601String() }}"
                >
                    Calculando...
                </strong>
            </div>

            <a href="{{ route('reservas.mostrar', $reserva) }}" class="pago-volver">
                Volver a mi reserva
            </a>

            <div class="pago-seguro">
                <strong>Pago seguro</strong>
                <span>TickEvent no almacena números de tarjeta ni códigos de seguridad.</span>
            </div>
        </aside>
    </div>

</main>

@if($culqiConfigurado)
<script src="https://checkout.culqi.com/js/v4"></script>
@endif

<script>
    const contadorPago = document.getElementById('tiempo-pago');
    const expiraPago = new Date(contadorPago.dataset.expira).getTime();

    function actualizarPago() {
        const restante = Math.max(0, expiraPago - Date.now());
        const minutos = Math.floor(restante / 60000);
        const segundos = Math.floor((restante % 60000) / 1000);

        contadorPago.textContent =
            String(minutos).padStart(2, '0') + ':' +
            String(segundos).padStart(2, '0');

        if (restante === 0) {
            clearInterval(intervaloPago);
            window.location.reload();
        }
    }

    const intervaloPago = setInterval(actualizarPago, 1000);
    actualizarPago();
</script>

@if($culqiConfigurado)
<script>
    const botonCulqi = document.getElementById('abrir-culqi');
    const formularioCulqi = document.getElementById('formulario-culqi');
    const tokenCulqi = document.getElementById('culqi-token');
    const errorCulqi = document.getElementById('pago-error-culqi');

    Culqi.publicKey = @json($culqiPublicKey);

    Culqi.settings({
        title: 'TickEvent',
        currency: 'PEN',
        amount: {{ $totalCentimos }},
        xculqirsaid: @json($culqiRsaId),
        rsapublickey: @json($culqiRsaPublicKey)
    });

    Culqi.options({
        lang: 'es',
        installments: false,
        paymentMethods: {
            tarjeta: true,
            yape: false,
            bancaMovil: false,
            agente: false,
            billetera: false,
            cuotealo: false
        },
        style: {
            buttonBackground: '#00c9c7',
            menuColor: '#003235',
            linksColor: '#008b91',
            buttonText: 'Pagar',
            buttonTextColor: '#003235',
            priceColor: '#003235'
        }
    });

    botonCulqi.addEventListener('click', () => {
        errorCulqi.hidden = true;
        Culqi.open();
    });

    function culqi() {
        if (Culqi.token) {
            tokenCulqi.value = Culqi.token.id;
            Culqi.close();
            botonCulqi.disabled = true;
            botonCulqi.textContent = 'Verificando pago...';
            formularioCulqi.submit();
            return;
        }

        const error = Culqi.error || {};
        errorCulqi.textContent = error.user_message
            || error.merchant_message
            || 'Culqi no pudo generar el token de pago.';
        errorCulqi.hidden = false;
    }
</script>
@endif

@endsection

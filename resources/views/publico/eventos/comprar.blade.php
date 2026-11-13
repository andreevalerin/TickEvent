@extends('layouts.principal')

@section('titulo', 'Comprar entradas - ' . $evento->titulo)

@section('contenido')

<main class="compra-contenedor">

    <a href="{{ route('eventos.show', $evento) }}" class="volver-evento">
        ← Volver al evento
    </a>

    <div class="compra-titulo">
        <span class="etiqueta">COMPRA DE ENTRADAS</span>
        <h1>{{ $evento->titulo }}</h1>
        <p>
            {{ $evento->fecha_evento->format('d/m/Y') }}
            · {{ $evento->fecha_evento->format('H:i') }}
            · {{ $evento->lugar }}, {{ $evento->ciudad }}
        </p>
    </div>

    <div class="compra-pasos">
        <div class="compra-paso activo" id="paso-sector">
            <span class="compra-paso-numero">1</span>
            <span>Sector</span>
        </div>

        <div class="compra-paso-linea"></div>

        <div class="compra-paso" id="paso-asistentes">
            <span class="compra-paso-numero">2</span>
            <span>Asistentes</span>
        </div>

        <div class="compra-paso-linea"></div>

        <div class="compra-paso">
            <span class="compra-paso-numero">3</span>
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

    
    <form
    action="{{ route('reservas.guardar', $evento) }}"
    method="POST"
    id="formulario-reserva"
    data-cargo-servicio="{{ $evento->cargoServicio() }}"
    data-dni-anteriores="{{ json_encode(old('dni', [])) }}">

        @csrf

        <div class="compra-columnas">

            <section class="compra-panel">
                <h2>Selecciona tu zona</h2>

                <p>
                    Elige la zona donde deseas disfrutar del evento.
                </p>

                <div class="compra-zonas">

                    @foreach($evento->zonas as $zona)
                        @if($zona->activo)

                            @foreach($zona->tarifas as $tarifa)
                                @if($tarifa->activo)

                                    <label class="compra-zona">

                                        <input
                                            type="radio"
                                            name="tarifa_id"
                                            value="{{ $tarifa->id }}"
                                            data-zona="{{ $zona->nombre }}"
                                            data-tarifa="{{ $tarifa->nombre }}"
                                            data-precio="{{ $tarifa->precio }}"
                                            @checked((string) old('tarifa_id', $tarifaSeleccionada) === (string) $tarifa->id)
                                            required
                                        >

                                        <span class="compra-zona-info">
                                            <strong>
                                                {{ $zona->nombre }}
                                            </strong>

                                            <small>
                                                {{ $tarifa->nombre }}
                                            </small>
                                        </span>

                                        <strong class="compra-zona-precio">
                                            S/ {{ number_format($tarifa->precio, 2) }}
                                        </strong>

                                    </label>

                                @endif
                            @endforeach

                        @endif
                    @endforeach

                </div>

                <p class="compra-nota">
                    Las entradas permiten el acceso a la zona
                    seleccionada. No se asignan asientos numerados.
                </p>

            </section>

            <aside class="compra-panel compra-resumen">

                <h2>Resumen de compra</h2>

                <label for="cantidad-entradas">
                    Cantidad de entradas
                </label>

                <select
                    id="cantidad-entradas"
                    name="cantidad"
                    required
                >
                    @for($i = 1; $i <= 6; $i++)
                        <option
                            value="{{ $i }}"
                            @selected((int) old('cantidad', 1) === $i)
                        >
                            {{ $i }}
                        </option>
                    @endfor
                </select>

                <div class="compra-detalle">

                    <div>
                        <span>Zona seleccionada</span>
                        <strong id="resumen-zona">
                            Sin seleccionar
                        </strong>
                    </div>

                    <div>
                        <span>Precio por entrada</span>
                        <strong id="resumen-precio">
                            S/ 0.00
                        </strong>
                    </div>

                    <div>
                        <span>Subtotal</span>
                        <strong id="resumen-subtotal">
                            S/ 0.00
                        </strong>
                    </div>

                    <div>
                        <span>Cargo por servicio</span>
                        <strong id="resumen-servicio">
                            S/ 0.00
                        </strong>
                    </div>

                </div>

                <div class="compra-total">
                    <strong>Total</strong>
                    <strong id="resumen-total">
                        S/ 0.00
                    </strong>
                </div>

                <button
                    type="button"
                    class="compra-continuar"
                    id="continuar-compra"
                    disabled
                >
                    Continuar con los asistentes
                </button>

                <small class="compra-aviso">
                    Máximo 6 entradas acumuladas por cliente
                    y evento, incluyendo compras anteriores.
                </small>

            </aside>

        </div>

        <section
            id="datos-asistentes"
            class="compra-panel compra-asistentes"
            hidden
        >

            <span class="etiqueta">
                DATOS DE LOS ASISTENTES
            </span>

            <h2>¿Quiénes asistirán al evento?</h2>

            <p>
                Ingresa el DNI de cada persona que utilizará
                una entrada. Puedes comprar entradas para ti
                o para otras personas.
            </p>

            <div id="lista-asistentes"></div>

            <p class="compra-nota">
                Cada entrada corresponde a un asistente.
                Los DNI deben ser diferentes y contener
                ocho dígitos.
            </p>

            <div class="compra-total">
                <strong>Total de la reserva</strong>
                <strong id="total-final">
                    S/ 0.00
                </strong>
            </div>

            <button
                type="submit"
                class="compra-continuar"
                id="reservar-entradas"
            >
                Reservar entradas por 15 minutos
            </button>

            <small class="compra-aviso">
                La reserva estará disponible durante 15 minutos.
                El pago se realizará en el siguiente paso.
            </small>

        </section>

    </form>

</main>

<script>
    const opciones = document.querySelectorAll('input[name="tarifa_id"]');
    const cantidad = document.getElementById('cantidad-entradas');
    const continuar = document.getElementById('continuar-compra');
    const asistentes = document.getElementById('datos-asistentes');
    const listaAsistentes = document.getElementById('lista-asistentes');
    const formulario = document.getElementById('formulario-reserva');
    const reservar = document.getElementById('reservar-entradas');
    const cargoServicio = Number(formulario.dataset.cargoServicio);
    const dniAnteriores = JSON.parse(formulario.dataset.dniAnteriores || '[]');


    function dinero(valor) {
        return 'S/ ' + valor.toFixed(2);
    }

    function obtenerSeleccion() {
        return document.querySelector('input[name="tarifa_id"]:checked');
    }

    function actualizarResumen() {
        const seleccion = obtenerSeleccion();
        const unidades = Number(cantidad.value);

        if (!seleccion) {
            document.getElementById('resumen-zona').textContent = 'Sin seleccionar';
            document.getElementById('resumen-precio').textContent = dinero(0);
            document.getElementById('resumen-subtotal').textContent = dinero(0);
            document.getElementById('resumen-servicio').textContent = dinero(0);
            document.getElementById('resumen-total').textContent = dinero(0);
            document.getElementById('total-final').textContent = dinero(0);

            continuar.disabled = true;
            reservar.disabled = true;
            asistentes.hidden = true;

            return;
        }

        const precio = Number(seleccion.dataset.precio);
        const subtotal = precio * unidades;
        const servicio = cargoServicio * unidades;
        const total = subtotal + servicio;

        document.getElementById('resumen-zona').textContent =
            seleccion.dataset.zona + ' - ' + seleccion.dataset.tarifa;

        document.getElementById('resumen-precio').textContent =
            dinero(precio);

        document.getElementById('resumen-subtotal').textContent =
            dinero(subtotal);

        document.getElementById('resumen-servicio').textContent =
            dinero(servicio);

        document.getElementById('resumen-total').textContent =
            dinero(total);

        document.getElementById('total-final').textContent =
            dinero(total);

        continuar.disabled = false;
        reservar.disabled = false;

        if (!asistentes.hidden) {
            mostrarAsistentes();
        }
    }

    function mostrarAsistentes(valoresIniciales = null) {
        const unidades = Number(cantidad.value);

        const valoresGuardados = valoresIniciales ??
            Array.from(
                listaAsistentes.querySelectorAll('input')
            ).map(entrada => entrada.value);

        listaAsistentes.replaceChildren();

        for (let i = 1; i <= unidades; i++) {
            const grupo = document.createElement('div');
            grupo.className = 'compra-asistente';

            const etiqueta = document.createElement('label');
            etiqueta.textContent = 'DNI del asistente ' + i;
            etiqueta.htmlFor = 'dni-' + i;

            const entrada = document.createElement('input');
            entrada.type = 'text';
            entrada.id = 'dni-' + i;
            entrada.name = 'dni[]';
            entrada.maxLength = 8;
            entrada.minLength = 8;
            entrada.inputMode = 'numeric';
            entrada.pattern = '[0-9]{8}';
            entrada.placeholder = 'Ingresa 8 dígitos';
            entrada.autocomplete = 'off';
            entrada.required = true;
            entrada.value = valoresGuardados[i - 1] ?? '';

            entrada.addEventListener('input', () => {
                entrada.value = entrada.value
                    .replace(/\D/g, '')
                    .slice(0, 8);
            });

            grupo.append(etiqueta, entrada);
            listaAsistentes.appendChild(grupo);
        }

        asistentes.hidden = false;

        document.getElementById('paso-asistentes')
            .classList.add('activo');
    }

    opciones.forEach(opcion => {
        opcion.addEventListener('change', actualizarResumen);
    });

    cantidad.addEventListener('change', actualizarResumen);

    continuar.addEventListener('click', () => {
        if (!obtenerSeleccion()) {
            return;
        }

        if (asistentes.hidden) {
            mostrarAsistentes();
        }

        asistentes.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });

    formulario.addEventListener('submit', evento => {
        const seleccion = obtenerSeleccion();
        const unidades = Number(cantidad.value);
        const campos = Array.from(
            listaAsistentes.querySelectorAll('input[name="dni[]"]')
        );

        if (!seleccion || asistentes.hidden || campos.length !== unidades) {
            evento.preventDefault();
            mostrarAsistentes();

            asistentes.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            return;
        }

        const numeros = campos.map(campo => campo.value);

        if (new Set(numeros).size !== numeros.length) {
            evento.preventDefault();
            alert('Cada asistente debe tener un DNI diferente.');
            return;
        }

        reservar.disabled = true;
        reservar.textContent = 'Registrando reserva...';
    });

    actualizarResumen();

    if (dniAnteriores.length > 0 && obtenerSeleccion()) {
        mostrarAsistentes(dniAnteriores);
    }
</script>

@endsection

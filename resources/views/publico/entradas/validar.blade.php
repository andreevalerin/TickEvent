@extends('layouts.acceso')

@section('titulo', 'Validar entrada - TickEvent')

@section('contenido')

<div class="acceso-contenido">
    @if($eventoSeleccionado)
        <section class="acceso-evento-cabecera">
            <div class="acceso-evento-imagen">
                @if($eventoSeleccionado->imagen_principal)
                    <img src="{{ asset($eventoSeleccionado->imagen_principal) }}" alt="{{ $eventoSeleccionado->titulo }}">
                @endif
            </div>
            <div class="acceso-evento-informacion">
                <h1>{{ $eventoSeleccionado->titulo }}</h1>
                <p><span>Fecha</span> {{ $eventoSeleccionado->fecha_evento->translatedFormat('D. d M Y, H:i') }} <span>Lugar</span> {{ $eventoSeleccionado->lugar }}, {{ $eventoSeleccionado->ciudad }}</p>
            </div>
            @if($eventosAsignados->count() > 1)
                <form action="{{ route('entradas.validar') }}" method="GET" class="acceso-evento-selector">
                    <label for="evento_id">Evento asignado</label>
                    <select name="evento_id" id="evento_id" onchange="this.form.submit()">
                        @foreach($eventosAsignados as $eventoAsignado)
                            <option value="{{ $eventoAsignado->id }}" @selected($eventoAsignado->id === $eventoSeleccionado->id)>{{ $eventoAsignado->titulo }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </section>
    @endif

    @if($eventoSeleccionado)
    @if($errors->any())
        <div class="compra-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="validacion-columnas">
        <section class="compra-panel validacion-lector">
            <div>
                <h2>Escanear código QR</h2>
                <p>Acerca la entrada digital o impresa al lector.</p>
            </div>

            <div class="camara-visor">
                <video id="camara-video" playsinline muted></video>
                <div id="camara-vacia">
                    <strong>Cámara desactivada</strong>
                    <span>Actívala para mostrar la imagen real del dispositivo.</span>
                </div>
            </div>

            <div class="camara-controles">
                <label id="camara-selector-contenedor" hidden>
                    <span>Cámara</span>
                    <select id="camara-selector"></select>
                </label>
                <button type="button" id="camara-iniciar" class="boton-comprar-link">Activar cámara</button>
                <button type="button" id="camara-detener" class="boton-secundario" disabled>Detener</button>
            </div>

            <p id="camara-estado" class="camara-estado" role="status">La cámara no está activa.</p>

            <div class="validacion-separador"><span>Ingresar código manualmente</span></div>

            <form id="validacion-formulario" action="{{ route('entradas.validar.procesar') }}" method="POST" class="validacion-formulario">
                @csrf
                <input type="hidden" name="evento_id" value="{{ $eventoSeleccionado->id }}">
                <label for="codigo">Código de entrada</label>
                <input
                    type="text"
                    name="codigo"
                    id="codigo"
                    value="{{ old('codigo') }}"
                    maxlength="80"
                    autocomplete="off"
                    required
                >
                <button type="submit" class="compra-continuar">Validar</button>
            </form>
        </section>

        <section class="compra-panel validacion-resultado">
            @isset($permitido)
                <div class="validacion-estado {{ $permitido ? 'permitido' : 'denegado' }}">
                    <span class="validacion-estado-icono" aria-hidden="true">{{ $permitido ? '✓' : '×' }}</span>
                    <div>
                        <strong>{{ $permitido ? 'Acceso permitido' : 'Acceso denegado' }}</strong>
                        <span>{{ $motivo }}</span>
                    </div>
                </div>

                @if($entrada)
                    <div class="validacion-entrada-detalle">
                        <div>
                            <span>Zona</span>
                            <strong>{{ $entrada->tarifa->zona->nombre }}</strong>
                        </div>
                        <div>
                            <span>Tarifa</span>
                            <strong>{{ $entrada->tarifa->nombre }}</strong>
                        </div>
                        <div>
                            <span>Cantidad</span>
                            <strong>1</strong>
                        </div>
                        <div>
                            <span>Código</span>
                            <strong>{{ $entrada->codigo }}</strong>
                        </div>
                        <div>
                            <span>Estado</span>
                            <strong>{{ $permitido ? 'ENTRADA VÁLIDA' : ($entrada->estado === 'usada' ? 'YA UTILIZADA' : strtoupper($entrada->estado)) }}</strong>
                        </div>
                        @if($entrada->usada_en)
                            <div>
                                <span>Fecha de uso</span>
                                <strong>{{ $entrada->usada_en->translatedFormat('D. d M Y, H:i:s') }}</strong>
                            </div>
                        @endif
                        @if($entrada->validador)
                            <div>
                                <span>Operador</span>
                                <strong>{{ $entrada->validador->name }}</strong>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="validacion-motivo {{ $permitido ? 'permitido' : 'denegado' }}">
                    <strong>Motivo</strong>
                    <span>{{ $motivo }}</span>
                </div>

                @if($permitido && $entrada)
                    <div class="validacion-registro">
                        <div><strong>Entrada validada correctamente</strong><span>El asistente puede ingresar al evento.</span></div>
                        <div><span>Operador</span><strong>{{ $entrada->validador?->name ?? auth()->user()->name }}</strong></div>
                        <div><span>Fecha</span><strong>{{ $entrada->usada_en?->translatedFormat('D. d M Y, H:i:s') }}</strong></div>
                    </div>
                @endif

                <a href="{{ route('entradas.validar', ['evento_id' => $eventoSeleccionado->id]) }}" class="validacion-otro">Escanear otro QR</a>
            @else
                <div class="validacion-espera">
                    <span class="validacion-espera-icono" aria-hidden="true">⌁</span>
                    <strong>Esperando un código</strong>
                    <span>Escanea un QR o ingresa el código manualmente.</span>
                </div>
            @endisset
        </section>
    </div>
    @else
        <section class="acceso-sin-eventos">
            <strong>No tienes eventos asignados</strong>
            <p>Un administrador debe asignarte un evento publicado antes de que puedas validar entradas.</p>
        </section>
    @endif
</div>

@if($eventoSeleccionado)
<script>
    const videoCamara = document.getElementById('camara-video');
    const camaraVacia = document.getElementById('camara-vacia');
    const botonIniciar = document.getElementById('camara-iniciar');
    const botonDetener = document.getElementById('camara-detener');
    const estadoCamara = document.getElementById('camara-estado');
    const selectorCamara = document.getElementById('camara-selector');
    const selectorContenedor = document.getElementById('camara-selector-contenedor');
    const campoCodigo = document.getElementById('codigo');
    const formularioValidacion = document.getElementById('validacion-formulario');
    let flujoCamara = null;
    let detectorQr = null;
    let lecturaActiva = false;
    let ultimaLectura = 0;

    function informarCamara(mensaje, tipo = '') {
        estadoCamara.textContent = mensaje;
        estadoCamara.className = `camara-estado ${tipo}`.trim();
    }

    function detenerCamara() {
        lecturaActiva = false;
        if (flujoCamara) {
            flujoCamara.getTracks().forEach(pista => pista.stop());
        }
        flujoCamara = null;
        videoCamara.srcObject = null;
        videoCamara.classList.remove('activa');
        camaraVacia.hidden = false;
        botonIniciar.disabled = false;
        botonDetener.disabled = true;
        informarCamara('La cámara está detenida.');
    }

    async function cargarCamaras() {
        const dispositivos = await navigator.mediaDevices.enumerateDevices();
        const camaras = dispositivos.filter(dispositivo => dispositivo.kind === 'videoinput');
        const seleccionActual = selectorCamara.value;
        selectorCamara.replaceChildren();
        camaras.forEach((camara, indice) => {
            const opcion = document.createElement('option');
            opcion.value = camara.deviceId;
            opcion.textContent = camara.label || `Cámara ${indice + 1}`;
            selectorCamara.appendChild(opcion);
        });
        if (camaras.some(camara => camara.deviceId === seleccionActual)) {
            selectorCamara.value = seleccionActual;
        }
        selectorContenedor.hidden = camaras.length < 2;
    }

    async function prepararDetector() {
        detectorQr = null;
        if (!('BarcodeDetector' in window)) {
            return false;
        }
        const formatos = await BarcodeDetector.getSupportedFormats();
        if (!formatos.includes('qr_code')) {
            return false;
        }
        detectorQr = new BarcodeDetector({ formats: ['qr_code'] });
        return true;
    }

    async function leerQr(momento) {
        if (!lecturaActiva || !detectorQr || videoCamara.readyState < 2) {
            if (lecturaActiva) {
                requestAnimationFrame(leerQr);
            }
            return;
        }
        if (momento - ultimaLectura > 450) {
            ultimaLectura = momento;
            try {
                const codigos = await detectorQr.detect(videoCamara);
                const codigo = codigos.find(resultado => resultado.rawValue)?.rawValue?.trim();
                if (codigo) {
                    campoCodigo.value = codigo;
                    detenerCamara();
                    formularioValidacion.requestSubmit();
                    return;
                }
            } catch (error) {
                informarCamara('La cámara continúa activa, pero no se pudo analizar este cuadro.', 'advertencia');
            }
        }
        requestAnimationFrame(leerQr);
    }

    async function iniciarCamara() {
        if (!navigator.mediaDevices?.getUserMedia) {
            informarCamara('Este navegador no permite acceder a la cámara.', 'error');
            return;
        }
        detenerCamara();
        botonIniciar.disabled = true;
        informarCamara('Solicitando acceso a la cámara...');
        try {
            const dispositivo = selectorCamara.value;
            flujoCamara = await navigator.mediaDevices.getUserMedia({
                video: dispositivo
                    ? { deviceId: { exact: dispositivo } }
                    : { facingMode: { ideal: 'environment' } },
                audio: false,
            });
            videoCamara.srcObject = flujoCamara;
            await videoCamara.play();
            videoCamara.classList.add('activa');
            camaraVacia.hidden = true;
            botonIniciar.disabled = true;
            botonDetener.disabled = false;
            await cargarCamaras();
            const lecturaDisponible = await prepararDetector();
            informarCamara(
                lecturaDisponible
                    ? 'Cámara activa. Buscando un código QR.'
                    : 'Cámara activa. La lectura automática de QR no está disponible; utiliza el código manual.',
                'activa'
            );
            lecturaActiva = lecturaDisponible;
            if (lecturaActiva) {
                requestAnimationFrame(leerQr);
            }
        } catch (error) {
            detenerCamara();
            const mensajes = {
                NotAllowedError: 'El permiso de cámara fue rechazado o está bloqueado.',
                NotFoundError: 'No se encontró una cámara compatible.',
                NotReadableError: 'La cámara está siendo utilizada por otra aplicación.',
                OverconstrainedError: 'La cámara seleccionada ya no está disponible.',
            };
            informarCamara(mensajes[error.name] || 'No fue posible iniciar la cámara.', 'error');
        }
    }

    botonIniciar.addEventListener('click', iniciarCamara);
    botonDetener.addEventListener('click', detenerCamara);
    selectorCamara.addEventListener('change', iniciarCamara);
    window.addEventListener('beforeunload', detenerCamara);
</script>
@endif

@endsection

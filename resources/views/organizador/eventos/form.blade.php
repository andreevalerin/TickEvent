@extends('layouts.principal')

@section('titulo', ($evento->exists ? 'Editar' : 'Crear') . ' evento - TickEvent')

@section('contenido')

@php
    $zonasIniciales = old('zonas', $zonasFormulario);

    if (count($zonasIniciales) === 0) {
        $zonasIniciales = [[
            'nombre' => '',
            'capacidad' => '',
            'activo' => true,
            'tarifas' => [[
                'nombre' => 'Regular',
                'precio' => '',
                'activo' => true,
            ]],
        ]];
    }
@endphp

<main class="compra-contenedor">
    <a href="{{ route('organizador.eventos.index') }}" class="volver-evento">
        ← Volver a mis eventos
    </a>

    <div class="compra-titulo">
        <span class="etiqueta">PANEL DEL ORGANIZADOR</span>
        <h1>{{ $evento->exists ? 'Editar evento' : 'Crear evento' }}</h1>
        <p>La capacidad total se calcula sumando todas las zonas.</p>
    </div>

    @if($errors->any())
        <div class="compra-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form
        action="{{ $evento->exists ? route('organizador.eventos.update', $evento) : route('organizador.eventos.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="organizador-formulario"
    >
        @csrf
        @if($evento->exists)
            @method('PUT')
        @endif

        <section class="compra-panel">
            <h2>Datos del evento</h2>

            <div class="formulario-grid">
                <label class="campo campo-amplio">
                    <span>Título</span>
                    <input type="text" name="titulo" value="{{ old('titulo', $evento->titulo) }}" maxlength="150" required>
                </label>

                <label class="campo">
                    <span>Categoría</span>
                    <select name="categoria" required>
                        <option value="concierto" @selected(old('categoria', $evento->categoria) === 'concierto')>Concierto</option>
                        <option value="partido" @selected(old('categoria', $evento->categoria) === 'partido')>Partido</option>
                    </select>
                </label>

                <label class="campo">
                    <span>Estado</span>
                    <select name="estado" required>
                        <option value="borrador" @selected(old('estado', $evento->estado ?: 'borrador') === 'borrador')>Borrador</option>
                        <option value="publicado" @selected(old('estado', $evento->estado) === 'publicado')>Publicado</option>
                    </select>
                </label>

                <label class="campo campo-amplio">
                    <span>Descripción</span>
                    <textarea name="descripcion" rows="4" maxlength="3000">{{ old('descripcion', $evento->descripcion) }}</textarea>
                </label>

                <label class="campo">
                    <span>Fecha y hora</span>
                    <input
                        type="datetime-local"
                        name="fecha_evento"
                        value="{{ old('fecha_evento', $evento->fecha_evento?->format('Y-m-d\TH:i')) }}"
                        required
                    >
                </label>

                <label class="campo">
                    <span>Lugar</span>
                    <input type="text" name="lugar" value="{{ old('lugar', $evento->lugar) }}" maxlength="150" required>
                </label>

                <label class="campo">
                    <span>Dirección</span>
                    <input type="text" name="direccion" value="{{ old('direccion', $evento->direccion) }}" maxlength="180">
                </label>

                <label class="campo">
                    <span>Ciudad</span>
                    <input type="text" name="ciudad" value="{{ old('ciudad', $evento->ciudad ?: 'Lima') }}" maxlength="80" required>
                </label>

                <label class="campo">
                    <span>Portada panorámica</span>
                    <input type="file" name="imagen_principal" accept="image/png,image/jpeg,image/webp">
                    @if($evento->imagen_principal)
                        <small>Actual: {{ $evento->imagen_principal }}</small>
                    @endif
                </label>

                <label class="campo">
                    <span>Plano y tarifario</span>
                    <input type="file" name="imagen_plano" accept="image/png,image/jpeg,image/webp">
                    @if($evento->imagen_plano)
                        <small>Actual: {{ $evento->imagen_plano }}</small>
                    @endif
                </label>
            </div>
        </section>

        <section class="compra-panel editor-zonas">
            <div class="editor-zonas-cabecera">
                <div>
                    <h2>Zonas y tarifas</h2>
                    <p>Todas las tarifas de una zona comparten su capacidad.</p>
                </div>
                <button type="button" class="boton-secundario" id="agregar-zona">Agregar zona</button>
            </div>

            <div id="lista-zonas">
                @foreach($zonasIniciales as $indiceZona => $zona)
                    <article class="zona-editor" data-indice-zona="{{ $indiceZona }}" data-siguiente-tarifa="{{ count($zona['tarifas'] ?? []) }}">
                        <div class="zona-editor-cabecera">
                            <h3>Zona {{ $indiceZona + 1 }}</h3>
                            <button type="button" class="quitar-zona">Quitar zona</button>
                        </div>

                        @if(!empty($zona['id']))
                            <input type="hidden" name="zonas[{{ $indiceZona }}][id]" value="{{ $zona['id'] }}">
                        @endif

                        <div class="formulario-grid">
                            <label class="campo">
                                <span>Nombre</span>
                                <input type="text" name="zonas[{{ $indiceZona }}][nombre]" value="{{ $zona['nombre'] ?? '' }}" maxlength="60" required>
                            </label>

                            <label class="campo">
                                <span>Capacidad</span>
                                <input type="number" name="zonas[{{ $indiceZona }}][capacidad]" value="{{ $zona['capacidad'] ?? '' }}" min="1" required>
                            </label>

                            <label class="campo campo-check">
                                <input type="hidden" name="zonas[{{ $indiceZona }}][activo]" value="0">
                                <input type="checkbox" name="zonas[{{ $indiceZona }}][activo]" value="1" @checked((bool) ($zona['activo'] ?? true))>
                                <span>Zona activa</span>
                            </label>
                        </div>

                        <div class="tarifas-editor">
                            @foreach(($zona['tarifas'] ?? []) as $indiceTarifa => $tarifa)
                                <div class="tarifa-editor">
                                    @if(!empty($tarifa['id']))
                                        <input type="hidden" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][id]" value="{{ $tarifa['id'] }}">
                                    @endif
                                    <input type="text" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][nombre]" value="{{ $tarifa['nombre'] ?? '' }}" placeholder="Tarifa" maxlength="60" required>
                                    <input type="number" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][precio]" value="{{ $tarifa['precio'] ?? '' }}" placeholder="Precio" min="0.01" step="0.01" required>
                                    <label>
                                        <input type="hidden" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][activo]" value="0">
                                        <input type="checkbox" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][activo]" value="1" @checked((bool) ($tarifa['activo'] ?? true))>
                                        Activa
                                    </label>
                                    <button type="button" class="quitar-tarifa">Quitar</button>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="agregar-tarifa boton-secundario">Agregar tarifa</button>
                    </article>
                @endforeach
            </div>
        </section>

        <button type="submit" class="compra-continuar">
            {{ $evento->exists ? 'Guardar cambios' : 'Crear evento' }}
        </button>
    </form>
</main>

<script>
    const listaZonas = document.getElementById('lista-zonas');
    const agregarZona = document.getElementById('agregar-zona');
    let siguienteZona = {{ count($zonasIniciales) }};

    function tarifaNueva(indiceZona, indiceTarifa) {
        const tarifa = document.createElement('div');
        tarifa.className = 'tarifa-editor';
        tarifa.innerHTML = `
            <input type="text" name="zonas[${indiceZona}][tarifas][${indiceTarifa}][nombre]" value="Regular" placeholder="Tarifa" maxlength="60" required>
            <input type="number" name="zonas[${indiceZona}][tarifas][${indiceTarifa}][precio]" placeholder="Precio" min="0.01" step="0.01" required>
            <label>
                <input type="hidden" name="zonas[${indiceZona}][tarifas][${indiceTarifa}][activo]" value="0">
                <input type="checkbox" name="zonas[${indiceZona}][tarifas][${indiceTarifa}][activo]" value="1" checked>
                Activa
            </label>
            <button type="button" class="quitar-tarifa">Quitar</button>
        `;
        return tarifa;
    }

    function zonaNueva(indiceZona) {
        const zona = document.createElement('article');
        zona.className = 'zona-editor';
        zona.dataset.indiceZona = indiceZona;
        zona.dataset.siguienteTarifa = 1;
        zona.innerHTML = `
            <div class="zona-editor-cabecera">
                <h3>Nueva zona</h3>
                <button type="button" class="quitar-zona">Quitar zona</button>
            </div>
            <div class="formulario-grid">
                <label class="campo">
                    <span>Nombre</span>
                    <input type="text" name="zonas[${indiceZona}][nombre]" maxlength="60" required>
                </label>
                <label class="campo">
                    <span>Capacidad</span>
                    <input type="number" name="zonas[${indiceZona}][capacidad]" min="1" required>
                </label>
                <label class="campo campo-check">
                    <input type="hidden" name="zonas[${indiceZona}][activo]" value="0">
                    <input type="checkbox" name="zonas[${indiceZona}][activo]" value="1" checked>
                    <span>Zona activa</span>
                </label>
            </div>
            <div class="tarifas-editor"></div>
            <button type="button" class="agregar-tarifa boton-secundario">Agregar tarifa</button>
        `;
        zona.querySelector('.tarifas-editor').appendChild(tarifaNueva(indiceZona, 0));
        return zona;
    }

    agregarZona.addEventListener('click', () => {
        listaZonas.appendChild(zonaNueva(siguienteZona));
        siguienteZona++;
    });

    listaZonas.addEventListener('click', evento => {
        const zona = evento.target.closest('.zona-editor');

        if (evento.target.classList.contains('quitar-zona')) {
            if (listaZonas.querySelectorAll('.zona-editor').length > 1) {
                zona.remove();
            }
            return;
        }

        if (evento.target.classList.contains('agregar-tarifa')) {
            const indiceZona = zona.dataset.indiceZona;
            const indiceTarifa = Number(zona.dataset.siguienteTarifa);
            zona.querySelector('.tarifas-editor').appendChild(
                tarifaNueva(indiceZona, indiceTarifa)
            );
            zona.dataset.siguienteTarifa = indiceTarifa + 1;
            return;
        }

        if (evento.target.classList.contains('quitar-tarifa')) {
            const tarifas = zona.querySelectorAll('.tarifa-editor');
            if (tarifas.length > 1) {
                evento.target.closest('.tarifa-editor').remove();
            }
        }
    });
</script>

@endsection

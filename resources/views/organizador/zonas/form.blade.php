@extends('layouts.organizador')

@section('titulo', ($zona->exists ? 'Editar' : 'Registrar') . ' zona - TickEvent')

@section('contenido')

@php
    $tarifasIniciales = old('tarifas', $zona->exists
        ? $zona->tarifas->map(fn ($tarifa) => [
            'id' => $tarifa->id,
            'nombre' => $tarifa->nombre,
            'precio' => $tarifa->precio,
            'activo' => $tarifa->activo,
        ])->values()->all()
        : []);

    if (count($tarifasIniciales) === 0) {
        $tarifasIniciales = [[
            'nombre' => 'Regular',
            'precio' => '',
            'activo' => true,
        ]];
    }
@endphp

<div class="panel-rol-contenido">

    <a href="{{ route('organizador.zonas.index') }}" class="volver-evento">← Volver a zonas</a>

    <div class="compra-titulo">
        <span class="etiqueta">PANEL DEL ORGANIZADOR</span>
        <h1>{{ $zona->exists ? 'Editar zona' : 'Registrar zona' }}</h1>
        <p>La relación se muestra mediante el nombre del evento.</p>
    </div>

    @if($errors->any())
        <div class="compra-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if(!$zona->exists && $eventos->isEmpty())
        <section class="compra-panel entradas-vacio">
            <h2>Primero debes registrar un evento</h2>
            <p>No existen eventos disponibles para asociar la zona.</p>
            <a href="{{ route('organizador.eventos.create') }}" class="boton-comprar-link">Crear evento</a>
        </section>
    @else
        <form
            action="{{ $zona->exists ? route('organizador.zonas.update', $zona) : route('organizador.zonas.store') }}"
            method="POST"
            class="organizador-formulario"
        >
            @csrf
            @if($zona->exists)
                @method('PUT')
            @endif

            <section class="compra-panel">
                <div class="formulario-grid">
                    <label class="campo campo-amplio">
                        <span>Evento</span>
                        @if($zona->exists)
                            <input type="text" value="{{ $zona->evento->titulo }}" disabled>
                        @else
                            <select name="evento_id" required>
                                <option value="">Selecciona un evento</option>
                                @foreach($eventos as $evento)
                                    <option value="{{ $evento->id }}" @selected((string) old('evento_id') === (string) $evento->id)>
                                        {{ $evento->titulo }} — {{ $evento->fecha_evento->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </label>

                    <label class="campo">
                        <span>Nombre de la zona</span>
                        <input type="text" name="nombre" value="{{ old('nombre', $zona->nombre) }}" maxlength="60" required>
                    </label>

                    <label class="campo">
                        <span>Capacidad</span>
                        <input type="number" name="capacidad" value="{{ old('capacidad', $zona->capacidad) }}" min="1" max="1000000" required>
                    </label>

                    <label class="campo campo-check">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" @checked((bool) old('activo', $zona->exists ? $zona->activo : true))>
                        <span>Zona activa</span>
                    </label>
                </div>
            </section>

            <section class="compra-panel zona-tarifas-panel">
                <div class="admin-seccion-cabecera">
                    <div>
                        <h2>Tarifas de la zona</h2>
                        <p>Las tarifas comparten la capacidad total de esta zona.</p>
                    </div>
                    <button type="button" class="boton-secundario" id="agregar-tarifa">Añadir tarifa</button>
                </div>

                <div class="tarifas-editor" id="tarifas-zona">
                    @foreach($tarifasIniciales as $indice => $tarifa)
                        <div class="tarifa-editor">
                            @if(!empty($tarifa['id']))
                                <input type="hidden" name="tarifas[{{ $indice }}][id]" value="{{ $tarifa['id'] }}">
                            @endif
                            <input type="text" name="tarifas[{{ $indice }}][nombre]" value="{{ $tarifa['nombre'] ?? '' }}" placeholder="Nombre de tarifa" maxlength="60" required>
                            <input type="number" name="tarifas[{{ $indice }}][precio]" value="{{ $tarifa['precio'] ?? '' }}" placeholder="Precio" min="0.01" max="99999999.99" step="0.01" required>
                            <label>
                                <input type="hidden" name="tarifas[{{ $indice }}][activo]" value="0">
                                <input type="checkbox" name="tarifas[{{ $indice }}][activo]" value="1" @checked((bool) ($tarifa['activo'] ?? true))>
                                Activa
                            </label>
                        </div>
                    @endforeach
                </div>
            </section>

            <button type="submit" class="compra-continuar">
                {{ $zona->exists ? 'Guardar cambios' : 'Registrar zona' }}
            </button>
        </form>
    @endif
</div>

<script>
    const contenedorTarifas = document.getElementById('tarifas-zona');
    const botonAgregarTarifa = document.getElementById('agregar-tarifa');

    if (contenedorTarifas && botonAgregarTarifa) {
        botonAgregarTarifa.addEventListener('click', () => {
            const indice = contenedorTarifas.children.length;
            const tarifa = document.createElement('div');
            tarifa.className = 'tarifa-editor';
            tarifa.innerHTML = `
                <input type="text" name="tarifas[${indice}][nombre]" placeholder="Nombre de tarifa" maxlength="60" required>
                <input type="number" name="tarifas[${indice}][precio]" placeholder="Precio" min="0.01" max="99999999.99" step="0.01" required>
                <label>
                    <input type="hidden" name="tarifas[${indice}][activo]" value="0">
                    <input type="checkbox" name="tarifas[${indice}][activo]" value="1" checked>
                    Activa
                </label>
            `;
            contenedorTarifas.appendChild(tarifa);
        });
    }
</script>

@endsection

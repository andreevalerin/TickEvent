@extends('layouts.principal')

@section('titulo', 'Validar entrada - TickEvent')

@section('contenido')

<main class="compra-contenedor">
    <div class="compra-titulo">
        <span class="etiqueta">CONTROL DE ACCESO</span>
        <h1>Validación de entradas</h1>
        <p>Ingresa el código de una entrada para verificarla una sola vez.</p>
    </div>

    @if($errors->any())
        <div class="compra-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="validacion-columnas">
        <section class="compra-panel">
            <h2>Ingresar código manualmente</h2>
            <p>Usa el código completo mostrado en la entrada digital.</p>

            <form action="{{ route('entradas.validar.procesar') }}" method="POST" class="validacion-formulario">
                @csrf
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
                <button type="submit" class="compra-continuar">Validar entrada</button>
            </form>
        </section>

        <section class="compra-panel validacion-resultado">
            @isset($permitido)
                <div class="validacion-estado {{ $permitido ? 'permitido' : 'denegado' }}">
                    <strong>{{ $permitido ? 'Acceso permitido' : 'Acceso denegado' }}</strong>
                    <span>{{ $motivo }}</span>
                </div>

                @if($entrada)
                    <div class="compra-detalle">
                        <div>
                            <span>Evento</span>
                            <strong>{{ $entrada->reserva->evento->titulo }}</strong>
                        </div>
                        <div>
                            <span>Zona</span>
                            <strong>{{ $entrada->tarifa->zona->nombre }}</strong>
                        </div>
                        <div>
                            <span>Tarifa</span>
                            <strong>{{ $entrada->tarifa->nombre }}</strong>
                        </div>
                        <div>
                            <span>DNI</span>
                            <strong>{{ $entrada->dni }}</strong>
                        </div>
                        <div>
                            <span>Estado</span>
                            <strong>{{ strtoupper($entrada->estado) }}</strong>
                        </div>
                    </div>
                @endif
            @else
                <div class="validacion-espera">
                    <strong>Esperando un código</strong>
                    <span>El resultado aparecerá aquí.</span>
                </div>
            @endisset
        </section>
    </div>
</main>

@endsection
